<?php

namespace Model;


class Preferences extends \Model
{

	private static $_preferences = array();


	public static function _init()
	{
		static::load_settings();
	}


	public static function load_settings($reload = false)
	{
		\Profiler::mark('Preferences::load_settings Start');
		if ($reload === true)
		{
			\Cache::delete('model.preferences.settings');
		}

		try
		{
			static::$_preferences = \Cache::get('model.preferences.settings');
		}
		catch (\CacheNotFoundException $e)
		{
			$preferences = \DB::select()->from('preferences')->as_assoc()->execute();

			foreach($preferences as $pref)
			{
				// fix the PHP issue where . is changed to _ in the $_POST array
				static::$_preferences[$pref['name']] = $pref['value'];
			}

			\Cache::set('model.preferences.settings', static::$_preferences, 3600);
		}

		\Profiler::mark_memory(static::$_preferences, 'Preferences static::$_preferences');
		\Profiler::mark('Preferences::load_settings End');
		return static::$_preferences;
	}


	public static function save_settings($data)
	{
		if (is_array($data) && count($data) > 0)
		{
			foreach ($data as $setting => $value)
			{
				// fix the PHP issue where . is changed to _ in the $_POST array
				$setting = substr($setting['name'], 0, 2).'.'.substr($setting['name'], 3);

				// if value contains array, serialize it
				if (is_array($value))
				{
					$value = serialize(array_filter($value, array($this, '_filter_value')));
				}

				$validate = DB::select('*')->from('preferences')->where('name', $setting)->execute();
				if (count($validate) === 1)
				{
					DB::update('preferences')->value($setting, $value)->where('name', $setting)->execute();
				}
				else
				{
					DB::insert('preferences')->set(array($setting, $value))->execute();
				}
			}

			return static::load_settings(true);
		}

		return false;
	}


	public static function get($setting, $fallback = null)
	{
		if (isset(self::$_preferences[$setting]))
		{
			return self::$_preferences[$setting];
		}

		if ($fallback != null)
		{
			return $fallback;
		}

		$const = strtoupper(substr($setting,strpos($setting,'.') + 1));
		if (defined('FOOL_'.$const))
		{
			return constant('FOOL_'.$const);
		}

		return null;
	}


	public static function set($setting, $value)
	{
		// if array, serialize value
		if (is_array($value))
		{
			$value = serialize($value);
		}

		$validate = DB::select('*')->from('preferences')->where('name', $setting)->execute();
		if (count($validate) === 1)
		{
			DB::update('preferences')->value($setting, $value)->where('name', $setting)->execute();
		}
		else
		{
			DB::insert('preferences')->set(array($setting, $value))->execute();
		}

		return static::load_settings(true);
	}


	/**
	 * Save in the preferences table the name/value pairs
	 *
	 * @param array $data name => value
	 */
	public static function submit($data)
	{
		foreach ($data as $name => $value)
		{
			// in case it's an array of values from name="thename[]"
			if(is_array($value))
			{
				// remove also empty values with array_filter
				// but we want to keep 0s
				$value = serialize(array_filter($value, function($var){
					if($var === 0)
						return TRUE;
					return $var;
				}));
			}

			$count = \DB::select(\DB::expr('COUNT(*) as count'))
				->from('preferences')->where('name', $name)->execute()->current();

			// we can update only if it already exists
			if ($count['count'])
			{
				\DB::update('preferences')->value('value', $value)->where('name', $name)->execute();
			}
			else
			{
				\DB::insert('preferences')->set(array('name' => $name, 'value' => $value))->execute();
			}
		}

		// reload those preferences
		static::load_settings(true);
	}


	/**
	 * A lazy way to submit the preference panel input, saves some code in controller
	 *
	 * This function runs the custom validation function that uses the $form array
	 * to first run the original CodeIgniter validation and then the anonymous
	 * functions included in the $form array. It sets a proper notice for the
	 * admin interface on conclusion.
	 *
	 * @param array $form
	 */
	public static function submit_auto($form)
	{
		if (\Input::post())
		{
			$post = array();
			foreach (\Input::post() as $key => $item)
			{
				$post[substr($key, 0, 2).'.'.substr($key, 3)] = $item;
			}

			$result = \Validation::form_validate($form, $post);
			if (isset($result['error']))
			{
				\Notices::set('warning', $result['error']);
			}
			else
			{
				if (isset($result['warning']))
				{
					\Notices::set('warning', $result['warning']);
				}

				\Notices::set('success', __('Preferences updated.'));
				static::submit($result['success']);
			}
		}
	}


	private function _filter_value($value)
	{
		if ($value === 0)
		{
			return true;
		}

		return $value;
	}

}

/* end of file preferences.php */