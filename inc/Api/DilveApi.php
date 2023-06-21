<?php 

namespace Inc\Dilve\Api;

use GuzzleHttp\Client;

class DilveApi {
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
      		$url  = 'http://'.$this->url_host.'/'.$this->url_path.'/getResourceX.do?user='.$this->url_user.'&password='.$this->url_pass;
      		$url .= '&identifier='.$isbn.'&resource='.urlencode($filename);
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
	function create_cover($data, $filename, $mimetype = 'image/jpeg', $force = FALSE) {
		$current_user = wp_get_current_user();

		//Primero intentamos cargar la imagen de la base de datos
  
		$filepath = sprintf("%s/%s/portadas/%s", ABSPATH, wp_upload_dir()['basedir'], $filename);
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
			wp_update_attachment_metadata($file_id, wp_generate_attachment_metadata($file_id, $filepath));
			}
    	}
	return get_post($file_id);
  }
}