<?php


class Router extends \Fuel\Core\Router
{
	/**
	 * Reformats a lowercase string to a class name by splitting on underscores and capitalizing
	 *
	 * @param  string  $class_name  The name of the class, lowercase and with words separated by underscore
	 *
	 * @return  string
	 */
	public static function lowercaseToClassName($class_name)
	{
		$pieces = explode('_', $class_name);

		$result = '';
		foreach ($pieces as $piece)
		{
			$result .= ucfirst($piece);
		}

		return $result;
	}

	protected static function parse_match($match)
	{
		$namespace = '';
		$segments = $match->segments;
		$module = false;

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

				array_unshift($method_params, array_pop($temp_segments));

				foreach (array_slice($temp_segments, 2) as $segment)
				{
					$temp_namespace .= '\\'.static::lowercaseToClassName($segment);
				}

				$hook = \Foolz\Plugin\Hook::forge('Fuel\Core\Router.parse_match.intercept')
					->setParams([
						'controller' => $temp_namespace,
						'action' => $method_params[0],
						'method_params' => array_slice($method_params, 1)
					])
					->execute();

				// some plugin was listening to the route
				if ($hook->get(null) !== null)
				{
					$match->controller = $hook->getParam('controller');
					$match->action = $hook->getParam('action');
					$match->method_params = $hook->getParam('method_params');
					return $match;
				}


				if (class_exists($temp_namespace))
				{
					$match->controller = $temp_namespace;
					$match->action = $method_params[0];
					$match->method_params = array_slice($method_params, 1);
					return $match;
				}
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