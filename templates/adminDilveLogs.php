<?php
use Inc\Dilve\Api\DilveLogListTable;
?>

<div class="wrap">
    <h1>Dilve LOGS</h1>
    <?php settings_errors(); ?>
    <!-- FILTERS -->
    <?php
        global $wpdb;
        $logTable = $wpdb->prefix . 'dilve_log';

        // Fetch distinct types
        $start_date_sql = "SELECT DISTINCT start_date FROM {$logTable}";
        $start_dates = $wpdb->get_col($start_date_sql);

        $end_date_sql = "SELECT DISTINCT end_date FROM {$logTable}";
        $end_dates = $wpdb->get_col($end_date_sql);

        $status_sql = "SELECT DISTINCT status FROM {$logTable}";
        $statuses = $wpdb->get_col($status_sql);

        $scanned_items_sql = "SELECT DISTINCT scanned_items FROM {$logTable}";
        $scanned_items = $wpdb->get_col($scanned_items_sql);

        $processed_items_sql = "SELECT DISTINCT processed_items FROM {$logTable}";
        $processed_items = $wpdb->get_col($processed_items_sql);
        ?>
    <form method="post">
        <select name="filter_start_date"></select>
        <select name="filter_end_date"></select>
        <select name="filter_status"></select>
        <select name="filter_scanned_items"></select>
        <select name="filter_processed_items"></select>
    </form>
    <!-- Page display -->
    <?php
        $wp_list_table = new DilveLogListTable;
        $wp_list_table->prepare_items();
        // Render the table
        echo "<form method='post' name='dilve_log_search' action='".$_SERVER['PHP_SELF']."?page=dilve_log'>";
        $wp_list_table->search_box("Dilve Log Search", "search_dilve_log");
        echo "</form>";
        $wp_list_table->display();
    ?>

</div>