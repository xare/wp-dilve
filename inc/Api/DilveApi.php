<?php 

namespace Inc\Dilve\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class DilveApi {

	private $url_host;
  	private $url_path;
	private $dilveSettings;
  	private $url_user;
  	private $url_pass;

	public function __construct(){
		$this->dilveSettings = get_option('dilve_settings');
		$this->url_host = "www.dilve.es";
		$this->url_path = "/dilve/dilve";
		$this->url_user = $this->dilveSettings['dilve_user'];
    	$this->url_pass = $this->dilveSettings['dilve_pass'];
	}

	/**
	* Function DilveApi::search
	*
	* @param string $isbn
	*   ISBN code to search
	* @return hash
	*   hash data of book
	*/ 
	public function search($isbn) {
		$query  = 'http://'
					.$this->url_host.$this->url_path.
					'/getRecordsX.do?user='.
					$this->url_user.
					'&password='.$this->url_pass.
					'&identifier='.$isbn;
		# Get xml in ONIX version 2.1
		$query .= '&metadataformat=ONIX&version=2.1';
		# Get xml in CEGAL version 3
		#$query .= '&metadataformat=CEGAL&version=3&formatdetail=C';
		# By default responses are UTF-8 encoded, but force it
		$query .= '&encoding=UTF-8';
		/* var_dump($query);
		return; */
		$response = wp_remote_get($query);
		
		if ( is_wp_error( $response ) ) {
			return;  // In case of error return immediately.
		 } else {
			$body = wp_remote_retrieve_body( $response );  // Get the body of the response
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			   $xml = simplexml_load_string($body);
			   // Your code here to handle the $xml object
			} else {
			   return;
			}
		 }
		
