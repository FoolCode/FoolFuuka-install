<?php

namespace Foolz\Autoupgrade;

/**
 * Collection of utilities used in Foolz\Autoupgrade
 *
 * @author Foolz <support@foolz.us>
 * @package Foolz\Autoupgrade
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class Util
{
	/**
	 * Empties a directory
	 *
	 * @param  string  $path
	 */
	public static function flushDir($path, $root_exclude = null)
	{
		$fp = opendir($path);

		while (false !== ($file = readdir($fp)))
		{
			// Remove '.', '..'
			if (in_array($file, array('.', '..')))
			{
				continue;
			}

			// protected directory (for downloaded archive)
			if ($root_exclude === $file)
			{
				continue;
			}

			$filepath = $path.'/'.$file;

			if (is_dir($filepath))
			{
				static::flushDir($filepath);

				// removing dir here won't remove the root dir, just as we want it
				rmdir($filepath);
				continue;
			}
			else if (is_file($filepath))
			{
				unlink($filepath);
			}
		}

		closedir($fp);
	}

	/**
	 * Moves the paths specified in an array elsewhere keeping the folder structure
	 *
	 * @param  array  $array  Array of paths relative to $from
	 * @param  type   $from   Source path
	 * @param  type   $to     Destination path
	 */
	public static function renameArray($array, $from, $to)
	{
		foreach ($array as $ignored)
		{
			$to_path = $to.$ignored;
			$from_path = $from.$ignored;

			if (is_dir($from_path))
			{
				if ( ! file_exists($to_path))
				{
					mkdir($to_path, 0777, true);
				}

				rename($from_path, $to_path);
			}
			elseif (is_file($from_path))
			{
				$dir_path = dirname($from_path);

				if ( ! file_exists($dir_path))
				{
					mkdir($dir_path, 0777, true);
				}

				rename($from_path, $to_path);
			}
		}
	}
}