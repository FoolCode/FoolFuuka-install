<?php

class Input extends Fuel\Core\Input
{

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param    string  The index key
	 * @param    mixed   The default value
	 * @return   string|array
	 */
	public static function cookie($index = null, $default = null)
	{
		$index = \Foolz\Config\Config::get('foolz/foolframe', 'config', 'config.cookie_prefix').$index;
		return (func_num_args() === 0) ? $_COOKIE : \Arr::get($_COOKIE, $index, $default);
	}

	public static function ip_decimal()
	{
		return \Foolz\Inet\Inet::ptod(static::ip());
	}

}