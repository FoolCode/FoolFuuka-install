<?php

class Controller_Admin_Plugins extends Controller_Admin
{

	public function before()
	{
		parent::before();

		if( ! Auth::has_access('maccess.admin'))
		{
			Response::redirect('admin');
		}

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


	function action_action($identifier, $vendor, $slug)
	{
		$slug = $vendor.'/'.$slug;

		if (\Input::post() && ! \Security::check_token())
		{
			\Notices::set_flash('warning', __('The security token wasn\'t found. Try resubmitting.'));
			\Response::redirect('admin/plugins/manage');
		}

		if (!\Input::post('action') || !in_array(\Input::post('action'), array('enable', 'disable', 'remove')))
		{
			throw new HttpNotFoundException;
		}

		$action = \Input::post('action');

		$plugin = \Plugins::get_plugin($identifier, $slug);

		if (!$plugin)
		{
			throw new HttpNotFoundException;
		}

		switch ($action)
		{
			case 'enable':
				try
				{
					\Plugins::enable($identifier, $slug);
				}
				catch (\Plugins\PluginException $e)
				{
					\Notices::set_flash('error', \Str::tr(__('The plugin :slug couldn\'t be enabled.'),
						array('slug' => $plugin->getJsonConfig('extra.name'))));
					break;
				}

				\Notices::set_flash('success',
					\Str::tr(__('The :slug plugin is now enabled.'), array('slug' => $plugin->getJsonConfig('extra.name'))));

				break;

			case 'disable':
				try
				{
					\Plugins::disable($identifier, $slug);
				}
				catch (\Plugins\PluginException $e)
				{
					\Notices::set_flash('error', \Str::tr(__('The :slug plugin couldn\'t be enabled.'),
						array('slug' => $plugin->getJsonConfig('extra.name'))));
					break;
				}

				\Notices::set_flash('success',
					\Str::tr(__('The :slug plugin is now disabled.'), array('slug' => $plugin->getJsonConfig('extra.name'))));
				break;

			case 'upgrade':
				break;

			case 'remove':
				try
				{
					\Plugin::remove($identifier, $slug);
				}
				catch (\Plugins\PluginException $e)
				{
					\Notices::set_flash('error',
						\Str::tr(__('The :slug plugin couldn\'t be removed.'),
							array('slug' => $plugin->getJsonConfig('extra.name'))));
					break;
				}
				\Notices::set_flash('success',
					\Str::tr(__('The :slug plugin was removed.'), array('slug' => $plugin->getJsonConfig('extra.name'))));
				break;
		}

		\Response::redirect('admin/plugins/manage');
	}


}