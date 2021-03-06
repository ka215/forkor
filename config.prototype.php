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

// Database Settings
define( 'DB_DRIVER',  '#%dsn_prefix%#' );
define( 'DB_NAME',    '#%db_name%#' );
define( 'DB_USER',    '#%db_user%#' );
define( 'DB_PASS',    '#%db_pass%#' );
define( 'DB_HOST',    '#%db_host%#' );
define( 'DB_CHARSET', '#%db_charset%#' );

// Register Path
define( 'REGISTER_PATH', '#%register_path%#' );
$ips_allowed_to_register = [
    // IP addresses to allow; all allowed if empty
    // c.f. $_SERVER['SERVER_ADDR'], '123.123.4.56', ...
    '#%register_allowed_ips%#'
];
define( 'REGISTER_ALLOWED_IPS', $ips_allowed_to_register );

// Analyze Path
define( 'ANALYZE_PATH',  '#%analyze_path%#' );
$ips_allowed_to_analyze = [
    // IP addresses to allow; all allowed if empty
    // c.f. $_SERVER['SERVER_ADDR'], '123.123.4.56', ...
    '#%analyze_allowed_ips%#'
];
define( 'ANALYZE_ALLOWED_IPS', $ips_allowed_to_analyze );

// Forkor Index: Not Found if false
define( 'SHOW_INDEX', '#%show_index%#' );

// Switching Debug Mode: Defaults to false as OFF
define( 'DEBUG_MODE', false );

// Other Advanced Settings
// Not yet