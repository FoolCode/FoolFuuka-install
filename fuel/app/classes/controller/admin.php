<?php

class Controller_Admin extends Controller
{

    public function before()
    {
		if( ! Auth::has_access('admin.logged') && \URI::segment(2) != 'auth')
			Response::redirect('admin/auth/login');
    }

    public function action_index()
    {
        Response::redirect('admin/boards/manage');
    }
}