		if($xml->ONIXMessage->Product != NULL ) {
			$xml_book = $xml->ONIXMessage->Product[0];
			$book = [];
			if ($xml_book) {
			
			//drupal_set_message(dprint_r($xml_book, 1));
			
			$book['isbn'] = $isbn;//(string)$xml_book->RecordReference;
			$book['ean'] = (string)$xml_book->RecordReference;
			$book['date'] = (int)$xml_book->PublicationDate;
			$book['year'] = substr($book['date'],0, 4);
			
			#Get Price
			foreach($xml_book->SupplyDetail->Price as $price) {
				$book['price'] = (float)$price->PriceAmount;
				$book['price'] = str_replace('.', '', number_format($book['price'], 2));
			}
			# Get title
			foreach($xml_book->Title as $title) {
				if ($title->TitleType == "01") {
				$book["title"] = (string)$title->TitleText;
				if ($title->Subtitle) {
					$book["subtitle"] = (string)$title->Subtitle;
				}
				}
			}
			
			//Get Publisher
			foreach ($xml_book->Publisher as $publisher) {
				if ($publisher->NameCodeType == 02) {
				$book['publisher'] = (string)$xml_book->Publisher->PublisherName;
				}
			}
			
			# Get author
			foreach($xml_book->Contributor as $contributor) {
				if ($contributor->ContributorRole == "A01") {
				$author_name = (string) $contributor->PersonNameInverted;
				$author_description = (string) $contributor->BiographicalNote;
				if ($author_description) {
					$book["author"][] = array('name' => $author_name, 'description' => $author_description);
				} else {
					$book["author"][] = array('name' => $author_name);
				}
				}
			}
			# Get measurements
			foreach($xml_book->Measure as $measure) {
				switch ($measure->MeasureTypeCode) {
				case "01":
					$book["length"] = array('unit' => (string)$measure->MeasureUnitCode, 'value' => (string)$measure->Measurement);
					break;
				case "02":
					$book["width"] = array('unit' => (string)$measure->MeasureUnitCode, 'value' => (string)$measure->Measurement);
					break;
				case "08":
					$book["weight"] = array('unit' => (string)$measure->MeasureUnitCode, 'value' => (string)$measure->Measurement);
					break;
				}
			}
			# Get number of pages
			if($xml_book->NumberOfPages) {
				$book["pages"] = (string)$xml_book->NumberOfPages;
			}
				# Get descriptions
				foreach($xml_book->OtherText as $description) {
					switch ($description->TextTypeCode) {
					case "01":
					case "03":
					case "05":
					case "07":
					case "31":
						//Descripción general
						$book["description"] = nl2br( (string) $description->Text );
						break;
					case "09":
						$book["promoting_description"] = nl2br( (string) $description->Text );
						break;
					case "12":
						$book["short_description"] = nl2br( (string) $description->Text );
						break;
					case "13":
						if ( count($book['author']) == 1 ) {
						$book["author"][0]["description"] = nl2br( (string) $description->Text );
						}
						break;
					case "23":
						$book["preview_url"] = $this->get_file_url((string) $description->TextLink, $isbn);
						#print "\n---> Recogido fichero de preview: " . $book["*preview_url"] ." --- ";
						#print_r($description);
						break;
					default:
						#print "\n-----------------------> Tipo de texto no definido (".$description->TextTypeCode.") para el libro con ISBN ".$isbn."\n\n";
					}
				}
				# Get cover URL
				foreach ($xml_book->MediaFile as $media) {
					switch ($media->MediaFileTypeCode) {
					# Covers
					case "03":
					case "04":
					case "05":
					case "06":
						# Its better dilve uris
						if (!isset($book["cover_url"]) || $media->MediaFileLinkTypeCode == "06") {
						$book["cover_url"] = $this->get_file_url((string) $media->MediaFileLink, $isbn);
						}
					break;
					# Cover miniature
					case "07":
						break;
					# Author image
					case "08":
						$book["image_author_url"] = $this->get_file_url((string) $media->MediaFileLink, $isbn);
						#print "\n---> Recogido imagen del autor: " . $book["*image_author_url"];
						#print "\n---> Formato: " . $media->MediaFileFormatCode;
						#print "\n---> Tipo de Enlace: " . $media->MediaFileLinkTypeCode;
						break;
					# Publisher logo
					case "17":
						$book["image_publisher_url"] = $this->get_file_url((string) $media->MediaFileLink, $isbn);
						#print "\n---> Recogido logo de editorial: " . $book["*image_publisher_url"];
						#print "\n---> Formato: " . $media->MediaFileFormatCode;
						#print "\n---> Tipo de Enlace: " . $media->MediaFileLinkTypeCode;
						break;
					# Preview book
					case "51";
						#$book["*preview_media_url"] = $this->::get_file_url((string) $media->MediaFileLink, $isbn);
						#print "\n---> Recogido fichero de preview: " . $book["*preview_media_url"];
						#print "\n---> Formato: " . $media->MediaFileFormatCode;
						#print "\n---> Tipo de Enlace: " . $media->MediaFileLinkTypeCode;
						#break;e
					default:
						#print_r ($media);
						#print "\n-----------------------> Tipo de medio no definido (".$media->MediaFileTypeCode.") para el libro con ISBN ".$isbn."\n\n";
					}
				}
			} 
		} else {
			$book = (string)$xml->error->text;
		}
		return $book;
  	}

	/**
	 * Function DilveSearch::get_file_url
	*
	* @param string $filename
	*   local or remote filename
	* @param string $isbn
	*   ISBN code to search
	* @return string
	*   Full URL of requested resource
	*/
  	private function get_file_url($filename, $isbn) {
    	# If URL is a DILVE reference, complete full request
    	if (strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) {
      		$url = $filename;
    	} else {
      		$url  = 'http://'.$this->url_host.$this->url_path.
					'/getResourceX.do?user='.$this->url_user.
					'&password='.$this->url_pass;
      		$url .= '&identifier='.$isbn.
					'&resource='.urlencode($filename);
    	}
    	return $url;
  	}

	/**
	 * Checks if the cover exists and if it does returns the file object.
	 * It it doesn't exists downloads it and creates the object
	 *
	 * @param type $url
	 * @param type $isbn
	 * @return type
	 */
	function create_cover($url, $filename, $mimetype = 'image/jpeg', $force = FALSE) {
		$current_user = wp_get_current_user();
		$client = new Client(['verify' => false]);
		var_dump($url);
		try {
			$response = $client->get($url);
			if( $response->getStatusCode() == 200 ) {
				$data = $response->getBody();
				//Primero intentamos cargar la imagen de la base de datos
				$filepath = sprintf("%s/portadas/%s", wp_upload_dir()['basedir'], $filename);
				// First, check if the image exists in the database
				$existing_file = get_posts([
					'post_type' => 'attachment',
					'post_status' => 'inherit',
					'meta_key' => '_wp_attached_file',
					'meta_value' => 'portadas/' . $filename,
					'posts_per_page' => 1,
				]);
				//Si existe comprobamos que efectivamente el archivo está. Si no lo creamos
				if (!empty($existing_file) && file_exists($filepath) && !$force) {
					$file_id = $existing_file[0]->ID;
				} else {

					file_put_contents($filepath, $data);
					// Create a new attachment
					$attachment = array(
						'post_mime_type' => $mimetype,
						'post_title' => $filename,
						'post_content' => '',
						'post_status' => 'inherit',
						'guid' => wp_upload_dir()['url'] . '/portadas/' . $filename,
					);
	
					$file_id = wp_insert_attachment($attachment, $filepath, 0);
					if (!is_wp_error($file_id)) {
						wp_update_attachment_metadata(
							$file_id, 
							wp_generate_attachment_metadata($file_id, $filepath));
					}
				}
				return get_post($file_id);
			} else {
				echo $response->getStatusCode . ' '. $response->getReasonPhrase();
				return;
			}
		} catch(ClientException $clientException) {
			echo 'Error: ' . $clientException->getMessage();
        	return null;
		}
  }

  function set_featured_image_for_product($file_id, $ean) {
    $args = array(
        'post_type' => 'product',
        'meta_query' => array(
            array(
                'key' => '_ean',
                'value' => $ean,
            ),
        ),
    );

    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;

        set_post_thumbnail($product_id, $file_id);
    }
}

}