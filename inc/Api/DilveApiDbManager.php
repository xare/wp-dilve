<?php

namespace Inc\Dilve\Api;

class DilveApiDbManager {
    const DILVE_LOG_TABLE = 'dilve_log';
    const DILVE_LINES_TABLE = 'dilve_lines';
    const DILVE_LOGGER_TABLE = 'dilve_logger';

    static $dilveLogKeys = [
		'start_date', // date
		'end_date', // date
		'status', // string waiting | enqueued | processed
		'scanned_items', // int number of lines
        'processed_items', // int number of lines
	];

    static $dilveLinesKeys = [
        'log_id', // int relation oneToMany with dilve_log
        'isbn',    // string
        'path',    // string
        'url_origin', // string
        'url_target', // string
        'date',    // date
        'isError', // boolean
        'error',   // string
        'attempts', // int
    ];

     /**
     * hasAttachment
     * @param int $product_id
     * @return bool
     */
     public function hasAttachment(int $product_id): bool {
        // Get the EAN number from product metadata
        $ean = get_post_meta($product_id, '_ean', true);

        // Check if EAN is set
        if (empty($ean)) {
            return false;
        }

        // Construct the expected file path
        $upload_dir = wp_upload_dir();
        $expected_file_path = $upload_dir['basedir'] . '/portadas/' . $ean . '.jpg';

        // Get the ID of the product's featured image
        $thumbnail_id = get_post_thumbnail_id($product_id);

        // Check if the product has a featured image
        if (!$thumbnail_id) {
            return false;
        }

        // Get the file path of the featured image
        $thumbnail_path = get_attached_file($thumbnail_id);

        // Compare the paths
        if ($thumbnail_path === $expected_file_path) {
            return true;
        } else {
            return false;
        }
    }

    /**
  	 * set_featured_image_for_product
  	 *
  	 * @param  mixed $file_id
  	 * @param  mixed $ean
  	 * @return void
  	 */
  	public function set_featured_image_for_product( $file_id, $ean ): void {
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

			// Check if a thumbnail is already set for the product
			if (get_post_thumbnail_id($product_id)) {
				continue; // Skip setting the featured image if already set
			}
			set_post_thumbnail($product_id, $file_id);
		}
	}

    /**
     * countAllProducts
     *
     * @return int
     */
    public function countAllProducts(): int {
        return count( wc_get_products( [
            'status' => 'publish',
            'limit' => -1,
        ]));
	}
}