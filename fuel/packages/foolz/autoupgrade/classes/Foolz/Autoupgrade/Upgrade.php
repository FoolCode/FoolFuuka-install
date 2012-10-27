<?php

namespace Foolz\Autoupgrade;

use Foolz\Postdeleter\Postdeleter;

class UpgradeException extends \Exception {}

/**
 * The client class to fetch upgrades and replace old files
 */
class Upgrade
{
	/**
	 * The currenrly installed container
	 *
	 * @var  \Foolz\Autoupgrade\Container
	 */
	public $current_container;

	/**
	 * The container fetched from
	 *
	 * @var  \Foolz\Autoupgrade\Container
	 */
	public $new_container;

	/**
	 * The directory where the content to replace can be found
	 *
	 * @var  string
	 */
	public $dir;

	/**
	 * A temporary directory where to safely store the downloaded files
	 *
	 * @var  string
	 */
	public $dir_temp;

	/**
	 * Directories that should be left untouched by the upgrade
	 *
	 * @var  array
	 */
	public $path_ignore = [];

	/**
	 * The URL to the POD server
	 *
	 * @var  string
	 */
	public $pod;

	/**
	 * Creates a new Container object from a json
	 *
	 * @param  string  $dir  The directory where the composer.json is located
	 * @param  string  $pod  The URL to the POD server
	 *
	 * @return  \Foolz\Autoupgrade\Upgrade  The current object
	 */
	public static function forge($dir, $pod)
	{
		$new = new static();
		$new->dir = rtrim($dir, '/').'/';
		$new->dir_temp = rtrim($dir, '/').'/';
		$new->pod = $pod;

		try
		{
			$new->current_container = Container::forgeFromComposer($this->dir.'composer.json');
		}
		catch (\Foolz\Autoupgrade\ContainerException $e)
		{
			throw new UpgradeException(__($e->getMessage()));
		}

		return $new;
	}

	/**
	 * Places the composer.json in the specified directory and fetches the content
	 *
	 * @param  string  $type_slug  The type of container
	 * @param  string  $name       The name of the container
	 * @param  string  $dir        The directory where to install the plugin
	 * @param  string  $pod        The URL of the pod
	 *
	 * @return  \Foolz\Autoupgrade\Upgrade           An upgrade object
	 * @throws  \Foolz\Autoupgrade\UpgradeException  In case of fetching errors
	 */
	public static function install($type_slug, $name, $dir, $pod)
	{
		$dir = rtrim($dir, '/').'/';
		$container = static::getLatest($type_slug, $name, $pod);

		if ( ! file_exists($dir))
		{
			mkdir($dir);
		}

		file_put_contents($dir.'composer.json', $container['json']);

		$new = static::forge($dir, $pod);
		$new->check();
		$new->fetch();
		$new->replace();
		$new->clean();
	}

	/**
	 * Set an array directories to ignore in case the element is being autoupdated. Previous sets will be nullified by this
	 * The path must be relative to the root directory of the container
	 *
	 * @param  array  $dir_array  The array of directories to ignore
	 *
	 * @return  \Foolz\Autoupgrade\Upgrade  The current object
	 */
	public function setPathIgnore(Array $dir_array)
	{
		foreach ($dir_array as $dir)
		{
			$this->path_ignore[] = explode('/', $dir);
		}

		// we need to ignore at least this directory
		return $this;
	}

	/**
	 * Add a directory to the ignore array
	 *
	 * @see  setDirIgnore
	 * @param  type  $dir
	 *
	 * @return \Foolz\Autoupgrade\Upgrade
	 */
	public function addPathIgnore($dir)
	{
		$this->path_ignore[] = explode('/', $dir);
		return $this;
	}

	/**
	 * Returns the container if there's an update
	 *
	 * @return  \Foolz\Autoupgrade\Container  The container to upgrade to
	 */
	public function getContainer()
	{
		return $this->container;
	}


