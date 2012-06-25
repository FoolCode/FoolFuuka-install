<?php


class Controller_Admin_Auth extends Controller_Admin
{


	public function action_login()
	{
		$data = array();

		// If so, you pressed the submit button. let's go over the steps
		if (Input::post())
		{
			// first of all, let's get a auth object
			$auth = Auth::instance();

			// check the credentials. This assumes that you have the table created and
			// you have used the table definition and configuration as mentioned above.
			if ($auth->login())
			{
				// credentials ok, go right in
				Response::redirect('admin');
			}
			else
			{
				// Oops, no soup for you. try to login again. Set some values to
				// repopulate the username field and give some error text back to the view
				$data['username'] = Input::post('username');
				Notice::set('error', __('Wrong username/password. Try again'));
			}
		}

		// Show the login form
		$data['controller_title'] = __('Authorization');
		$data['navbar'] = View::forge('admin/navbar', $data);
		$data['sidebar'] = View::forge('admin/sidebar', $data);
		$data['main_content_view'] = View::forge('admin/auth/login', $data);

		return Response::forge(View::forge('admin/default', $data));
	}


	public function action_register()
	{
		if (Input::post())
		{
			$val = Validation::forge('register');
			$val->add_field('username', __('Username'), 'required|min_length[4]|max_length[32]');
			$val->add_field('email', __('Email'), 'required|valid_email');
			$val->add_field('password', __('Password'), 'required|min_length[4]|max_length[32]');
			$val->add_field('confirm_password', __('Confirm password'), 'required|match_field[password]');

			if($val->run())
			{
				Auth::create_user(Input::post('username'), Input::post('password'), Input::post('email'));
				Notice::set('success', __('The registration was successful.'));
			}
			else
			{
				Notice::set('error', $val->error());
			}

		}

		$data['controller_title'] = __('Authorization');
		$data['navbar'] = View::forge('admin/navbar', $data);
		$data['sidebar'] = View::forge('admin/sidebar', $data);


		$data['main_content_view'] = View::forge('admin/auth/register', $data);

		return Response::forge(View::forge('admin/default', $data));
	}


	public function action_validate()
	{

	}


	public function action_forgotten_password()
	{

	}


	public function action_change_password()
	{

	}


	public function action_change_email()
	{

	}

}