<?php

class Input extends Fuel\Core\Input
{

	public static function ip_decimal()
	{
		return Inet::ptod(self::ip());
	}

	/**
	 * Enable the post array to work with associative arrays
	 * so you can use \Input::post(value[subvalue])
	 *
	 * @param string $index
	 * @param mixed $default
	 */
	public static function post($index = null, $default = false)
	{
		if(substr($index, -1, 1) == ']' && substr($index, -2, 1) != '[')
		{
			// we have an associative array
			$pos = strrpos($index, '[');
			$key = substr($index, $pos+1, -1);
			$index = substr($index, 0, $pos);
			$post = parent::post($index, $default);
			if(!isset($post[$key]))
				return false;
			return $post[$key];
		}

		return parent::post($index, $default);
	}

}