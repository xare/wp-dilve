<?php

/**
 * @package Dilve
 */

namespace Inc\Dilve\Base;

 class Activate {
  public static function activate() {
    global $wpdb;
    flush_rewrite_rules();

    $default = [];

    if ( !get_option('dilve_settings')) {
      update_option('dilve_settings', $default);
    }

    $charset_collate = $wpdb->get_charset_collate();
    $dilve_logger_table_name = $wpdb->prefix . 'dilve_logger';

    $dilve_logger_sql = "CREATE TABLE $dilve_logger_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      `log_id` mediumint(9) unsigned,
        `geslib_id` text,
        `type` varchar(255) NOT NULL,
        `action` varchar(255) NOT NULL,
        `entity` varchar(255) NOT NULL,
        `metadata` text,
        `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $dilve_logger_sql );

  }
 }