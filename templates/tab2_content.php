<?php
    $dilve_admin_notice = get_option('dilve_admin_notice', '');
    if ( !empty( $dilve_admin_notice ) ) {
        echo '<div class="notice">' . $dilve_admin_notice . '</div>';
        delete_option('dilve_admin_notice');  // Clear the notice
    }
?>
<div class="wrap">
    <h1>Acciones</h1>
    <form method="post" action="#tab-1" id="dilveProcess">
        <?php wp_nonce_field('dilve_scan_products_form', 'dilve_nonce'); ?>
        <?php
            $buttons = [
                ['0. Hello World', 'primary', 'hello_world'],
                ['1. Scan Products', 'primary', 'scan_products'],
            ];

            array_map(function($button) {
                list($label, $type, $name) = $button;
                submit_button($label, $type, $name, false);
            }, $buttons);
        ?>
    </form>
    <div data-container="dilve" class="terminal"></div>
</div>