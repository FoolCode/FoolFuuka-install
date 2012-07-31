<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

// this plugin works with indexes that don't exist in CLI
if (PHP_SAPI === 'cli')
{
	return false;
}

Autoloader::add_classes(array(
	'\\Foolframe\\Plugins\\Ssl_Tools\\Ssl_Tools' => __DIR__.'/classes/Ssl_Tools.php',
	'\\Foolframe\\Plugins\\Ssl_Tools\\Ssl_Tools_Controller' => __DIR__.'/classes/Ssl_Tools_Controller.php'
));

// don't add the admin panels if the user is not an admin
if (\Auth::has_access('maccess.admin'))
{
	\Plugins::register_controller_method(
		'admin/plugins/ssl_tools', '\\Foolframe\\Plugins\\Ssl_Tools\\Ssl_Tools_Controller::manage'
	);

	\Plugins::register_admin_sidebar_element('plugins', array(
		"content" => array("ssl_tools" => array("level" => "admin", "name" => __("SSL Tools"), "icon" => 'icon-lock'))
	));
}

// we can just run base checks now
\Foolframe\Plugins\Ssl_Tools\Ssl_Tools::check();

\Plugins::register_hook('ff.themes.generic_top_nav_buttons', '\\Foolframe\\Plugins\\Ssl_Tools\\Ssl_Tools::nav_top', 4);

\Plugins::register_hook('ff.themes.generic_bottom_nav_buttons', '\\Foolframe\\Plugins\\Ssl_Tools\\Ssl_Tools::nav_bottom', 4);