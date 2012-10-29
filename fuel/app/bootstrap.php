<?php

// load custom functions
require __DIR__.'/functions.php';

// Load in the Autoloader
require COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php';
class_alias('Fuel\\Core\\Autoloader', 'Autoloader');

// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';

Autoloader::add_classes([
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
	'Router' => APPPATH.'classes/extend/router.php',
	'Input' => APPPATH.'classes/extend/input.php',
	'Uri' => APPPATH.'classes/extend/uri.php',
	'Validation' => APPPATH.'classes/extend/validation.php',
	'Cookie' => APPPATH.'classes/extend/cookie.php',
	'Session_Db' => APPPATH.'classes/extend/session_db.php',
	'ReCaptcha' => APPPATH.'classes/extend/recaptcha.php',

	'Foolz\\Config\\Config' => PKGPATH.'foolz/config/classes/Foolz/Config/Config.php'
]);

// Register the autoloader
Autoloader::register();

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGE
 * Fuel::PRODUCTION
 */
Fuel::$env = (isset($_SERVER['FUEL_ENV']) ? $_SERVER['FUEL_ENV'] : Fuel::DEVELOPMENT);

// Initialize the framework with the config file.
Fuel::init('config.php');

// let's run FoolFrame and fuck off FuelPHP
require PKGPATH.'foolz/foolframe/bootstrap.php';