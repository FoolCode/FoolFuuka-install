<?php


class Controller_Admin_Auth extends Controller_Admin
{


	public function action_login()
	{
		if (Auth::has_access('maccess.user'))
		{
			Response::redirect('admin');
		}

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
		if (Auth::has_access('maccess.user'))
		{
			Response::redirect('admin');
		}

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

					$title = \Preferences::get('ff.gen.website_title').' '.__('account activation');

					$content = \View::Forge('admin/auth/email_activation', array(
						'title' => $title,
						'site' => \Preferences::get('ff.gen.website_title'),
						'username' => $input['username'],
						'link' => Uri::create('admin/auth/activate/'.$id.'/'.$activation_key)
					));

					Package::load('email');
					$email = Email::forge();
					$email->from($from, \Preferences::get('ff.gen.website_title'))
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
		if (Auth::has_access('maccess.user'))
		{
			Response::redirect('admin');
		}

		if (Auth::activate_user($id, $activation_key))
		{
			Notices::set_flash('success', __('The activation was successful. You can now login.'));
			Response::redirect('admin/auth/login');
		}

		Notices::set_flash('error', __('It appears that the link was not correct or the activation key expired. Your account was not activated. If more than 48 hours passed, you may have to register again.'));
		Response::redirect('admin/auth/login');
	}


