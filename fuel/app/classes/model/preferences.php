<?php

namespace Model;


class Preferences extends \Model
{

	private static $_preferences = array();
	private static $_module_identifiers = array();


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

		// we need to know the identifiers of the modules, like ff => foolfuuka, fu => foolfuuka, fs => foolslide
		$modules = \Config::get('foolframe.modules.installed');
		$modules[] = 'foolframe';

		foreach ($modules as $module)
		{
			static::$_module_identifiers[\Config::get($module.'.main.identifier')] = $module;
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

		$segments = explode('.', $setting);
		$identifier = array_shift($segments);
		$query = implode('.', $segments);

		return \Config::get(static::$_module_identifiers[$identifier].'.preferences.'.$query);
	}


	public static function set($setting, $value, $reload = true)
	{
		// if array, serialize value
		if (is_array($value))
		{
			$value = serialize($value);
		}

		$count = \DB::select(\DB::expr('COUNT(*) as count'))
				->from('preferences')->where('name', $setting)->execute()->current();

		if ($count['count'])
		{
			\DB::update('preferences')->value('value', $value)->where('name', $setting)->execute();
		}
		else
		{
			\DB::insert('preferences')->set(array('name' => $setting, 'value' => $value))->execute();
		}

		if ($reload)
		{
			return static::load_settings(true);
		}

		return static::$_preferences;
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

			static::set($name, $value, false);
		}

		// reload those preferences
		static::load_settings(true);
	}


	/**
	 * A lazy way to submit the preference panel input, saves some code in controller
	 *
	 * This function runs the custom validation function that uses the $form array
	 * to first run the original FuelPHP validation and then the anonymous
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
				// PHP doesn't allow periods in POST array
				$post[str_replace(',', '.', $key)] = $item;
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

}

/* end of file preferences.php */