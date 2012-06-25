<?php

class Controller_Admin extends Controller_Common
{

	protected $_views = null;

    public function before()
    {
		if( ! Auth::has_access('admin.logged') && \URI::segment(2) != 'auth')
			return Response::redirect('admin/auth/login');

		$this->_views['navbar'] = View::forge('admin/navbar');
		$this->_views['sidebar'] = View::forge('admin/sidebar');
    }

    public function action_index()
    {
        Response::redirect('admin/boards/manage');
    }
}