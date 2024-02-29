<?php

namespace Inc\Dilve\Api;

use DateTime;

class DilveApiDbLoggerManager extends DilveApiDbManager{


    public function dilveLogger( string $ean = '', string $url = '', array $metadata = [] ) {
        global $wpdb;

        try {
            $date = new DateTime();
            $wpdb->insert(
                $wpdb->prefix . self::DILVe_LOGGER_TABLE,
                array_combine( self::$dilveLoggerKeys,
                            [
                                $date,
                                $ean,
                                $url,
                                json_encode( $metadata ) ?? 'metadata',
                            ]),
                ['%s', '%s', '%s']
            );
            return true;
        } catch (\Exception $exception) {
            echo "Dilve Logger error: " . $exception->getMessage();
            return false;
        }
    }

    /**
     * getLatestLoggers
     *
     * @return string
     */
    public function getLatestLoggers(): string {
        global $wpdb;

        $results  = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM "
                            .$wpdb->prefix.self::DILVE_LOGGER_TABLE
                            ." ORDER BY date DESC LIMIT 20")
            , ARRAY_A);
        if( count( $results ) == 0 ) return 'No loggers found.';
        $html_list = '<ul>';
        foreach($results as $result) {
            $style = $result['type'] == 'error' ? ' style="color:red;"' : null;
            $metadata = json_decode($result['metadata']);
            $html_list .= "<li{$style}>".
                            " Ean: ". $result['ean'].
                            " Url: ". $result['url'].
                            " Metadata: ". $metadata->message .
                            "</li>";
        }
        $html_list .= '</ul>';
        return $html_list;
    }
}