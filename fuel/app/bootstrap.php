<?php

// we don't want to use the massive Security::htmlentities() function
function e($string)
{
	return htmlentities($string);
}

if (function_exists('_'))
{
	function __($text)
	{
		$text = _($text);
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
		return $text;
	}

	function _ngettext($msgid1, $msgid2, $n)
	{
		return ngettext($msgid1, $msgid2, $n);
	}
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
		if($n !== 1)
			return __($msgid2);

		return __($msgid1);
	}
}

// Load in the Autoloader
require COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php';
class_alias('Fuel\\Core\\Autoloader', 'Autoloader');

// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';


Autoloader::add_classes(array(
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
	'Router' => APPPATH.'classes/extend/router.php',
	'View' => APPPATH.'classes/extend/view.php',
	'Input' => APPPATH.'classes/extend/input.php',
	'Uri' => APPPATH.'classes/extend/uri.php',
	'Validation' => APPPATH.'classes/extend/validation.php',
	'Cookie' => APPPATH.'classes/extend/cookie.php',
	'Fuel\Core\Image_Imagemagick' => APPPATH.'classes/extend/imagemagick.php',
	'ReCaptcha' => APPPATH.'classes/extend/recaptcha.php',
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


// Initialize the framework with the config file.
\Config::load('foolframe', 'foolframe');
Fuel::init('config.php');

Autoloader::add_classes(array(
	'Model\\Model_Base' => APPPATH.'classes/model/model_base.php',
	'Model\\Preferences' => APPPATH.'classes/model/preferences.php',
	'Model\\Notices' => APPPATH.'classes/model/notices.php',
	'Model\\Plugins' => APPPATH.'classes/model/plugins.php',
	'Model\\Theme' => APPPATH.'classes/model/theme.php',
	'Model\\Users' => APPPATH.'classes/model/users.php',
	'Model\\User' => APPPATH.'classes/model/user.php',
));

Autoloader::add_core_namespace('Model');

// load Inet class for decimal use: \Inet::ptod($ip)
Package::load('inet');
Autoloader::alias_to_namespace('Foolz\\Inet\\Inet');

// check if FoolFrame is installed and in case it's not, allow reaching install
if ( ! \Config::get('foolframe.install.installed'))
{
	\Module::load('install');
	\Fuel::load(APPPATH.'modules/install/bootstrap.php');
}
else
{
	// load each FoolFrame module, bootstrap and config
	foreach(\Config::get('foolframe.modules.installed') as $module)
	{
		\Module::load($module);
		\Config::load($module.'::'.$module, $module);
		// load the module routing
		$autoroutes = \Config::load($module.'::autoroutes', 'autoroutes');
		foreach($autoroutes as $key => $item)
		{
			\Router::add($key, $item);
		}
	}
	
	// run the bootstrap for each module
	foreach(\Config::get('foolframe.modules.installed') as $module)
	{
		\Profiler::mark('Start module '.$module.' bootstrap');
		\Profiler::mark_memory(false, 'Start module '.$module.' bootstrap');
		
		\Fuel::load(APPPATH.'modules/'.$module.'/bootstrap.php');
		
		\Profiler::mark('End module '.$module.' bootstrap');
		\Profiler::mark_memory(false, 'End module '.$module.' bootstrap');
	}
	
	$available_langs = \Config::get('foolframe.preferences.lang.available');
	$lang = \Cookie::get('language');

	if( ! $lang || ! array_key_exists($lang, $available_langs))
	{
		$lang = \Preferences::get('ff.lang.default');
	}

	$locale = $lang . '.utf8';
	putenv('LANG=' . $locale);
	putenv('LANGUAGE=' . $locale);
	if ($locale !== "tr_TR.utf8") // long standing PHP bug
	{
		setlocale(LC_ALL, $locale);
	}
	else // workaround to make turkish work
	{
		setlocale(LC_COLLATE, $locale);
		setlocale(LC_MONETARY, $locale);
		setlocale(LC_NUMERIC, $locale);
		setlocale(LC_TIME, $locale);
		setlocale(LC_MESSAGES, $locale);
		setlocale(LC_CTYPE, "sk_SK.utf8");
	}

	bindtextdomain($lang, DOCROOT . "assets/locale");
	bind_textdomain_codeset($lang, 'UTF-8');
	textdomain($lang);

	\Profiler::mark('Start plugins initialization');
	\Profiler::mark_memory(false, 'Start plugins initialization');
	
	\Plugins::initialize();
	
	\Profiler::mark('End plugins initialization');
	\Profiler::mark_memory(false, 'End plugins initialization');
}