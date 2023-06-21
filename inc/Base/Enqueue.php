<?php

/**
 * @package starterkit
 */

namespace Inc\Dilve\Base;
use Inc\Dilve\Base\BaseController;
class Enqueue extends BaseController {
  public function register(){
    add_action ( 'admin_enqueue_scripts', [$this, 'enqueue_admin']);
    //add_action ( 'enqueue_scripts', [$this, 'enqueue']);
  }
function enqueue() {
        //enqueue all our scripts

        wp_enqueue_script('media_upload');
        wp_enqueue_media();
        wp_enqueue_style('DilveStyle', $this->plugin_url . 'dist/css/Dilve.css');
        wp_enqueue_script('DilveScript', $this->plugin_url . 'dist/js/Dilve.js');
      }
  function enqueue_admin() {
        // enqueue all our scripts
        wp_enqueue_style('DilveAdminStyle', $this->plugin_url .'dist/css/Dilve.min.css');
        wp_enqueue_script('DilveAdminScript', $this->plugin_url .'dist/js/Dilve.min.js');
      }
}