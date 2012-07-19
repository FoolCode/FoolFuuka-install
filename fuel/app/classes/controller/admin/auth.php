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
				$id = Auth::get_user_id();
				Cookie::set('autologin', Session::get('login_hash'));
				DB::insert('user_autologin')->set(array(
					'user_id' => $id[1],
					'login_hash' => Session::get('login_hash'),
					'expiration' => time() + 604800, // 7 days
					'last_ip' => Input::ip_decimal(),
					'user_agent' => Input::user_agent(),
					'last_login' => time()
				))->execute();

				Response::redirect('admin');
			}
			else
			{
				// Oops, no soup for you. try to login again. Set some values to
				// repopulate the username field and give some error text back to the view
				$data['username'] = Input::post('username');
				Notices::set('error', __('Wrong username/password. Try again'));
			}
		}

		// Show the login form
		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Login');
		$this->_views['main_content_view'] = View::forge('admin/auth/login');

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function action_register()
	{
		if (Preferences::get('ff.reg_disabled'))
		{
			throw new HttpNotFoundException;
		}

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
				Notices::set_flash('success', __('The registration was successful.'));
				Response::redirect('admin/auth/login');
			}
			else
			{
				Notices::set('error', $val->error());
			}

		}

		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Register');
		$this->_views['main_content_view'] = View::forge('admin/auth/register');

		return Response::forge(View::forge('admin/default', $this->_views));
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