	public function action_forgot_password()
	{
		if (Auth::has_access('maccess.user'))
		{
			Response::redirect('admin');
		}

		if (Input::post())
		{
			$val = Validation::forge('forgotten_password');
			$val->add_field('email', __('Email'), 'required|trim|valid_email');

			if($val->run())
			{
				$input = $val->input();

				return $this->send_change_password_email($input['email']);
			}
		}

		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Forgot Password');
		$this->_views['main_content_view'] = View::forge('admin/auth/forgot_password');

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function action_change_password($id, $password_key)
	{
		if (Auth::check_new_password_key($id, $password_key))
		{
			if (Input::post())
			{
				$val = Validation::forge('forgotten_password');
				$val->add_field('password', __('Password'), 'required|min_length[4]|max_length[32]');
				$val->add_field('confirm_password', __('Confirm password'), 'required|match_field[password]');

				if($val->run())
				{
					$input = $val->input();

					try
					{
						Auth::change_password($id, $password_key, $input['password']);
						Response::redirect('admin/auth/login');
					}
					catch (\Auth\FoolUserWrongKey $e)
					{
						Notices::set('warning', __('The link you used is incorrect or has expired.'));
					}
				}
				else
				{
					Notices::set('error', implode(' ', $val->error()));
				}
			}
			else
			{
				$this->_views['main_content_view'] = View::forge('admin/auth/change_password');
			}

		}
		else
		{
			Notices::set('warning', __('The link you used is incorrect or has expired.'));
		}

		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Forgot Password');

		return Response::forge(View::forge('admin/default', $this->_views));
	}

	/**
	 * Change password for registered users, will send a password change email
	 *
	 * @param type $id
	 * @param type $password_key
	 */
	public function action_change_password_request()
	{
		if (!Auth::has_access('maccess.user'))
		{
			Response::redirect('admin');
		}

		if (Input::post())
		{
			return $this->send_change_password_email(Auth::get_email());
		}

		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Forgot Password');
		$this->_views['main_content_view'] = View::forge('admin/auth/change_password_request');

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function action_change_email_request()
	{
		if ( ! Auth::has_access('maccess.user'))
		{
			Response::redirect('admin/auth/login');
		}

		if (Input::post())
		{
			$val = Validation::forge('change_password');
			$val->add_field('password', __('Password'), 'required');
			$val->add_field('email', __('Email'), 'required|trim|valid_email');

			if($val->run())
			{
				$input = $val->input();

				try
				{
					$change_email_key = Auth::create_change_email_key($input['email'], $input['password']);
				}
				catch (\Auth\FoolUserWrongPassword $e)
				{
					Notices::set_flash('error', __('The password doesn\'t match your account password.'));
					Response::redirect('admin/auth/change_email_request');
				}
				catch (\Auth\FoolUserEmailExists $e)
				{
					Notices::set_flash('error', __('The email already exists in the system and can\'t be used again.'));
					Response::redirect('admin/auth/change_email_request');
				}

				$user = Users::get_user();

				$from = 'no-reply@'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'no-email-assigned');

				$title = \Preferences::get('ff.gen.website_title').' '.__('email change');

				$content = \View::Forge('admin/auth/email_email_change', array(
					'title' => $title,
					'site' => \Preferences::get('ff.gen.website_title'),
					'username' => $user->username,
					'link' => Uri::create('admin/auth/change_email/'.$user->id.'/'.$change_email_key)
				));

				Package::load('email');
				$sending = Email::forge();
				$sending->from($from, \Preferences::get('ff.gen.website_title'))
					->subject($title)
					->to($input['email'])
					->html_body(\View::forge('email_default', array('title' => $title, 'content' => $content)));

				try
				{
					$sending->send();
					Notices::set_flash('success', __('The email change email has been sent. The link included will work for 24 hours.'));
				}
				catch(\EmailSendingFailedException $e)
				{
					// The driver could not send the email
					Notices::set_flash('error', __('There was an error and the system couldn\'t send the email change email.'));
					Log::error('The system can\'t send the Email. The user '.$user->username.' couldn\'t change his email.');
				}

				Response::redirect('admin/auth/login');

			}
			else
			{
				Notices::set('error', implode(' ', $val->error()));
			}
		}

		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Change Email Request');
		$this->_views['main_content_view'] = View::forge('admin/auth/change_email_request');

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function action_change_email($id, $email_key)
	{
		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Change Email');

		if ( ! Auth::has_access('maccess.user'))
		{
			Response::redirect('admin/auth/login');
		}

		try
		{
			Auth::change_email($id, $email_key);
			Notices::set_flash('success', __('Your new email has been activated'));
			Response::redirect();
		}
		catch (\Auth\FoolUserWrongKey $e)
		{
			Notices::set('warning', __('The link you used is incorrect or has expired.'));
		}

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function action_delete_account_request()
	{
		if ( ! Auth::has_access('maccess.user'))
		{
			Response::redirect('admin/auth/login');
		}

		if (Input::post())
		{
			$val = Validation::forge('change_password');
			$val->add_field('password', __('Password'), 'required');

			if ($val->run())
			{

				$input = $val->input();

				try
				{
					$account_deletion_key = Auth::create_account_deletion_key($input['password']);
				}
				catch (\Auth\FoolUserWrongPassword $e)
				{
					Notices::set_flash('error', __('The password doesn\'t match your account password.'));
					Response::redirect('admin/auth/delete_account_request');
				}

				$user = Users::get_user();

				$from = 'no-reply@'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'no-email-assigned');

				$title = \Preferences::get('ff.gen.website_title').' '.__('account deletion');

				$content = \View::Forge('admin/auth/email_delete_account', array(
					'title' => $title,
					'site' => \Preferences::get('ff.gen.website_title'),
					'username' => $user->username,
					'link' => Uri::create('admin/auth/delete_account/'.$user->id.'/'.$account_deletion_key)
				));

				Package::load('email');
				$sending = Email::forge();
				$sending->from($from, \Preferences::get('ff.gen.website_title'))
					->subject($title)
					->to($user->email)
					->html_body(\View::forge('email_default', array('title' => $title, 'content' => $content)));

				try
				{
					$sending->send();
					Notices::set_flash('success', __('The account deletion email has been sent. The link included will work for 15 minutes.'));
				}
				catch(\EmailSendingFailedException $e)
				{
					// The driver could not send the email
					Notices::set_flash('error', __('There was an error and the system couldn\'t send the account deletion email.'));
					Log::error('The system can\'t send the Email. The user '.$user->username.' couldn\'t delete his account.');
				}

				Response::redirect('admin/auth/delete_account_request');
			}
			else
			{
				Notices::set('error', implode(' ', $val->error()));
			}

		}

		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Account Deletion Request');
		$this->_views['main_content_view'] = View::forge('admin/auth/delete_account_request');

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function action_delete_account($id, $key)
	{
		$this->_views['controller_title'] = __('Authorization');
		$this->_views['method_title'] = __('Delete Account');

		if (!Auth::has_access('maccess.user'))
		{
			Notices::set('warning', __('You must be logged in to delete your account with this link.'));

			return Response::forge(View::forge('admin/default', $this->_views));
		}

		try
		{
			Auth::delete_account($id, $key);
			Notices::set('success', __('Your account has been deleted.'));
		}
		catch (\Auth\FoolUserWrongKey $e)
		{
			Notices::set('warning', __('The link you used is incorrect or has expired.'));
		}

		return Response::forge(View::forge('admin/default', $this->_views));
	}


	public function send_change_password_email($email)
	{
		try
		{
			$password_key = Auth::create_forgotten_password_key($email);
		}
		catch (\Auth\FoolUserWrongEmail $e)
		{
			Notices::set_flash('error', __('The email entered is not in the system.'));
			Response::redirect('admin/auth/forgotten_password');
		}

		$user = Users::get_user_by('email', $email);

		$from = 'no-reply@'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'no-email-assigned');

		$title = \Preferences::get('ff.gen.website_title').' '.__('password change');

		$content = \View::Forge('admin/auth/email_password_change', array(
			'title' => $title,
			'site' => \Preferences::get('ff.gen.website_title'),
			'username' => $user->username,
			'link' => Uri::create('admin/auth/change_password/'.$user->id.'/'.$password_key)
		));

		Package::load('email');
		$sending = Email::forge();
		$sending->from($from, \Preferences::get('ff.gen.website_title'))
			->subject($title)
			->to($email)
			->html_body(\View::forge('email_default', array('title' => $title, 'content' => $content)));

		try
		{
			$sending->send();
			Notices::set_flash('success', __('The password change email has been sent. The link included will work for 15 minutes.'));
		}
		catch(\EmailSendingFailedException $e)
		{
			// The driver could not send the email
			Notices::set_flash('error', __('There was an error and the system couldn\'t send the password change email.'));
			Log::error('The system can\'t send the Email. The user '.$user->username.' couldn\'t change his password.');
		}

		Auth::logout();
		Response::redirect('admin/auth/login');
	}


	public function action_profile()
	{
		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['paragraph'] = array(
			'type' => 'paragraph',
			'help' => __('You can customize your account here.')
		);

		$form['paragraph-2'] = array(
			'type' => 'paragraph',
			'help' => '<img src="'.Gravatar::get_gravatar(Auth::get_email()).'" width="80" height="80" style="padding:2px; border: 1px solid #ccc;"/> '.
				Str::tr(__('Your avatar is automatically fetched from :gravatar, based on your registration email.'),
				array('gravatar' => '<a href="http://gravatar.com" target="_blank">Gravatar</a>'))
		);

		$form['bio'] = array(
			'type' => 'textarea',
			'database' => true,
			'label' => 'Bio',
			'style' => 'height:150px;',
			'class' => 'span5',
			'help' => __('Some details about you'),
			'validation' => 'trim|max_length[360]'
		);

		$form['twitter'] = array(
			'type' => 'input',
			'database' => true,
			'label' => 'Twitter',
			'class' => 'span3',
			'help' => __('Your twitter nickname'),
			'validation' => 'trim|max_length[32]'
		);

		$form['display_name'] = array(
			'type' => 'input',
			'database' => true,
			'label' => 'Display name',
			'class' => 'span3',
			'help' => __('Alternative name in place of login username'),
			'validation' => 'trim|max_length[32]'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'class' => 'btn btn-primary',
			'value' => __('Submit')
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$data['form'] = $form;

		if (Input::post())
		{
			$result = \Validation::form_validate($form);

			if (isset($result['error']))
			{
				\Notices::set('warning', $result['error']);
			}
			else
			{
				if (isset($result['warning']))
				{
					\Notices::set('warning', $result['warning']);
				}

				\Notices::set('success', __('Preferences updated.'));

				Auth::update_profile($result['success']);
			}
		}

		$data['object'] = (object) Auth::get_profile();

		// create a form
		$this->_views["controller_title"] = __('Authentication');
		$this->_views["method_title"] = __('Profile');
		$this->_views["main_content_view"] = View::forge('admin/form_creator', $data);
		return Response::forge(View::forge('admin/default', $this->_views));
	}

}