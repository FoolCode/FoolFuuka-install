<?php

class Cookie extends Fuel\Core\Cookie
{
	public static function get($name = null, $default = null)
	{
		return \Input::cookie(\Config::get('foolframe.cookie_prefix').$name, $default);
	}

	public static function set($name, $value, $expiration = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		parent::set(\Config::get('foolframe.cookie_prefix').$name, $value, $expiration, $path, $domain, $secure, $http_only);
	}

	public static function delete($name, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		parent::delete(\Config::get('foolframe.cookie_prefix').$name, $path, $domain, $secure, $http_only);
	}
}