<?php

class Cookie // this is an abomination, why can't I extend this without getting the private variable error?
{

	public static function get($name = null, $default = null)
	{
		return \Fuel\Core\Cookie::get(\Config::get('foolframe.cookie_prefix').$name, $default);
	}

	public static function set($name, $value, $expiration = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		return \Fuel\Core\Cookie::set(\Config::get('foolframe.cookie_prefix').$name, $value, $expiration, $path, $domain, $secure, $http_only);
	}

	public static function delete($name, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		return \Fuel\Core\Cookie::delete(\Config::get('foolframe.cookie_prefix').$name, $path, $domain, $secure, $http_only);
	}
}