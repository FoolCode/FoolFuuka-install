<?php

class Input extends Fuel\Core\Input
{

	public static function ip_decimal($default = '0.0.0.0')
	{
		return \Library\Inet::ptod(static::server('REMOTE_ADDR', $default));
	}

}