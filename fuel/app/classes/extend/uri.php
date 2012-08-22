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
	
	
	public static function uri_to_assoc($uri, $index = 0, $allowed = null)
	{
		if (is_string($uri))
		{
			$uri = explode('/', $uri);
		}
		
		for ($i = 0; $i < $index; $i++)
		{
			array_shift($uri);
		}
		
		// reorder the keys
		$uri = array_values($uri);
		$result = array();
		
		foreach ($uri as $key => $item)
		{
			if ($key % 2)
			{
				$result[$temp] = $item;
			}
			else
			{
				$temp = $item;
			}
		}
		
		if ($allowed !== null)
		{
			foreach ($allowed as $item)
			{
				$filtered[$item] = isset($result[$item]) ? $result[$item] : null;
			}
			
			$result = $filtered;
		}
		
		return $result;
	}
}
