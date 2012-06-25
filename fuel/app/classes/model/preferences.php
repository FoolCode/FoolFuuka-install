<?php

namespace Model;


class Preferences extends \Model
{

	private static $_preferences = array();


	public static function _init()
	{
		self::load_settings();
	}


	public static function load_settings()
	{
		$preferences = \DB::select('*')->from('preferences')->as_assoc()->execute();

		foreach($preferences as $pref)
		{
			self::$_preferences[$pref['name']] = $pref['value'];
		}

		return self::$_preferences;
	}


	public static function save_settings($data)
	{
		if (is_array($data) && count($data) > 0)
		{
			foreach ($data as $setting => $value)
			{
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

			return $this->load_settings();
		}

		return false;
	}


	public static function get($setting, $fallback = null)
	{
		if(isset(self::$_preferences[$setting]))
			return self::$_preferences[$setting];

		if($fallback != null)
			return $fallback;

		return null;
	}


	/**
	 *
	 *  This is a function to strip the 'name' tag from HTML
	 *
	public static function get($setting, $fallback = null)
	{
		$preferences = self::$_preferences;

		// remove associative array
		if (substr($setting, -2, 1) == '[' && substr($setting, -1, 1) == ']')
		{
			$pos = strrpos($setting, '[');
			$key = substr($setting, $pos + 1, -1);
			$setting = substr($setting, 0, $pos);
		}

		if (isset($preferences[$setting]) && $preferences[$setting] !== null)
		{
			return trim($preferences[$setting]);
		}

		if (!is_null($fallback))
		{
			return trim($fallback);
		}

		return false;
	}
	 *
	 *
	 */


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

		return $this->load_settings();
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