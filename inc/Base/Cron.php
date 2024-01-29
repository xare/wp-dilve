<?php

/**
 * @package dilve
 */
namespace Inc\Dilve\Base;

use Inc\Dilve\Api\DilveApi;

class Cron extends BaseController {

    public function register() {
        if ( ! wp_next_scheduled( 'dilve_cron_event' ) ) {
            wp_schedule_event( time(), 'daily', 'dilve_cron_event' );
        }
        add_action( 'dilve_cron_event', [ $this, 'dilveCron' ] );
    }
    /**
     * dilveCron
     *
     * @return void
     */
    function dilveCron() {
        $dilveApi = new DilveApi;
        $dilveApi->scanProducts();
    }
}