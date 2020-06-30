<?php
/*
 * Forkor application front. This file prepares the environment of the application,
 * reads the external extension file, if any, and instantiates the application class.
 *
 * @package forkor
 */

// Forkor Application Preferences
define( 'APP_NAME',         'Forkor' );
define( 'VERSION',          '1.0' );
define( 'APP_ROOT',         __DIR__ );
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
