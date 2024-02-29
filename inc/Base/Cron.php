<?php

/**
 * @package dilve
 */
namespace Inc\Dilve\Base;

use Inc\Dilve\Api\DilveApi;
use Inc\Dilve\Api\DilveApiDbLogManager;
use Inc\Dilve\Api\DilveApiDbManager;

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
        $batch_size = 6;
        $dilveApi = new DilveApi;
        $dilveApiDbManager = new DilveApiDbManager;
        $dilveApiDbLogManager = new DilveApiDbLogManager;
        error_log('Start Cron '. date('Y-m-d') );
        $totalLines = $dilveApiDbManager->countAllProducts();
        $log_id = $dilveApiDbLogManager->insertLogData('logged', $totalLines);
        do {
            $offset = get_option( 'last_processed_dilve_offset', 0 );
            $jsonResponse = $dilveApi->scanProducts($log_id, $batch_size, $offset);
            $responseArray = json_decode($jsonResponse, true);
            error_log(var_export($responseArray, true));
            error_log(var_export($responseArray['hasMore'], true));
            if($responseArray['hasMore'] == false) {
                update_option( 'last_processed_dilve_offset', 0 );
            }
        } while ( $responseArray['hasMore'] == true );
        error_log('End Cron '. $jsonResponse );

    }
}