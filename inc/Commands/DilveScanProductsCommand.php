<?php

namespace Inc\Dilve\Commands;

use Inc\Dilve\Api\DilveApi;
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
        $args = [
            'status' => 'publish',
            'limit' => -1
        ];
        $products = wc_get_products($args);
        foreach( $products as $product ) {
            $ean = get_post_meta( $product->get_id(), '_ean', true );
            $book = $this->dilveApi->search($ean);
            if( $book && isset($book['cover_url'])) {
                $cover_post = $this->dilveApi->create_cover($book['cover_url'],$ean.'.jpg');
                $this->dilveApi->set_featured_image_for_product($cover_post->ID, $ean);

            }
            WP_CLI::line( 'EAN:' . $ean );
        }
        WP_CLI::line( 'End of products.' );
    }
}

