<?php

namespace Inc\Dilve\Commands;

use WP_CLI;

/**
 * Class DilveScanProductsCommand
 */
class DilveScanProductsCommand {
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
            'limit' => 10
        ];
        $products = wc_get_products($args);
        foreach( $products as $product ) {
            $ean = get_post_meta( $product->id, '_ean', true );
            WP_CLI::line( 'EAN:' . $ean );
        }
        WP_CLI::line( 'End of products.' );
    }
}

