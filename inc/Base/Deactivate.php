<?php

/**
 * @package Dilve
 */
namespace Inc\Dilve\Base;

 class Deactivate {
  public static function deactivate() {
    flush_rewrite_rules();
  }
 }