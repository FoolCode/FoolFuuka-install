<?php


class Router extends \Fuel\Core\Router
{
	
	protected static function parse_match($match)
	{
		$namespace = '';
		$segments = $match->segments;
		$module = false;
		
		if ($segments[0] === 'plugin')
		{
			$namespace = 
				ucfirst(\Plugins::get_module_name_by_identifier($segments[1])).'\\Plugins\\'.\Inflector::words_to_upper($segments[2]).'\\';
		}
		
		if ($segments[0] === 'theme')
		{
			$namespace = 
				ucfirst(\Plugins::get_module_name_by_identifier($segments[1])).'\\Theme\\'.\Inflector::words_to_upper($segments[2]).'\\';
		}

		// First port of call: request for a module?
		if (\Module::exists($segments[0]))
		{
			// make the module known to the autoloader
			\Module::load($segments[0]);
			$match->module = array_shift($segments);
			$namespace .= ucfirst($match->module).'\\';
			$module = $match->module;
		}

		if ($info = static::parse_segments($segments, $namespace, $module))
		{
			$match->controller = $info['controller'];
			$match->action = $info['action'];
			$match->method_params = $info['method_params'];
			return $match;
		}
		else
		{
			return null;
		}
	}
	
}