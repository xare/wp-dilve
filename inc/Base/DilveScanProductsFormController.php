<?php
/**
 * @package dilve
 */
namespace Inc\Dilve\Base;

use Inc\Dilve\Api\DilveApi;
use Inc\Dilve\Api\DilveApiDbLogManager;
use Inc\Dilve\Api\DilveApiDbManager;
use Inc\Dilve\Base\BaseController;

/**
 * @class DilveScanProductsFormController
 */
class DilveScanProductsFormController extends BaseController
{
    public $adminNotice = '';
    /**
     * register
     *
     * @return void
     */
    public function register()
    {
        //add_action('admin_init', [$this, 'handleFormSubmission']);
        $actions = [
            'hello_world',
            'scan_products',
        ];
        //add_action('wp_ajax_dilve_log_queue', [$this, 'ajaxHandleDilveLogQueue']);
        foreach ( $actions as $action ) {
            $camelCase = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $action ) ) );
            add_action( 'wp_ajax_dilve_' . $action, [ $this, 'ajaxHandle' . $camelCase ] );
        }

        add_action('admin_notices', [ $this, 'displayAdminNotice' ]);
    }

    /**
     * ajaxHandleHelloWorld
     *
     * @return void
     */
    public function ajaxHandleHelloWorld() {
        check_ajax_referer('dilve_scan_products_form', 'dilve_nonce');
        update_option('dilve_admin_notice', 'Hello world!');
        wp_send_json_success(['message' => 'Hello world!']);
    }

    /**
     * ajaxHandleScanProducts
     *
     * @return void
     */
    public function ajaxHandleScanProducts() {
        check_ajax_referer('dilve_scan_products_form', 'dilve_nonce');
        update_option('dilve_admin_notice', 'File Checked!');
        $dilveApi = new DilveApi;
        $dilveApiDbManager = new DilveApiDbManager;
        $dilveApiDbLogManager = new DilveApiDbLogManager;
        $batch_size = 10;
        $totalLines = $dilveApiDbManager->countAllProducts();
        $log_id = $dilveApiDbLogManager->insertLogData('logged', $totalLines);
        $response = $dilveApi->scanProducts($log_id);
        wp_send_json_success($response);
    }

    public function displayAdminNotice() {
        if ($this->adminNotice !== '') {
            echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . $this->adminNotice . '</p>';
            echo '</div>';
        }
    }
}
