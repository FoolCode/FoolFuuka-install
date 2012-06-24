<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$route['default_controller'] = "foolfuuka/chan";

// if we're using special subdomains or if we're under system stuff
if(
	!defined('FOOL_SUBDOMAINS_ENABLED')
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_SYSTEM) !== FALSE
)
{
	
	$route['search'] = "foolfuuka/chan/search";
	$route['search/(.*?)'] = "foolfuuka/chan/search/$1";
	
	$route['admin/boards'] = "foolfuuka/boards/manage";
	$route['admin/boards/(.*?)'] = "foolfuuka/boards/$1";
	$route['admin/posts'] = "foolfuuka/posts";
	$route['admin/posts/(.*?)'] = "foolfuuka/posts/$1";
}

// if we're using special subdomains or if we're under boards/archives:
if(
	!defined('FOOL_SUBDOMAINS_ENABLED')
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_BOARD) !== FALSE
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_ARCHIVE) !== FALSE
)
{
	if(!defined('FOOL_SUBDOMAINS_ENABLED'))
	{
		$protected_radixes = implode('|', unserialize(FOOL_PROTECTED_RADIXES));
		$route['(?!(' . $protected_radixes . '))(\w+)/(.*?).xml'] = "foolfuuka/chan/$2/feeds/$3";
		$route['(?!(' . $protected_radixes . '))(\w+)/(.*?)'] = "foolfuuka/chan/$2/$3";
	}
	else
	{
		$route['(\w+)/(.*?).xml'] = "foolfuuka/chan/$1/feeds/$2";
		$route['(\w+)/(.*?)'] = "foolfuuka/chan/$1/$2";
	}
	$route['(\w+)'] = "foolfuuka/chan/$1/page";
}

$route['404_override'] = 'foolfuuka/chan/show_404';
