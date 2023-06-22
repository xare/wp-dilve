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

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

  }
 }