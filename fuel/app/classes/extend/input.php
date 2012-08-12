<?php

class Input extends Fuel\Core\Input
{

	public static function ip_decimal()
	{
		return Inet::ptod(self::ip());
	}

}