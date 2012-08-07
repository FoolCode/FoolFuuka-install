<?php

class Uri extends \Fuel\Core\Uri
{
	public static function create($uri = null, $variables = array(), $get_variables = array(), $secure = null)
	{
		if (is_null($uri))
		{
			return parent::create($uri, $variables, $get_variables, $secure);
		}
		
		if(is_string($uri))
		{
			$uri = explode('/', $uri);
		}

		if(strpos(current($uri), '@') !== FALSE)
		{
			array_shift($uri);
		}

		if(is_array($uri))
		{
			$uri = implode('/', $uri);
		}

		return parent::create($uri, $variables, $get_variables, $secure);
	}
}
