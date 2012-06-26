<?php

// Load in the Autoloader
require COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php';
class_alias('Fuel\\Core\\Autoloader', 'Autoloader');

// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';


Autoloader::add_classes(array(
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
	'View' => APPPATH.'classes/extend/view.php',
	'Input' => APPPATH.'classes/extend/input.php',
	'Uri' => APPPATH.'classes/extend/uri.php',
));

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

Fuel::load(APPPATH.'config/constants.php');

function __($string)
{
	return $string;
}

// Initialize the framework with the config file.
Fuel::init('config.php');
//Autoloader::alias_to_namespace('Fuel\\Core\\URI');

Autoloader::alias_to_namespace('Model\\Inet');
Autoloader::alias_to_namespace('Model\\Preferences');
Autoloader::alias_to_namespace('Model\\Notices');
Autoloader::alias_to_namespace('Model\\Plugins');
Autoloader::alias_to_namespace('Model\\Theme');
Autoloader::alias_to_namespace('Model\\Radix');