<?php

namespace Inc\Dilve\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

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
    	$this->url_pass = isset($this->dilveSettings['dilve_pass']) ? $this->dilveSettings['dilve_pass'] : '';

	}

	/**
	* Function DilveApi::search
	*
	* @param string $isbn
	*   ISBN code to search
	* @return hash
	*   hash data of book
	*/
	public function search( string $isbn ) {
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
						if ( isset($book['author']) && count($book['author']) == 1 ) {
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
  	private function get_file_url( string $filename, string $isbn ): string {
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

	public function fetch_cover(string $url, string $isbn ) {
		$client = new Client(['verify' => false, 'timeout' => 10.0]);
		$dilveApiDbLinesManager = new DilveApiDbLinesManager;
		try {
			$response = $client->get($url);
		} catch( ConnectException $connectException ) {
			$error = ['message'=> $connectException->getMessage()];
			error_log( 'Connection exception: ' . $connectException->getMessage() );
			$dilveApiDbLinesManager->setError( $isbn, $error['message'] );
			return false;
		} catch ( RequestException $requestException ) {
			error_log( 'Request exception: ' . $requestException->getMessage() );
			if ($requestException->getResponse() instanceof ResponseInterface) {
				$error['statusCode'] = $requestException->getResponse()->getStatusCode();
				if ( $error['statusCode'] === 404 ) {
					$error['message'] = 'Error: Resource not found';
				} else {
					// Handle other client errors
					$error['message'] = 'Error: Client error - ' . $error['statusCode'];
				}
			} else {
				// Handle other exceptions
				$error['message'] = 'Error: ' . $e->getMessage();
			}
			error_log($error['message']);
			$dilveApiDbLinesManager->setError( $isbn, $error['message'] );
			return false;
		} catch (\Exception $exception) {
			$error['message'] = 'Error: ' . $exception->getMessage();
			error_log($error['message']);
			$dilveApiDbLinesManager->setError( $isbn, $error['message'] );
			return false;
		}
		if ( isset( $response->errors ) && count( $response->errors ) > 0 ) {
			var_dump( $response->errors );
			$errorString = '';
			foreach($response->errors as $error) {
				$errorString .= ' ' . $error;
			}
			$dilveApiDbLinesManager->setError( $isbn, $errorString );
			return false;
		}
		if( $response->getStatusCode() == 200 ) {
			return $response->getBody();
		}
	}

	/**
	 * Checks if the cover exists and if it does returns the file object.
	 * It it doesn't exists downloads it and creates the object
	 *
	 * @param string $url
	 * @param string $filename
	 * @param string $mimetype
	 * @param bool $force
	 * @return mixed
	 */
	public function create_cover(string $url, string $filename, string $mimetype = 'image/jpeg', bool $force = FALSE): mixed {
		$isbn = explode('.', $filename)[0];
		$data = $this->fetch_cover($url, $isbn);
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
  	}


	public function scanProducts($log_id, $batch_size = 0, $offset = -1) {
		$dilveApiDbManager = new DilveApiDbManager;
        $dilveApiDbLogManager = new DilveApiDbLogManager;
        $dilveApiDbLinesManager = new DilveApiDbLinesManager;

		// Read all products.
		// Query for all products.
		$batch_size = (isset($_POST['batch_size']) && $_POST['batch_size'] != null) ? $_POST['batch_size'] : -1;
		$offset = (isset($_POST['offset']) && $_POST['offset'] != null) ? $_POST['offset']: 0;
		$args = [
				'status' => 'publish',
				'limit' => $batch_size,
				'offset' => $offset
		];

		$products = wc_get_products($args);
		$eans = [];
		$hasMore = !empty($products);
		$totalLines = $this->_countAllProducts();
		$progress = 0;

        foreach( $products as $product ) {
            $ean = get_post_meta( $product->get_id(), '_ean', true );
            $book = $this->search($ean);
			$filepath = sprintf("%s/portadas/%s", wp_upload_dir()['basedir'], $ean.'.jpg');
            if ( $book && isset($book['cover_url'] ) ) {
				$line_id = $dilveApiDbLinesManager->insertLinesData($log_id, $ean, $filepath);
				if ( $dilveApiDbManager->hasAttachment( $product->get_id() ) ) {
                    $dilveApiDbLinesManager->setError( $ean, 'This product has already a cover.' );
                    continue;
                }
				$dilveApiDbLinesManager->set_origin_url($line_id, $book['cover_url']);
                $dilveApiDbLinesManager->setBook($product->get_title(), $product->get_id(), $line_id);
                if ($cover_post = $this->create_cover( $book['cover_url'], $ean.'.jpg' )) {
					$dilveApiDbManager->set_featured_image_for_product($cover_post->ID, $ean);
                    $dilveApiDbLinesManager->set_url_target($line_id, $product->get_id());
				} else {
					error_log('The coverpost was not properly created');
				}
            }
			$dilveApiDbLogManager->setLogStatus($log_id, 'processed');
			$response[] = [ 'id' => $product->get_id() ];
			error_log('Offset now: '. $offset );
			$progress = ( $offset / $totalLines ) * 100;
			error_log('Progress now: '. $progress );
			array_push($eans, $ean);
        }
		$response['hasMore'] = $hasMore;
		$response['eans'] = $eans;
		$response['message'] = $batch_size." books have been processed: ";
		$response['progress'] = number_format($progress, 2)." %";
        return json_encode( $response );
    }

	private function _countAllProducts(): int {
		$args = [
            'status' => 'publish',
            'limit' => -1,
        ];
        $products = wc_get_products($args);
		return (int) count($products);
	}
}