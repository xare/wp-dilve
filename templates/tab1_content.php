<form method="post" action="options.php">
    <?php
        settings_fields( 'dilve_settings' );
        do_settings_sections( 'dilve' );
        submit_button();
    ?>
</form>