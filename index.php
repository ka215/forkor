<?php
/* */
ini_set( 'display_errors', 1 );
ini_set( 'log_errors', 'On' );
error_reporting( E_ALL );
/* */

// Define host hash
define( 'FORKOR_HOST_HASH', sha1( $_SERVER['HTTP_HOST'] ) );

// Loading if exists users custom functions file
$custom_functions = __DIR__ . '/functions.php';
if ( @file_exists( $custom_functions ) ) {
    require_once $custom_functions;
}

// Attach the classes autoloader via composer
require 'vendor/autoload.php';

// Set configuration file path (server path)
$config = __DIR__ . '/config.php';

$forkor = new \Forkor\Forkor( $config );
