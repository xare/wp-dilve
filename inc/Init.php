<?php

namespace Inc\Dilve;

use Inc\Dilve\Base\Cron;
use Inc\Dilve\Base\DilveScanProductsFormController;
use Inc\Dilve\Base\Enqueue;
use Inc\Dilve\Commands\DilveHelloCommand;
use Inc\Dilve\Commands\DilveScanProductsCommand;
use Inc\Dilve\Commands\DilveMediaCleanup;
use Inc\Dilve\Pages\Dashboard;

final class Init
{
  /**
   * Store all the classes inside an array
   *
   * @return array Full list of classes
   */
  public static function get_services():Array {
    return [
      Dashboard::class,
      DilveScanProductsCommand::class,
      DilveHelloCommand::class,
      DilveMediaCleanup::class,
      DilveScanProductsFormController::class,
      Enqueue::class,
      Cron::class,
    ];
  }

  /**
   * Loop through the classes, initialize them
   * and call the register() method if it exists
   *
   * @return void
   */
  public static function register_services() {
    foreach(self::get_services() as $class){
      $service = self::instantiate( $class );
      if(method_exists($service,'register')) {
          $service->register();
      }
    }
  }
  /**
   * Initialize the class
   *
   * @param [type] $class class from the services array
   * @return class instance new instance of the class
   */
  private static function instantiate( $class ) {
    return new $class();
  }
}
