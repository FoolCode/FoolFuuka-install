<?php

namespace Model;

class Model_Base extends \Model
{

	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$class = strtolower(get_class($this));

		$parameters = array_merge($parameters + array(&$this));
		
		$before = \Plugins::run_hook($class.'.'.$name.'.call.before', $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = \Plugins::run_hook($class.'.'.$name.'.call.replace', $parameters, array($parameters));

		if ($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters))
			{
				case 0:
					$return = $this->{'p_'.$name}();
					break;
				case 1:
					$return = $this->{'p_'.$name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_'.$name), $parameters);
					break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = \Plugins::run_hook($class.'.'.$name.'.call.after', $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	public static function __callStatic($name, $parameters)
	{
		$class = str_replace('\\', '/', strtolower(get_called_class()));

		$before = \Plugins::run_hook($class.'.'.$name.'.call.before', $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = \Plugins::run_hook($class.'.'.$name.'.call.replace', $parameters, array($parameters));

		if ($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			$pname = 'p_'.$name;
			switch (count($parameters))
			{
				case 0:
					$return = static::$pname();
					break;
				case 1:
					$return = static::$pname($parameters[0]);
					break;
				case 2:
					$return = static::$pname($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = static::$pname($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = static::$pname($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = static::$pname($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(get_called_class().'::'.$pname, $parameters);
					break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = \Plugins::run_hook($class.'.'.$name.'.call.after', $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}

}