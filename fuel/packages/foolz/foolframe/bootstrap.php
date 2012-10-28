<?php

\Module::load('foolz/foolframe', PKGPATH.'foolz/foolframe/');

// Foolz packages
require PKGPATH.'foolz/bootstrap.php';

// bootstrap doctrine
require PKGPATH.'doctrine/Doctrine/Common/ClassLoader.php';
$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', PKGPATH.'doctrine');
$classLoader->register();

Autoloader::add_classes([
	'Foolz\Foolframe\Model\DoctrineConnection' => __DIR__.'/classes/Foolz/Foolframe/Model/DoctrineConnection.php',
	'Foolz\Foolframe\Model\DC' => __DIR__.'/classes/Foolz/Foolframe/Model/DoctrineConnection.php',
	'Foolz\Foolframe\Model\Preferences' => __DIR__.'/classes/Foolz/Foolframe/Model/Preferences.php',
	'Foolz\Foolframe\Model\Notices' => __DIR__.'/classes/Foolz/Foolframe/Model/Notices.php',
	'Foolz\Foolframe\Model\Plugins' => __DIR__.'/classes/Foolz/Foolframe/Model/Plugins.php',
	'Foolz\Foolframe\Model\Theme' => __DIR__.'/classes/Foolz/Foolframe/Model/Theme.php',
	'Foolz\Foolframe\Model\Users' => __DIR__.'/classes/Foolz/Foolframe/Model/Users.php',
	'Foolz\Foolframe\Model\User' => __DIR__.'/classes/Foolz/Foolframe/Model/User.php',
	'Foolz\Foolframe\Model\Schema' => __DIR__.'/classes/Foolz/Foolframe/Model/Schema.php',
	'Foolz\Foolframe\Model\SchemaManager' => __DIR__.'/classes/Foolz/Foolframe/Model/SchemaManager.php',


	'Foolz\Foolframe\Controller\Common' => __DIR__.'/classes/Foolz/Foolframe/Controller/Common.php',
	'Foolz\Foolframe\Controller\Admin' => __DIR__.'/classes/Foolz/Foolframe/Controller/Admin.php',
	'Foolz\Foolframe\Controller\Admin\Account' => __DIR__.'/classes/Foolz/Foolframe/Controller/Admin/Account.php',
	'Foolz\Foolframe\Controller\Admin\Plugins' => __DIR__.'/classes/Foolz/Foolframe/Controller/Admin/Plugins.php',
	'Foolz\Foolframe\Controller\Admin\Preferences' => __DIR__.'/classes/Foolz/Foolframe/Controller/Admin/Preferences.php',
	'Foolz\Foolframe\Controller\Admin\System' => __DIR__.'/classes/Foolz/Foolframe/Controller/Admin/System.php',
	'Foolz\Foolframe\Controller\Admin\Users' => __DIR__.'/classes/Foolz/Foolframe/Controller/Admin/Users.php',
]);

Autoloader::add_core_namespace('Foolz\Foolframe\Model');

// load Inet class for decimal use: \Inet::ptod($ip)
Autoloader::alias_to_namespace('Foolz\Inet\Inet');

// check if FoolFrame is installed and in case it's not, allow reaching install
if ( ! \Foolz\Config\Config::get('foolz/foolframe', 'package', 'install.installed'))
{
	\Module::load('install', PKGPATH.'foolz/install/');
	require PKGPATH.'foolz/install/bootstrap.php';
}
else
{
	// load each FoolFrame module, bootstrap and config
	foreach(\Foolz\Config\Config::get('foolz/foolframe', 'package', 'modules.installed') as $module)
	{
		// foolframe is already loaded
		if ($module !== 'foolz/foolframe')
		{
			\Module::load($module, PKGPATH.$module.'/');
		}

		// load the module routing
		foreach(\Foolz\Config\Config::get($module, 'autoroutes') as $key => $item)
		{
			\Router::add($key, $item);
		}
	}

	// run the bootstrap for each module
	foreach(\Config::get('foolframe.modules.installed') as $module)
	{
		if ($module !== 'foolz/foolframe')
		{
			require PKGPATH.$module.'/bootstrap.php';
		}
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

	\Plugins::initialize();
}