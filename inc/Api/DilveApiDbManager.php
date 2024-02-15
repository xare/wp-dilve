<?php

namespace Inc\Dilve\Api;

class DilveApiDbManager {
    const DILVE_LOG_TABLE = 'dilve_log';
    const DILVE_LINES_TABLE = 'dilve_lines';
    const DILVE_LOGGER_TABLE = 'dilve_logger';

    static $dilveLogKeys = [
		'start_date', // date
		'end_date', // date
		'status', // string waiting | enqueued | processed
		'scanned_items', // int number of lines
        'processed_items', // int number of lines
	];

    static $dilveLinesKeys = [
        'log_id', // int relation oneToMany with dilve_log
        'isbn',    // string
        'path',    // string
        'url_origin', // string
        'url_target', // string
        'date',    // date
        'isError', // boolean
        'error',   // string
        'attempts', // int
    ];
}