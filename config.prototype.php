<?php
/*
 * Forkor Configurations
 */
if ( ! defined( 'FORKOR_HOST_HASH' ) || FORKOR_HOST_HASH !== sha1( $_SERVER['HTTP_HOST'] ) ||
     in_array( basename( __FILE__ ), [ basename( $_SERVER['SCRIPT_NAME'] ), basename( $_SERVER['REQUEST_URI'] ) ], true ) ) {
    // Invalid to direct access
    header( 'HTTP/1.0 404 Not Found' );
    exit;
}

// Forkor Common Info.
define( 'APP_NAME',   'Forkor' );
define( 'VERSION',    '1.0' );
define( 'APP_ROOT',   __DIR__ );
define( 'DEBUG_MODE', false );

// Database
define( 'DB_DRIVER',  'mysql' );
define( 'DB_NAME',    '#%db_name%#' );
define( 'DB_USER',    '#%db_user%#' );
define( 'DB_PASS',    '#%db_pass%#' );
define( 'DB_HOST',    '#%db_host%#' );
define( 'DB_CHARSET', '#%db_charset%#' );

// Register Path
define( 'REGISTER_PATH', '#%register_path%#' );
$ips_allowed_to_register = [
    // IP addresses to allow; all allowed if empty
    // c.f. $_SERVER['SERVER_ADDR'], '123.123.1.23', ...
    '#%register_allowed_ips%#'
];
define( 'REGISTER_ALLOWED_IPS', $ips_allowed_to_register );

// Analyze Path
define( 'ANALYZE_PATH',  '#%analyze_path%#' );
$ips_allowed_to_analyze = [
    // IP addresses to allow; all allowed if empty
    // c.f. $_SERVER['SERVER_ADDR'], '123.123.1.23', ...
    '#%analyze_allowed_ips%#'
];
define( 'ANALYZE_ALLOWED_IPS', $ips_allowed_to_analyze );

// Forkor Index: Not Found if false
define( 'SHOW_INDEX', '#%show_index%#' );

// Shortener URL Generate Settings
