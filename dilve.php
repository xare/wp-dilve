<?php
    /*
    Plugin Name: Dilve for WP plugin
    Description: Dilve plugin for WordPress
    Version: 1.0
    Author: xare
    */

defined( 'ABSPATH' ) or die ( 'Acceso prohibido');

// Require once the Composer Autoload
if( file_exists( dirname( __FILE__).'/vendor/autoload.php' ) ){
  require_once dirname( __FILE__).'/vendor/autoload.php';
}

/**
 * The code that runs during plugin Activation
 *
 * @return void
 */
function activate_dilve(){
  Inc\Dilve\Base\Activate::activate();
}
register_activation_hook( __FILE__, 'activate_dilve');

/**
 * The code that runs during plugin Deactivation
 *
 * @return void
 */
function deactivate_dilve(){
  Inc\Dilve\Base\Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_dilve');

if(class_exists( 'Inc\\Dilve\\Init' )) {
  Inc\Dilve\Init::register_services();
}