<?php

namespace Foolz\Install\Controller;

use \Foolz\Config\Config;
use \Foolz\Foolframe\Model\DoctrineConnection as DC;

class Install extends \Controller
{

	protected $_view_data = [
		'title' => 'FoolFrame Installation',
		'controller_title' => 'FoolFrame Installation'
	];

	public function before()
	{
		// don't let in people if it's already installed
		if (Config::get('foolz/foolframe', 'package', 'install.installed'))
		{
			throw new \HttpNotFoundException;
		}
	}


	public function process($action)
	{
		$procedure = array(
			'welcome' => __('Welcome'),
			'system_check' => __('System Check'),
			'database_setup' => __('Database Setup'),
			'create_admin' => __('Admin Account'),
			'modules' => __('Install Modules'),
			'complete' => __('Congratulations'),
		);

		$this->_view_data['sidebar'] = \View::forge('install::sidebar', ['sidebar' => $procedure, 'current' => $action]);
	}


	public function action_index()
	{
		$data = [];

		$this->process('welcome');
		$this->_view_data['method_title'] = __('Welcome');
		$this->_view_data['main_content_view'] = \View::forge('install::welcome', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


	public function action_system_check()
	{
		$data = [];
		$data['system'] = \Foolz\Install\Model\Install::check_system();

		$this->process('system_check');
		$this->_view_data['method_title'] = __('System Check');
		$this->_view_data['main_content_view'] = \View::forge('install::system_check', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


	public function action_database_setup()
	{
		$data = [];

		if (\Input::post())
		{
			$val = \Validation::forge('database');
			$val->add_field('hostname', __('Hostname'), 'required|trim');
			$val->add_field('prefix', __('Prefix'), 'trim');
			$val->add_field('username', __('Username'), 'required|trim');
			$val->add_field('password', __('Password'), '');
			$val->add_field('database', __('Database name'), 'required|trim');

			if ($val->run())
			{
				$input = $val->input();
				$input['type'] = 'pdo_mysql';

				if ( ! \Foolz\Install\Model\Install::check_database($input))
				{
					\Foolz\Install\Model\Install::setup_database($input);
					$sm = \Foolz\Foolframe\Model\SchemaManager::forge(DC::forge(), DC::getPrefix());
					\Foolz\Foolframe\Model\Schema::load($sm);
					$sm->commit();
					\Foolz\Install\Model\Install::create_salts();
					\Response::redirect('install/create_admin');
				}
				else
				{
					$this->_view_data['errors'] = __('Connection to specified database failed. Please check your connection details again.');
				}
			}
			else
			{
				$this->_view_data['errors'] = $val->error();
			}
		}

		$this->process('database_setup');
		$this->_view_data['method_title'] = __('Database Setup');
		$this->_view_data['main_content_view'] = \View::forge('install::database_setup', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


	public function action_create_admin()
	{
		// if an admin account exists, lock down this step and redirect to the next step instead
		\Config::load('foolauth', 'foolauth');
		$check_users = \Foolz\Foolframe\Model\Users::get_all();

		if ($check_users['count'] > 0)
		{
			\Response::redirect('install/modules');
		}

		if (\Input::post())
		{
			$val = \Validation::forge('database');
			$val->add_field('username', __('Username'), 'required|trim|min_length[4]|max_length[32]');
			$val->add_field('email', __('Email'), 'required|trim|valid_email');
			$val->add_field('password', __('Password'), 'required|min_length[4]|max_length[32]');
			$val->add_field('confirm_password', __('Confirm password'), 'required|match_field[password]');

			if ($val->run())
			{
				$input = $val->input();

				list($id, $activation_key) = \Auth::create_user($input['username'], $input['password'], $input['email']);
				\Auth::activate_user($id, $activation_key);
				\Auth::force_login($id);
				$user = \Foolz\Foolframe\Model\Users::get_user();
				$user->save(['group_id' => 100]);
				\Response::redirect('install/modules');
			}
			else
			{
				$this->_view_data['errors'] = $val->error();
			}
		}

		$this->process('create_admin');
		$this->_view_data['method_title'] = __('Admin Account');
		$this->_view_data['main_content_view'] = \View::forge('install::create_admin');
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


	public function action_modules()
	{
		$data = [];
		$data['modules'] = \Foolz\Install\Model\Install::modules();

		if (\Input::post())
		{
			\Config::load('foolframe', 'foolframe');

			$modules = ['ff' => 'foolz/foolframe'];

			$sm = \Foolz\Foolframe\Model\SchemaManager::forge(DC::forge(), DC::getPrefix());
			\Foolz\Foolframe\Model\Schema::load($sm);

			if (\Input::post('foolfuuka'))
			{
				$modules['fu'] = 'foolz/foolfuuka';

				\Foolz\Foolfuuka\Model\Schema::load($sm);
			}

			if (\Input::post('foolpod'))
			{
				$modules['fp'] = 'foolz/foolpod';
			}

			if (\Input::post('foolslide'))
			{
				$modules['fs'] = 'foolz/foolslide';
			}

			$sm->commit();

			if (count($modules) > 0)
			{
				Config::set('foolz/foolframe', 'package', 'modules.installed', $modules);
				Config::save('foolz/foolframe', 'package');

				\Response::redirect('install/complete');
			}
			else
			{
				$this->_view_data['errors'] = __('Please select at least one module.');
			}
		}

		$this->process('modules');
		$this->_view_data['method_title'] = __('Install FoolFrame Modules');
		$this->_view_data['main_content_view'] = \View::forge('install::modules', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


	public function action_complete()
	{
		// lock down the install system
		Config::set('foolz/foolframe', 'package', 'install.installed', true);
		Config::save('foolz/foolframe', 'package');

		$this->process('complete');
		$this->_view_data['method_title'] = __('Congratulations');
		$this->_view_data['main_content_view'] = \View::forge('install::complete');
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

}