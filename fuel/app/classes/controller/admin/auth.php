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
			$val->add_field('username', __('Username'), 'required|trim|min_length[4]|max_length[32]');
			$val->add_field('email', __('Email'), 'required|trim|valid_email');
			$val->add_field('password', __('Password'), 'required|min_length[4]|max_length[32]');
			$val->add_field('confirm_password', __('Confirm password'), 'required|match_field[password]');

			if($val->run())
			{
				$input = $val->input();

				list($id, $activation_key) = Auth::create_user($input['username'], $input['password'], $input['email']);

				// activate or send activation email
				if (!$activation_key)
				{
					Notices::set_flash('success', __('The registration was successful.'));
				}
				else
				{
					$from = 'no-reply@'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'no-email-assigned');

					$title = \Preferences::get('ff.gen_site_title').' '.__('account activation');

					$content = \View::Forge('admin/auth/email_activation', array(
						'title' => $title,
						'site' => \Preferences::get('ff.gen_site_title'),
						'username' => $input['username'],
						'activation_link' => Uri::create('admin/auth/activate/'.$id.'/'.$activation_key)
					));

					Package::load('email');
					$email = Email::forge();
					$email->from($from, \Preferences::get('ff.gen_site_title'))
						->subject($title)
						->to($input['email'])
						->html_body(\View::forge('email_default', array('title' => $title, 'content' => $content)));

					try
					{
						$email->send();
					}
					catch(\EmailSendingFailedException $e)
					{
						// The driver could not send the email
						// let's activate it and go on with life
						Auth::activate_user($id, $activation_key);
						Notices::set_flash('success', __('The registration was successful.'));
						Log::error('The system can\'t send the Email. The user '.$input['username'].' was activated automatically not to stop him from using the system.');
						Response::redirect('admin/auth/login');
					}


					Notices::set_flash('success', __('The registration was successful. Check your email to activate your account'));
				}

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


	public function action_activate($id, $activation_key)
	{
		if (Auth::activate_user($id, $activation_key))
		{
			Notices::set_flash('success', __('The activation was successful. You can now login.'));
			Response::redirect('admin/auth/login');
		}

		Notices::set_flash('error', __('It appears that the link was not correct or the activation key expired. Your account was not activated. If more than 48 hours passed, you may have to register again.'));
		Response::redirect('admin/auth/login');
	}


	public function action_forgotten_password()
	{
		if (Input::post())
		{
			$val = Validation::forge('forgotten_password');
			$val->add_field('email', __('Email'), 'required|trim|valid_email');

			if($val->run())
			{
				$input = $val->input();

				try
				{
					list($id, $forgotten_password_key) = Auth::create_forgotten_password_key($input['email']);
				}
				catch (\Auth\FoolUserWrongEmail $e)
				{
					Notices::set_flash('error', __('The email entered is not in the system.'));
					Response::redirect('admin/auth/forgotten_password');
				}

				$from = 'no-reply@'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'no-email-assigned');

				$title = \Preferences::get('ff.gen_site_title').' '.__('password change');

				$content = \View::Forge('admin/auth/email_password_change', array(
					'title' => $title,
					'site' => \Preferences::get('ff.gen_site_title'),
					'username' => $input['username'],
					'password_change_link' => Uri::create('admin/auth/change_password/'.$id.'/'.$forgotten_password_key)
				));

				Package::load('email');
				$email = Email::forge();
				$email->from($from, \Preferences::get('ff.gen_site_title'))
					->subject($title)
					->to($input['email'])
					->html_body(\View::forge('email_default', array('title' => $title, 'content' => $content)));

				try
				{
					$email->send();
				}
				catch(\EmailSendingFailedException $e)
				{
					// The driver could not send the email
					Log::error('The system can\'t send the Email. The user '.$input['username'].' couldn\'t change his password.');
					Response::redirect('admin/auth/login');
				}
			}
		}
	}


	public function action_change_password($id, $password_key)
	{
		if (!Auth::check_new_password_key($id, $password_key))
		{
			Notices::set('warning', __('The link you used is incorrect or expired.'));
		}
	}


	public function action_change_email()
	{

	}

}