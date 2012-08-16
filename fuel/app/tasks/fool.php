<?php

namespace Fuel\Tasks;

class Fool
{
	public function run()
	{
		\Cli::write(__('Welcome to the FoolFrame command line system.'));
		
		$modules = \Config::get('foolframe.modules.installed');
		
		if(count($modules) > 1)
		{
			$module = \Cli::prompt(__('What module would you like to work on?'), $modules);
		}
		else
		{
			$module = $modules[0];
		}
		
		$class = '\\'.ucfirst($module).'\\Tasks\\Fool';
		
		$instance = new $class;
		
		if (method_exists($instance, 'before'))
		{
			$instance->before();
		}
		
		$instance->run();
	}
}