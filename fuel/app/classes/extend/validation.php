<?php

class Validation extends \Fuel\Core\Validation
{
	/**
	 * Checks the form for and returns either a compiled array of values or
	 * the error
	 *
	 * @param $form array
	 * @param $alternate array name/value pairs to use instead of the POST array
	 */
	public static function form_validate($form, $alternate = null)
	{
		// this gets a bit complex because we want to show all errors at the same
		// time, which means we have to run both core validation and custom, then
		// merge the result.

		$input = !is_null($alternate) ? $alternate : \Input::post();

		foreach ($form as $name => $item)
		{
			if(isset($item['sub']))
			{
				// flatten the form
				$form = array_merge($form, $item['sub']);
			}

			if(isset($item['sub_inverse']))
			{
				// flatten the form
				$form = array_merge($form, $item['sub_inverse']);
			}

			if(isset($item['checkboxes']))
			{
				// flatten the form
				$form_temp = array();

				foreach($item['checkboxes'] as $checkbox)
				{
					$form_temp[$name . '[' . $checkbox['array_key'] . ']'] = $checkbox;
				}

				$form = array_merge($form, $form_temp);
			}
		}

		$val = \Validation::forge();

		foreach ($form as $name => $item)
		{
			if (isset($item['validation']))
			{
				// set the rules and add [] to the name if array
				$val->add_field($name . ((isset($item['array']) && $item['array'])?'[]':''), $item['label'], $item['validation']);
			}
		}

		// we need to run both validation and closures
		$val->run($input);
		$fuel_validation_errors = $val->error();

		$validation_func = array();
		// we run this after form_validation in case form_validation edited the POST data
		foreach ($form as $name => $item)
		{
			// the "required" MUST be handled with the standard form_validation
			// or we'll never get in here
			if (isset($item['validation_func']) && isset($input[$name]))
			{
				// contains TRUE for success and in array with ['error'] in case
				$validation_func[$name] = $item['validation_func']($input, $form);

				// critical errors don't allow the continuation of the validation.
				// this allows less checks for functions that come after the critical ones.
				// criticals are usually the IDs in the hidden fields.
				if (isset($validation_func[$name]['critical']) && $validation_func[$name]['critical'] == TRUE)
				{
					break;
				}

				if (isset($validation_func[$name]['push']) && is_array($validation_func[$name]['push'] == TRUE))
				{
					// overwrite the $input array
					foreach ($validation_func[$name]['push'] as $n => $i)
					{
						$input[$n] = $i;
					}
				}
			}
		}

		// filter results, since the closures return ['success'] = TRUE on success
		$validation_func_errors = array();
		$validation_func_warnings = array();
		foreach ($validation_func as $item)
		{
			// we want only the errors
			if (isset($item['success']))
			{
				continue;
			}

			if (isset($item['warning']))
			{
				// we want only the human readable error
				$validation_func_warnings[] = $item['warning'];
			}

			if (isset($item['error']))
			{
				// we want only the human readable error
				$validation_func_errors[] = $item['error'];
			}
		}

		if (count($fuel_validation_errors) > 0 || count($validation_func_errors) > 0)
		{
			$errors = array_merge($fuel_validation_errors, $validation_func_errors);
			return array('error' => implode(' ', $errors));
		}
		else
		{
			// get rid of all the uninteresting inputs and simplify
			$result = array();

			foreach ($form as $name => $item)
			{
				// not interested in data that is not related to database
				if ((!isset($item['database']) || $item['database'] !== TRUE) &&
					(!isset($item['preferences']) || $item['preferences'] === FALSE))
				{
					continue;
				}

				// create a version without array index
				if(isset($item['array_key']) && substr($name, -1, 1) == ']' && substr($name, -2, 1) != '[')
				{
					$pos = strrpos($name, '[');
					$name_no_index = substr($name, 0, $pos);
				}

				if ($item['type'] == 'checkbox' && isset($input[$name]))
				{
					if ($input[$name] == 1)
					{
						// support for multidimensional checkbox groups
						if(isset($item['array_key']))
						{
							$result[$name_no_index][$item['array_key']] = 1;
						}
						else
						{
							$result[$name] = 1;
						}
					}
					else
					{
						if(isset($item['array_key']))
						{
							$result[$name_no_index][$item['array_key']] = 0;
						}
						else
						{
							$result[$name] = 0;
						}
					}
				}
				else
				{
					if (isset($input[$name]) && $input[$name] !== FALSE)
					{
						$result[$name] = $input[$name];
					}
				}
			}

			if (count($validation_func_warnings) > 0)
			{
				return array('success' => $result, 'warning' => implode(' ', $validation_func_warnings));
			}

			// returning a form with the new values
			return array('success' => $result);
		}
	}
}
