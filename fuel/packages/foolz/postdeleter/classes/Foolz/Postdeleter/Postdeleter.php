<?php

namespace Foolz\Postdeleter;

/**
 * Registers paths to remove during shutdown. Extremely useful to get rid of temporary files.
 */
class Postdeleter
{
	/**
	 * Array of paths to get rid of on shutdown
	 *
	 * @var  array
	 */
	public $path = [];

	/**
	 * Destroys eventual uploaded files
	 */
	public function __destruct()
	{
		foreach ($this->to_delete as $del)
		{
			if ( ! file_exists($del))
			{
				continue;
			}

			if (is_dir($del))
			{
				static::flushDir($del);
				rmdir($del);
			}
			else
			{
				unlink($del);
			}
		}
	}

	/**
	 * Creates an object that will delete files on shutdown
	 *
	 * @param  array  $paths  Add an array of paths to be deleted on shutdown
	 *
	 * @return  \Fool\Postdeleter\Postdeleter
	 */
	public static function forge($paths = [])
	{
		$new = new static();
		$new->to_delete = $paths;
		return $new;
	}

	/**
	 * Removes the paths to remove so they aren't deleted
	 *
	 * @return  \Fool\Postdeleter\Postdeleter
	 */
	public function reset()
	{
		$this->to_delete = [];
		return $this;
	}

	/**
	 * Empties a directory
	 *
	 * @param  string  $path
	 */
	public static function flushDir($path)
	{
		$fp = opendir($path);

		while (false !== ($file = readdir($fp)))
		{
			// Remove '.', '..'
			if (in_array($file, array('.', '..')))
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
}