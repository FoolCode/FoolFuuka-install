<?php

namespace Foolz\Config;

/**
 * Reads PHP arrays in configuration files
 */
class Config
{
	/**
	 * Array of information for each package with configurations
	 *
	 * @var  array
	 */
	public static $packages = [];

	/**
	 * Add a package to the array of available configs
	 *
	 * @param  string  $package_name  The name of the package (use vendor/package format)
	 * @param  string  $dir           The directory of the package
	 * @param  string  $config_dir    The actual location of the config. Normally the "config/" dir relative to $dir
	 */
	public static function addPackage($package_name, $dir, $environment = 'override', $config_dir = 'config/')
	{
		static::$packages[$package_name] = [
			'dir' => rtrim($dir, '/').'/',
			'environment' => $environment,
			'config_dir' => rtrim($config_dir, '/').'/',
			'data' => null
		];
	}

	/**
	 * Remove a package from the array of available configs
	 *
	 * @param  string  $package_name  The name of the package (use vendor/package format)
	 *
	 * @return  \Foolz\Config\Config  The current object
	 */
	public static function removePackage($package_name)
	{
		unset(static::$packages[$package_name]);
	}

	/**
	 * Loads the config file. It's not necessary to do this as other methods call it if necessary.
	 * It will look down for other
	 *
	 * @param  string  $package_name  The name of the package (use vendor/package format)
	 * @param  string  $file          The filename where the config is located (without extension)
	 *
	 * @throws  \OutOfBoundsException  In case the package wasn't set
	 */
	public static function load($package_name, $file)
	{
		if ( ! isset(static::$packages[$package_name]))
		{
			// try using Composer format, the config could be somewhere down here!
			if (file_exists(VENDPATH.$package_name.'/config'))
			{
				static::addPackage($package_name, VENDPATH.$package_name);
			}
			else
			{
				throw new \OutOfBoundsException;
			}
		}

		if ( ! isset(static::$packages[$package_name]['data'][$file]))
		{
			$upper_level = static::$packages[$package_name]['dir'].static::$packages[$package_name]['config_dir']
					.static::$packages[$package_name]['environment'].'/'.$file.'.php';

			$lower_level = static::$packages[$package_name]['dir'].static::$packages[$package_name]['config_dir']
					.$file.'.php';

			if (file_exists($upper_level))
			{
				static::$packages[$package_name]['data'][$file] = require $upper_level;
			}
			else
			{
				static::$packages[$package_name]['data'][$file] = require $lower_level;
			}
		}
	}

	/**
	 * Get an element from the config file
	 *
	 * @param  string  $package_name  The name of the package (use vendor/package format)
	 * @param  string  $file          The filename where the config is located (without extension)
	 * @param  string  $key           The dotted key, each token is an array key
	 * @param  string  $fallback      The value returned if not found
	 *
	 * @return  mixed  The element, or $fallback
	 * @throws  \OutOfBoundsException  If the $package_name doesn't exist
	 */
	public static function get($package_name, $file, $key = '', $fallback = null)
	{
		static::load($package_name, $file);

		return static::dottedConfig(static::$packages[$package_name]['data'][$file], $key, $fallback);
	}

	/**
	 * Changes the values of the configuration
	 *
	 * @param  string  $package_name  The name of the package (use vendor/package format)
	 * @param  string  $file          The filename where the config is located (without extension)
	 * @param  string  $key           The dotted key, each token is an array key
	 * @param  string  $value         The value to set
	 *
	 * @throws  \OutOfBoundsException  In case the package wasn't set
	 */
	public static function set($package_name, $file, $key, $value)
	{
		static::load($package_name, $file);

		// @todo rewrite this to decouple from fuelphp
		\Arr::set(static::$packages[$package_name]['data'][$file], $key, $value);
	}


	/**
	 * Save the configuration array (it will be saved in the enviroment folder)
	 *
	 * @param  string  $package_name  The name of the package (vendor/package format)
	 * @param  string  $file          The filename where the config is located (without extension)
	 *
	 * @throws  \OutOfBoundsException  In case the package wasn't set
	 */
	public static function save($package_name, $file)
	{
		static::load($package_name, $file);

		$path = static::$packages[$package_name]['dir'].static::$packages[$package_name]['config_dir']
			.static::$packages[$package_name]['environment'].'/';

		if ( ! is_dir($path))
		{
			mkdir($path);
		}

		static::saveArrayToFile($path.$file.'.php', static::$packages[$package_name]['data'][$file]);
	}

	/**
	 * Returns the value of a deep associative array by using a dotted notation for the keys
	 *
	 * @param   array   $config    The config file to fetch the value from
	 * @param   string  $section   The dotted keys: akey.anotherkey.key
	 * @param   mixed   $fallback  The fallback value
	 *
	 * @return  mixed
	 */
	public static function dottedConfig($config, $section = '', $fallback = null)
	{
		if ($section === '')
		{
			return $config;
		}

		// get the section with the dot separated string
		$sections = explode('.', $section);
		$current = $config;
		foreach ($sections as $key)
		{
			if (isset($current[$key]))
			{
				$current = $current[$key];
			}
			else
			{
				return $fallback;
			}
		}

		return $current;
	}


	/**
	 * Saves an array to a PHP file with a return statement
	 *
	 * @param   string  $path   The target path
	 * @param   array   $array  The array to save
	 */
	public static function saveArrayToFile($path, $array)
	{
		$content = "<?php \n".
		"return ".var_export($array, true).';';

		file_put_contents($path, $content);
	}
}