	/**
	 * Returns the
	 *
	 * @param type $type_slug
	 * @param type $name
	 * @param type $pod
	 * @return boolean
	 * @throws UpgradeException
	 */
	public static function getLatest($type_slug, $name, $pod)
	{
		$container = file_get_contents($pod.'/_/api/pod/latest/?type='
			.rawurlencode($type_slug).'name='.rawurlencode($name));

		$container = json_decode($container, true);

		if ($container === null)
		{
			throw new UpgradeException(__('The upgrade server response was unreadable.'));
		}

		if (count($container) !== 1)
		{
			// we actually can't get a version of this
			return false;
		}

		if (isset($container['error']))
		{
			throw new UpgradeException(__('The upgrade server sent an error: "'.$container['error'].'".'));
		}

		return $container;
	}


	public function run()
	{
		if ($this->check())
		{
			$this->fetch();
			$this->replace();
			$this->clean();
		}
	}

	/**
	 * Make a call to the POD and check if there's an higher version available.
	 *
	 * @return  boolean  True if there's a new version, false otherwise
	 */
	public function check()
	{
		$container = static::getLatest($this->current_container->type, $this->current_container->name, $this->pod);

		$container = Container::forge($container);

		$container_ver = $container->major
			.'.'.$container->minor
			.'.'.$container->patch
			.($container->rc ? 'RC'.$container->rc : '');

		$cur_container_ver = $this->current_container->major
			.'.'.$this->current_container->minor
			.'.'.$this->current_container->patch
			.($this->current_container->rc ? 'RC'.$this->current_container->rc : '');

		if (version_compare($container_ver, $cur_container_ver) >= 0)
		{
			$this->container = $container;
			return true;
		}

		return false;
	}

	/**
	 * Fetch the upgrade file and extract it in the temp_dir
	 *
	 * @throws UpgradeException
	 * @throws ContainerInsertException
	 */
	public function fetch()
	{
		$direct_link = file_get_contents($this->pod.'/_/api/pod/download/?id='.$this->container->id);
		$direct_link = json_decode($direct_link, true);

		if ($direct_link === null || ! isset($direct_link['direct_link']))
		{
			throw new UpgradeException(__('The upgrade server response was unreadable.'));
		}

		if (isset($direct_link['error']))
		{
			throw new UpgradeException(__('The upgrade server sent an error: "'.$container['error'].'".'));
		}

		$temp_dir = $this->dir_temp.str_replace('/', '_', $this->name).'/';

		if ( ! file_exists($temp_dir))
		{
			mkdir($temp_dir, 0777, true);
		}

		$file = file_get_contents($direct_link['direct_link']);

		if ($file === false)
		{
			throw new UpgradeException(__('The container could not be fetched.'));
		}

		file_put_contents($temp_dir.'archive.zip', $file);

		$zip = new \ZipArchive;

		$res = $zip->open($temp_dir.'archive.zip');

		if ($res !== true)
		{
			throw new UpgradeException(__('Couldn\'t open the zip.'));
		}

		Postdeleter::forge([$temp_dir.'archive/', $temp_dir.'old/']);

		if ( ! file_exists($temp_dir.'archive/'))
		{
			mkdir($temp_dir.'archive/', 0777, true);
		}

		if ( ! file_exists($temp_dir.'old/'))
		{
			mkdir($temp_dir.'old/', 0777, true);
		}

		if ( ! file_exists($temp_dir.'backup/'))
		{
			mkdir($temp_dir.'backup/', 0777, true);
		}

		$zip->extractTo($temp_dir.'archive/');
		$zip->close();
	}


	/**
	 * Remove the files and replace with the new versions
	 */
	public function replace()
	{
		$temp_dir = $this->dir_temp.str_replace('/', '_', $this->name).'/';

		// move the ignored files in a safe place
		Util::renameArray($this->path_ignore, $this->dir, $temp_dir.'backup/');

		// get rid of the old version
		Util::flushDir($this->dir);

		// make the content of the archive the new content
		rename($temp_dir.'archive/', $this->dir);

		// move back the ignored files
		Util::renameArray($this->path_ignore, $temp_dir.'backup/', $this->dir);
	}

	/**
	 * Removes temporary folders created by fetch
	 */
	public function clean()
	{
		$temp_dir = $this->dir_temp.str_replace('/', '_', $this->name).'/';
		static::flushDir($temp_dir);
		rmdir($temp_dir);
	}
}