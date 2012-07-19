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
	'Validation' => APPPATH.'classes/extend/validation.php'
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
/*
if (function_exists('_'))
{*/
	function __($text)
	{
		$text = _($text);
		$text = str_replace('{{FOOL_NAME}}', FOOL_NAME, $text);
		return $text;
	}

	function _ngettext($msgid1, $msgid2, $n)
	{
		return ngettext($msgid1, $msgid2, $n);
	}/*
}
else
{
	function __($text)
	{
		$text = str_replace('{{FOOL_NAME}}', FOOL_NAME, $text);
		return $text;
	}

	function _ngettext($msgid1, $msgid2, $n)
	{
		if($n != 1)
			return __($msgid2);

		return __($msgid1);
	}
}/*

function __($string)
{
	return $string;
}

function _ngettext($string)
{
	return $string;
}*/

// Initialize the framework with the config file.
Fuel::init('config.php');

Autoloader::add_class('Model\\Model_Base', APPPATH.'classes/model/model_base.php');
Autoloader::alias_to_namespace('Model\\Model_Base');
Autoloader::alias_to_namespace('Model\\Inet');
Autoloader::alias_to_namespace('Model\\Preferences');
Autoloader::alias_to_namespace('Model\\Notices');
Autoloader::alias_to_namespace('Model\\Plugins');
Autoloader::alias_to_namespace('Model\\Theme');
Autoloader::alias_to_namespace('Model\\Radix');
Autoloader::alias_to_namespace('Model\\Board');
Autoloader::alias_to_namespace('Model\\Comment');
Autoloader::alias_to_namespace('Model\\Media');
Autoloader::alias_to_namespace('Model\\Users');