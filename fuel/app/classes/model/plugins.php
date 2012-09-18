<?php

namespace Model;

class PluginException extends \FuelException {}


/**
 * FoOlFrame Plugins Model
 *
 * The Plugins Model allows running code wherever there are hooks,
 * change parameters passed to functions manipulate returns, and create
 * (ficticious) controllers, and controller functions.
 *
 * @package        	FoOlFrame
 * @subpackage    	Models
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Plugins extends \Model
{

	/**
	 * List of routes and callbacks
	 *
	 * @var array
	 */
	protected static $_routes = array();

	/**
	 * List of hooks with their associated classes, priority and callback
	 *
	 * @var array
	 */
	protected static $_hooks = array();


	/**
	 * List of identifiers and their modules.
	 *
	 * @var array key is the identifier, the value is the lowercase name of the module
	 */
	protected static $_identifiers = array();

	protected static $_admin_sidebars = array();


	public static function initialize()
	{
		// store all the relevant data from the modules
		foreach (array_merge(array('foolframe'), \Config::get('foolframe.modules.installed')) as $module)
		{
			static::$_identifiers[\Config::get($module.'.main.identifier')] = array(
				'slug' => strtolower(\Config::get($module.'.main.name')),
				'dir' => \Config::get($module.'.directories.plugins')
			);
		}

		static::load_plugins();
	}

	/**
	 * The plugin slugs are the folder names
	 *
	 * @return array The directory names
	 */
	public static function lookup_plugins()
	{
		$slugs = array();

		// get all the plugins stacked by identifier
		foreach (static::$_identifiers as $key => $item)
		{
			$temp = \File::read_dir($item['dir'], 1);

			// remove the dir's last slash
			foreach ($temp as $k => $i)
			{
				$slugs[$key][] = rtrim($k, '/');
			}
		}

		return $slugs;
	}


	public static function get_module_name_by_identifier($identifier)
	{
		return static::$_identifiers[$identifier]['slug'];
	}


	protected static function get_plugin_dir($identifier, $slug)
	{
		return static::$_identifiers[$identifier]['dir'].$slug.'/';
	}


	/**
	 * Grabs the info from the plugin _info.php file and returns it as object
	 *
	 * @param string $slug the directory of the plugin
	 * @return object the config
	 */
	public static function get_info($identifier, $slug)
	{
		$dir = static::get_plugin_dir($identifier, $slug);
		return \Fuel::load($dir.'config/config.php');
	}

	
	public static function clear_cache()
	{
		\Cache::delete('ff.model.plugins.get_all.query');
		\Cache::delete('ff.model.plugins.get_enabled.query');
	}

	/**
	 * Retrieve all the available plugins and their status
	 *
	 * @return array Array of objects where $this[$x]->info contains the plugin info
	 */
	public static function get_all()
	{
		\Profiler::mark('Plugins::get_all Start');
		
		$slugs = static::lookup_plugins();

		$result = array();

		if (count($slugs) > 0)
		{
			$slugs_to_sql = $slugs;

			try
			{
				$result = \Cache::get('ff.model.plugins.get_all.query');
			}
			catch (\CacheNotFoundException $e)
			{
				// we don't care if the database doesn't contain an entry for a plugin
				// in that case, it means it was never installed
				$query = \DB::select()
					->from('plugins');

				foreach ($slugs_to_sql as $key => $item)
				{
					foreach ($item as $slug)
					{
						$query->or_where_open();
						$query->where('identifier', $key);
						$query->where('slug', $slug);
						$query->or_where_close();
					}
				}

				$result = $query->execute()->as_array();
				\Cache::set('ff.model.plugins.get_all.query', $result, 3600);
			}
		}

		$slugs_with_data = array();
		foreach ($slugs as $key => $item)
		{
			foreach ($item as $slug)
			{
				$done = false;
				foreach ($result as $r)
				{
					if ($key == $r['identifier'] && $slug == $r['slug'])
					{
						$slugs_with_data[$key][$slug] = $r;
						$slugs_with_data[$key][$slug]['installed'] = true;
						$done = true;
					}
				}

				if($done === false) $slugs_with_data[$key][$slug] = array();
				$slugs_with_data[$key][$slug]['info'] = static::get_info($key, $slug);

				if ($done === false)
				{
					$slugs_with_data[$key][$slug]['enabled'] = false;
					$slugs_with_data[$key][$slug]['installed'] = false;
				}
			}
		}

		\Profiler::mark_memory($slugs_with_data, 'Plugins object - $slugs_with_data');
		\Profiler::mark('Plugins::get_all End');
		return $slugs_with_data;
	}


	/**
	 * Gets the enabled plugins from the database
	 *
	 * @return array Array of objects, the rows or the active plugins
	 */
	protected static function get_enabled()
	{
		try
		{
			$result = \Cache::get('ff.model.plugins.get_enabled.query');
		}
		catch (\CacheNotFoundException $e)
		{
			$result = \DB::select()
				->from('plugins')
				->where('enabled', 1)
				->execute()
				->as_array();
			
			\Cache::set('ff.model.plugins.get_enabled.query', $result, 3600);
		}
			
		return $result;
	}


	/**
	 * Gets the data of the plugin by slug
	 *
	 * @param string $slug the directory name of the plugin
	 * @return object The database row of the plugin with extra ->info
	 */
	public static function get($identifier, $slug)
	{
		$query = \DB::select()
			->from('plugins')
			->where('identifier', $identifier)
			->where('slug', $slug)
			->execute();

		if(!count($query))
		{
			return false;
		}

		$result = $query->current();

		$result['info'] = static::get_info($identifier, $slug);

		return $result;
	}


	/**
	 * Loads and eventually initializes (runs) the plugins
	 *
	 * @param null|string $select the slug of the plugin if you want to choose one
	 * @param bool $initialize choose if just load the file or effectively run the plugin
	 */
	public static function load_plugins($identifier = NULL, $slug = NULL, $initialize = TRUE)
	{
		$plugins = static::get_enabled();

		foreach ($plugins as $plugin)
		{
			if(!is_null($identifier) && $plugin['identifier'] != $identifier && $plugin['slug'] != $slug)
				continue;

			$path = static::get_plugin_dir($plugin['identifier'], $plugin['slug']).'/bootstrap.php';

			if (file_exists($path))
			{
				\Fuel::load($path);
			}
		}
	}


	public static function install($identifier, $slug)
	{
		$dir = static::get_plugin_dir($identifier, $slug);

		if (file_exists($dir.'install.php'))
		{
			\Fuel::load($dir.'install.php');
		}

		\DB::insert('plugins')
			->set(array('identifier' => $identifier, 'slug' => $slug, 'enabled' => 1))
			->execute();
		
		static::clear_cache();
	}


	/**
	 * Deletes the plugin directory after running plugin_remove()
	 *
	 * @param string $slug the directory name of the plugin
	 */
	public static function uninstall($idenfitier, $slug)
	{
		$dir = static::get_plugin_dir($identifier, $slug);

		if (file_exists($dir.'uninstall.php'))
		{
			\Fuel::load($dir.'uninstall.php');
		}

		\DB::delete('plugins')
			->where('identifier', $identifier)
			->where('slug', $slug)
			->execute();
		
		static::clear_cache();
	}


	/**
	 * Enables the plugin after running the upgrade function
	 */
	public static function enable($identifier, $slug)
	{
		$dir = static::get_plugin_dir($identifier, $slug);

		$count = \DB::select(\DB::expr('COUNT(*) as count'))
			->from('plugins')
			->where('identifier', $identifier)
			->where('slug', $slug)
			->as_object()
			->execute()
			->current()->count;

		// if the plugin isn't installed yet, we will run install.php and NOT enable.php
		if (!$count)
		{
			return static::install($identifier, $slug);
		}

		if (file_exists($dir.'enable.php'))
		{
			\Fuel::load($dir.'enable.php');
		}

		\DB::update('plugins')
			->where('identifier', $identifier)
			->where('slug', $slug)
			->value('enabled', 1)
			->execute();
		
		static::clear_cache();
	}


	/**
	 * Disables plugin and runs plugin_disable()
	 */
	public static function disable($identifier, $slug)
	{
		$dir = static::get_plugin_dir($identifier, $slug);

		if (file_exists($dir.'disable.php'))
		{
			\Fuel::load($dir.'disable.php');
		}

		\DB::update('plugins')
			->where('identifier', $identifier)
			->where('slug', $slug)
			->value('enabled', 0)
			->execute();
		
		static::clear_cache();
	}


	/**
	 * Reads revision in the info files, uses the upgrade_xxx functions to upgrade files/db
	 * then updates the revision in the database
	 *
	 * @param string $slug the directory name of the plugin
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public static function upgrade($idenfitier, $slug)
	{}

	/**
	 * Adds a sidebar element when admin controller is accessed.
	 *
	 * @param string $section under which controller/section of the sidebar must this sidebar element appear
	 * @param array $array the overriding array, comprehending only the additions and modifications to the sidebar
	 */
	public static function register_sidebar_element($type, $section, $array = null)
	{
		// the user can also send an array with the index inseted in $section
		if(!is_null($array))
		{
			$array2 = array();
			$array2[$section] = $array;
			$array = $array2;
		}
		else
		{
			$array = $section;
		}

		static::$_admin_sidebars[$type][] = $array;

		\Controller_Admin::add_sidebar_element($array);
	}


	public static function get_sidebar_elements($type)
	{
		if (!isset(static::$_admin_sidebars[$type]))
		{
			return array();
		}

		return static::$_admin_sidebars[$type];
	}

	/**
	 * Runs functions stored in the hook
	 *
	 * @param string $target the name of the hook
	 * @param array $parameters parameters to pass to the hook
	 * @param bool|string $type if FALSE it's a simple hook, 'before'/'after' if before or after a method
	 * @return null
	 */
	public static function run_hook($target, $parameters = array(), $modifier = '')
	{
		if(!isset(static::$_hooks[$target]) && $modifier == 'simple')
		{
			return end($parameters);
		}

		if(!isset(static::$_hooks[$target]))
		{
			return NULL;
		}

		$hook_array = static::$_hooks[$target];

		usort($hook_array, function($a, $b){ return $a['priority'] - $b['priority']; });

		// default return if nothing happens
		$return = array('parameters' => $parameters, 'return' => NULL);

		foreach($hook_array as $hook)
		{
			// if this is 'after', we might already have an extra parameter in the array that is the previous result

			// this works whether it's a closure or \Some\method::calling
			$return_temp = call_user_func_array($hook['method'], $parameters);

			if(is_null($return_temp))
			{
				// if NULL, the plugin creator didn't want to send a message outside
				continue;
			}
			else if(!is_array($return_temp))
			{
				// if not an array, it's a plain result to stack in
				// the plugin creator can't do this if the result set is an array, and must use the complex solution
				$return['return'] = $return_temp;
				// but the return as last parameter
				array_push($parameters, $return['return']);
				continue;
			}

			// in the most complex situation, we have array('parameters'=>array(...), 'return'=>'value')
			if($modifier == 'simple' && !is_null($return_temp['return']))
			{
				// if simple we just stack the single parameter over and over
				$parameters = array($return_temp['return']);
			}
			else if(isset($return_temp['parameters']))
			{
				$parameters = $return_temp['parameters'];
				$return['parameters'] = $return_temp['parameters'];
			}
			if(isset($return_temp['return']))
			{
				$return['return'] = $return_temp['return'];
				// but the return as last parameter
				array_push($parameters, $return['return']);
			}
		}

		if($modifier == 'simple')
			return $return['return'];

		return $return;
	}


	/**
	 * Registers a function to the hook targeted
	 *
	 * @param object $class the class in which the method to run is located
	 * @param string $target the name of the hook
	 * @param int $priority the lowest, the highest the priority. negatives ALLOWED
	 * @param string|Closure $method name of the method or the closure to run
	 */
	public static function register_hook($target, $method, $priority = 5)
	{
		if (is_array($target))
		{
			foreach ($target as $t)
			{
				static::register_hook($t, $method, $priority);
			}	
			
			return null;
		}
		
		static::$_hooks[$target][] = array('method' => $method, 'priority' => $priority);
	}

}

/* end of file plugins.php */