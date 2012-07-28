<?php

namespace Install;

class Controller_Install extends \Controller
{

	protected $_view_data = array(
		'title' => 'Installing FoolFrame',
		'controller_title' => 'Installing FoolFrame'
	);

	public function before()
	{
		// don't let in people if it's already installed
		if (\Config::get('foolframe.install.installed'))
		{
			throw new \HttpNotFoundException;
		}
	}

	public function sidebar($status)
	{
		$sidebar = array(
			'welcome' => __('Welcome'),
			'system_check' => __('System check'),
			'database_connection' => __('Database connection'),
			'create_user' => __('User creation'),
			'install_modules' => __('Install modules'),
			'complete' => __('Complete'),
		);

		$this->_view_data['sidebar'] = \View::forge('install::sidebar', array('sidebar' => $sidebar, 'current' => $status));
	}

	public function action_index()
	{
		$data = array();
		$data['check'] = \Install::check_system();

		$this->sidebar('welcome');
		$this->_view_data['method_title'] = 'Welcome';
		$this->_view_data['main_content_view'] = \View::forge('install::welcome', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

	public function action_system_check()
	{
		$data = array();
		$data['check'] = \Install::check_system();

		$this->sidebar('system_check');
		$this->_view_data['method_title'] = 'Welcome';
		$this->_view_data['main_content_view'] = \View::forge('install::system_check', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

	public function action_database_connection()
	{
		$data = array();

		if (\Input::post())
		{
			$val = \Validation::forge('database');
			$val->add_field('hostname', __('Hostname'), 'required|trim');
			$val->add_field('prefix', __('Prefix'), 'trim');
			$val->add_field('username', __('Username'), 'required|trim');
			$val->add_field('password', __('Password'), 'required');
			$val->add_field('database', __('Database name'), 'required|trim');

			if ($val->run())
			{
				$input = $val->input();
				$input['type'] = 'mysqli';

				if (!\Install::check_database($input))
				{
					\Install::setup_database($input);
					\Migrate::latest();
					\Install::create_salts();
					\Response::redirect('install/create_user');
				}
				else
				{
					$this->_view_data['error'] = __('The database couldn\'t be contacted with the specificed coordinates.');
				}
			}
			else
			{
				$this->_view_data['error'] = implode(' ', $val->error());
			}
		}

		$this->sidebar('database_connection');
		$this->_view_data['method_title'] = 'Database connection';
		$this->_view_data['main_content_view'] = \View::forge('install::database', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

	public function action_create_user()
	{
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
				$user = \Users::get_user();
				$user->save(array('group' => 100));
				\Response::redirect('install/modules');
			}
			else
			{
				$this->_view_data['error'] = implode(' ', $val->error());
			}
		}

		$this->sidebar('create_user');
		$this->_view_data['method_title'] = 'Administrator account creation';
		$this->_view_data['main_content_view'] = \View::forge('install::create_user');
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


	public function action_modules()
	{
		if (\Input::post())
		{
			\Config::load('foolframe', 'foolframe');

			$modules = array();

			if (\Input::post('foolfuuka'))
			{
				$modules[] = 'foolfuuka';
				\Migrate::latest('foolfuuka', 'module');
			}

			if (\Input::post('foolpod'))
			{
				$modules[] = 'foolpod';
				\Migrate::latest('foolpod', 'module');
			}

			\Config::set('foolframe.modules.installed', $modules);
			\Config::save('foolframe', 'foolframe');

			\Response::redirect('install/complete');
		}

		$this->sidebar('install_modules');
		$this->_view_data['method_title'] = 'Module installation';
		$this->_view_data['main_content_view'] = \View::forge('install::modules');
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

	public function action_complete()
	{
		// lock down the install system
		\Config::load('foolframe');
		\Config::set('foolframe.install.installed', true);
		\Config::save('foolframe', 'foolframe');

		$this->sidebar('complete');
		$this->_view_data['method_title'] = 'Installation completed';
		$this->_view_data['main_content_view'] = \View::forge('install::complete');
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}


}