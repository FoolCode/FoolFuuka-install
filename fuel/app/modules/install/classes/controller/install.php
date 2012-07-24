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
			Response::redirect('');
		}
	}

	public function action_index()
	{
		$data = array();
		$data['check'] = \Install::system_check();

		$this->_view_data['method_title'] = 'Welcome';
		$this->_view_data['main_content_view'] = \View::forge('install::welcome', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

	public function action_database()
	{
		$data = array();

		$this->_view_data['method_title'] = 'Database connection';
		$this->_view_data['main_content_view'] = \View::forge('install::database', $data);
		return \Response::forge(\View::forge('install::default', $this->_view_data));
	}

	public function action_database_confirm()
	{
		if (!\Input::post())
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

				
			}
		}
	}


}