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
				$data['username']    = Input::post('username');
				$data['login_error'] = 'Wrong username/password combo. Try again';
			}
		}

		// Show the login form
		echo View::forge('auth/login',$data);
    }
	
	public function action_register()
	{}
	
	public function action_validate()
	{}
	
	public function action_forgotten_password()
	{}
		
	public function action_change_password()
	{}
	
	public function action_change_email()
	{}
}