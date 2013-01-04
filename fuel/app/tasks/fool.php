<?php

namespace Fuel\Tasks;

use \Foolz\Config\Config;

class Fool
{
	public function run()
	{
		\Cli::write(__('Welcome to the FoolFrame command line system.'));

		$modules = Config::get('foolz/foolframe', 'config', 'modules.installed');

		if(count($modules) > 1)
		{
			$module = \Cli::prompt(__('Which module would you like to work with?'), $modules);
		}
		else
		{
			$module = $modules[0];
		}

		list($vendor, $package) = explode('/', $module);

		$class = '\\'.ucfirst($vendor).'\\'.ucfirst($package).'\\Task\\Fool';

		$instance = new $class;

		if (method_exists($instance, 'before'))
		{
			$instance->before();
		}

		$instance->run();
	}
}