<?php

namespace Inc\Dilve\Commands;

use Inc\Dilve\Api\DilveApi;
use Inc\Dilve\Api\DilveApiDbLinesManager;
use Inc\Dilve\Api\DilveApiDbLogManager;
use WP_CLI;

/**
 * Class DilveScanProductsCommand
 */
class DilveScanProductsCommand {

    private $dilveApi;
    public function __construct() {
        $this->dilveApi = new DilveApi();
    }
	public function register() {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::add_command( 'dilve scanProducts', [$this, 'execute'] );
        }
    }
    /**
     * Prints a hello world message
     *
     * ## OPTIONS
     *
     *
     * ## EXAMPLES
     *
     *     wp dilve scanProducts
     *
     * @when after_wp_load
     */
    public function execute( $args, $assoc_args ) {
        //Read all products
        $dilveApiDbLogManager = new DilveApiDbLogManager;
        $dilveApiDbLinesManager = new DilveApiDbLinesManager;
        $args = [
            'status' => 'publish',
            'limit' => -1
        ];

        $products = wc_get_products($args);
        $log_id = $dilveApiDbLogManager->insertLogData('logged', count($products));
        foreach( $products as $product ) {
            $ean = get_post_meta( $product->get_id(), '_ean', true );
            $book = $this->dilveApi->search($ean);
            $filepath = sprintf("%s/portadas/%s", wp_upload_dir()['basedir'], $ean.'.jpg');
            if( $book && isset($book['cover_url'])) {
                $line_id = $dilveApiDbLinesManager->insertLinesData($log_id, $ean, $filepath);
                WP_CLI::line( 'COVER URL:' . $book['cover_url'] );
                $dilveApiDbLinesManager->set_origin_url($line_id, $book['cover_url']);
                $dilveApiDbLinesManager->setBook($product->get_title(), $product->get_id(), $line_id);
                $cover_post = $this->dilveApi->create_cover($book['cover_url'], $ean.'.jpg');
                $this->dilveApi->set_featured_image_for_product($cover_post->ID, $ean);
                $dilveApiDbLinesManager->set_url_target($line_id, $product->get_id());
            }
            WP_CLI::line( 'EAN:' . $ean );
        }
        $dilveApiDbLogManager->setLogStatus($log_id, 'processed');
        WP_CLI::line( 'End of products.' );
    }
}

