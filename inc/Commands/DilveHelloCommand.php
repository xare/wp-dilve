<?php

namespace Inc\Dilve\Commands;

use WP_CLI;

/**
 * Class DilveHelloCommand
 */
class DilveHelloCommand {
	public function register() {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::add_command( 'dilve hello', [$this, 'execute'] );
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
     *     wp dilve hello
     *
     * @when after_wp_load
     */
    public function execute( $args, $assoc_args ) {
        WP_CLI::line( 'Hello, World!' );
    }
}

