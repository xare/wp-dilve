<?php

/**
 * @package Dilve
 */

namespace Inc\Dilve\Base;

 class Activate {
  const DILVE_VERSION = '1.0.0';
  public static function activate() {
    flush_rewrite_rules();
    $default = [];
    if ( !get_option('dilve_settings')) {
      update_option('dilve_settings', $default);
    }
    update_option('dilve_version', self::DILVE_VERSION);
    self::dilve_update_tables();

  }

  public static function dilve_update_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $dilve_logger_table_name = $wpdb->prefix . 'dilve_logger';
    $dilve_log_table_name = $wpdb->prefix . 'dilve_log';
    $dilve_lines_table_name = $wpdb->prefix . 'dilve_lines';

    $dilve_log_sql = "CREATE TABLE $dilve_log_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      `start_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `end_date` datetime NULL DEFAULT NULL,
      `status` varchar(255) NOT NULL,
      `scanned_items` mediumint(9) NOT NULL,
      `processed_items` mediumint(9) NOT NULL,
      PRIMARY KEY (`id`)
    ) $charset_collate;";

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

    $dilve_lines_sql = "CREATE TABLE $dilve_lines_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      `log_id` mediumint(9) unsigned,
        `isbn` varchar(255) NOT NULL,
        `path` varchar(255) NOT NULL,
        `url_origin` varchar(255) NOT NULL,
        `url_target` varchar(255) NOT NULL,
        `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `isError` boolean,
        `error` varchar(255) NOT NULL,
        `attempts` mediumint(9) NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $dilve_logger_sql );
    dbDelta( $dilve_log_sql );
    dbDelta( $dilve_lines_sql );
  }

 }