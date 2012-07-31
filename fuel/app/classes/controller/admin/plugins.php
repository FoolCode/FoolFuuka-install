<?php

class Controller_Admin_Plugins extends Controller_Admin
{

	public function before()
	{
		parent::before();
		// set controller title
		$this->_views['controller_title'] = '<a href="' . Uri::Create("admin/plugins") . '">' . __("Plugins") . '</a>';
	}
	
	function action_manage()
	{
		$data = array();
		$data['plugins'] = \Plugins::get_all();
		$this->_views['method_title'] = __('Manage');
		$this->_views["main_content_view"] = \View::forge('admin/plugins/manage', $data);
		return \Response::forge(\View::forge('admin/default', $this->_views));
	}


	function action($slug)
	{
		if (!$this->input->post('action') || !in_array($this->input->post('action'),
				array('enable', 'disable', 'remove')))
		{
			show_404();
		}

		$action = $this->input->post('action');

		switch ($action)
		{
			case 'enable':
				$plugin = $this->plugins->enable($slug);
				if ($plugin === FALSE)
				{
					log_message('error', 'Plugin couldn\'t be enabled');
					flash_notice('error', __('The plugin couldn\'t be enabled.'));
				}
				else
				{
					flash_notice('success',
						sprintf(__('The %s plugin is now enabled.'), $plugin->info->name));
				}
				break;

			case 'disable':
				$plugin = $this->plugins->disable($slug);
				if ($plugin === FALSE)
				{
					log_message('error', 'Plugin couldn\'t be disabled');
					flash_notice('error', __('The plugin couldn\'t be disabled.'));
				}
				else
				{
					flash_notice('success',
						sprintf(__('The %s plugin is now disabled.'), $plugin->info->name));
				}
				break;

			case 'remove':
				$plugin = $this->plugins->get_by_slug($slug);
				$result = $this->plugins->remove($slug);
				if (isset($result['error']))
				{
					log_message('error', 'Plugin couldn\'t be removed');
					flash_notice('error',
						sprintf(__('The %splugin couldn\'t be removed.'), $plugin->info->name));
				}
				else
				{
					flash_notice('success',
						sprintf(__('The %s plugin was removed.'), $plugin->info->name));
				}
				break;
		}

		redirect('admin/plugins/manage');
	}


}