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
	'Validation' => APPPATH.'classes/extend/validation.php',
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

/*
if (function_exists('_'))
{*/
	function __($text)
	{
		$text = _($text);
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
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
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
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

\Config::load('foolframe', 'foolframe');

Autoloader::add_classes(array(
	'Model\\Model_Base' => APPPATH.'classes/model/model_base.php',
	'Model\\Inet' => APPPATH.'classes/model/inet.php',
	'Model\\Preferences' => APPPATH.'classes/model/preferences.php',
	'Model\\Notices' => APPPATH.'classes/model/notices.php',
	'Model\\Plugins' => APPPATH.'classes/model/plugins.php',
	'Model\\Theme' => APPPATH.'classes/model/theme.php',
	'Model\\Users' => APPPATH.'classes/model/users.php',
	'Model\\User' => APPPATH.'classes/model/user.php',
));

Autoloader::add_core_namespace('Model');


// load each FoolFrame module, bootstrap and config
foreach(\Config::get('foolframe.modules.installed') as $module)
{
	\Module::load($module);
	\Config::load($module.'::'.$module, $module);
	\Fuel::load(APPPATH.'modules/'.$module.'/bootstrap.php');
}