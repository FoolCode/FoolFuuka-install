<?php


class Controller_Admin_Users extends Controller_Admin
{

	public function before()
	{
		// only mods and admins can see and edit users
		if(!Auth::has_access('maccess.mod'))
		{
			Response::redirect('admin');
		}

		$this->_views['controller_title'] = __('Users');

		parent::before();
	}

	public function action_manage($page = 1)
	{
		if (intval($page) < 1)
		{
			$page = 1;
		}

		$data = array();
		$users_data = \Users::get_all($page, 40);
		$data['users'] = $users_data['result'];
		$data['count'] = $users_data['count'];

		$this->_views['method_title'] = __('Manage');
		$this->_views['main_content_view'] = View::forge('admin/users/manage', $data);

		return Response::forge(View::forge('admin/default', $this->_views));
	}

}