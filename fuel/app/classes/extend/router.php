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
				ucfirst(\Plugins::get_module_name_by_identifier($segments[1])).
					'\\Plugins\\'.\Inflector::words_to_upper($segments[2]).'\\';
		}

		if ($segments[0] === 'theme')
		{
			$namespace =
				ucfirst(\Plugins::get_module_name_by_identifier($segments[1])).
					'\\Themes\\'.\Inflector::words_to_upper($segments[2]).'\\';
		}

		if (count($segments) > 3)
		{
			$i = 0;
			$temp_segments = $segments;
			$method_params = [];
			$namespace = ucfirst($segments[0]).'\\'.ucfirst($segments[1]).'\\Controller';

			while (count($temp_segments) > 2)
			{
				$i++;
				$temp_namespace = $namespace;
				foreach (array_slice($temp_segments, 2) as $segment)
				{
					$temp_namespace .= '\\'.ucfirst($segment);
				}

				if (class_exists($temp_namespace))
				{
					$match->controller = $temp_namespace;
					$match->action = $method_params[0];
					$match->method_params = array_slice($method_params, 1);
					return $match;
				}

				array_unshift($method_params, array_pop($temp_segments));
			}
		}

		// First port of call: request for a module?
		if (\Module::exists($segments[0]))
		{
			// make the module known to the autoloader
			\Module::load($segments[0]);
			$match->module = array_shift($segments);
			$namespace .= ucfirst($match->module).'\\Controller\\';
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