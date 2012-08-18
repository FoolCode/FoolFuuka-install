<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Auth;


class FoolUserUpdateException extends \FuelException {}

class FoolUserWrongUsernameOrPassword extends \FuelException {}
class FoolUserLimitExceeded extends \FuelException {}
class FoolUserWrongPassword extends \FuelException {}
class FoolUserWrongEmail extends \FuelException {}
class FoolUserWrongKey extends \FuelException {}
class FoolUserEmailExists extends \FuelException {}

/**
 * FoolAuth basic login driver
 *
 * @package     Fuel
 * @subpackage  Auth
 */
class Auth_Login_FoolAuth extends \Auth_Login_Driver
{

	public static function _init()
	{
		\Config::load('foolauth', true, true, true);
	}

	/**
	 * @var  Database_Result  when login succeeded
	 */
	protected $user = null;

	/**
	 * @var  array  value for guest login
	 */
	protected static $guest_login = array(
		'id' => 0,
		'username' => 'guest',
		'group' => '0',
		'login_hash' => false,
		'email' => false
	);
	
	/**
	 * @var  array  value for cli login
	 */
	protected static $cli_login = array(
		'id' => 0,
		'username' => 'cli_admin',
		'group' => '100',
		'login_hash' => false,
		'email' => false
	);

	/**
	 * @var  array  FoolAuth class config
	 */
	protected $config = array(
		'drivers' => array('group' => array('FoolGroup')),
		'additional_fields' => array('profile_fields'),
	);

	/**
	 * Check for login
	 *
	 * @return  bool
	 */
	protected function perform_check()
	{
		if (PHP_SAPI === 'cli')
		{
			$this->user = static::$cli_login;
			return true;
		}
		
		$autologin_hash = \Cookie::get('autologin');

		if ( ! empty($autologin_hash))
		{
			if (is_null($this->user) and $this->user != static::$guest_login)
			{
				$autologin_query = \DB::select('*')
					->from(\Config::get('foolauth.table_autologin_name'))
					->where('login_hash', '=', $this->hash_password($autologin_hash))
					->and_where('expiration', '>', time())
					->execute(\Config::get('foolauth.db_connection'))->current();

				if ($autologin_query)
				{
					$this->user = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
						->where('id', '=', $autologin_query['user_id'])
						->from(\Config::get('foolauth.table_name'))
						->execute(\Config::get('foolauth.db_connection'))->current();
				}

				// return true when login was verified
				if ($this->user)
				{
					return true;
				}
			}
		}

		// no valid login when still here, ensure empty session and optionally set guest_login
		$this->user = \Config::get('foolauth.guest_login', true) ? static::$guest_login : false;
		//\Cookie::delete('autologin');

		return false;
	}

	/**
	 * Check the user exists before logging in
	 *
	 * @return  bool
	 */
	public function validate_user($username_or_email = '', $password = '')
	{
		$username_or_email = trim($username_or_email) ?: trim(\Input::post(\Config::get('foolauth.username_post_key', 'username')));
		$password = trim($password) ?: trim(\Input::post(\Config::get('foolauth.password_post_key', 'password')));

		if (empty($username_or_email) or empty($password))
		{
			throw new FoolUserWrongUsernameOrPassword;
		}

		if ($this->count_attempts($username_or_email) >= \Config::get('foolauth.attempts_to_lock'))
		{
			throw new FoolUserLimitExceeded;
		}

		$password = $this->hash_password($password);
		$this->user = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
			->where_open()
			->where('username', '=', $username_or_email)
		//	->or_where('email', '=', $username_or_email) // @todo get rid of email login or fix it
			->where_close()
			->where('password', '=', $password)
			->from(\Config::get('foolauth.table_name'))
			->execute(\Config::get('foolauth.db_connection'))->current();

		if ($this->user)
		{
			return $this->user;
		}
		else
		{
			\DB::insert(\Config::get('foolauth.table_login_attempts_name'))
				->set(array(
					'username' => $username_or_email,
					'ip' => \Input::ip_decimal(),
					'time' => time()
				))
				->execute(\Config::get('foolauth.db_connection'));

			throw new FoolUserWrongUsernameOrPassword;
		}
	}


	/**
	 * Checks how many attempts have been made to login
	 *
	 * @param  string $username the submitted username
	 * @return int the amount of attempts before successful login
	 */
	public function count_attempts($username)
	{
		return \DB::select(\DB::expr('COUNT(*) as count'))
			->from(\Config::get('foolauth.table_login_attempts_name'))
			->where('username', '=', $username)
			->as_object()
			->execute(\Config::get('foolauth.db_connection'))
			->current()
			->count;
	}


	/**
	 * Reset attempts have been made to login
	 *
	 * @param  string $username the submitted username
	 */
	public function reset_attempts($username)
	{
		\DB::delete(\Config::get('foolauth.table_login_attempts_name'))
			->where('username', $username)
			->execute(\Config::get('foolauth.db_connection'));
	}


	/**
	 * Login user
	 *
	 * @param   string
	 * @param   string
	 * @return  bool
	 */
	public function login($username_or_email = '', $password = '')
	{
		try
		{
			$this->user = $this->validate_user($username_or_email, $password);
		}
		catch (FoolUserWrongUsernameOrPassword $e)
		{
			$this->user = \Config::get('foolauth.guest_login', true) ? static::$guest_login : false;
			\Cookie::delete('autologin');

			throw new FoolUserWrongUsernameOrPassword;
		}
		catch (FoolUserLimitExceeded $e)
		{
			$this->user = \Config::get('foolauth.guest_login', true) ? static::$guest_login : false;
			\Cookie::delete('autologin');

			throw new FoolUserLimitExceeded;
		}

		$this->reset_attempts($this->user['username']);

		\Cookie::set('autologin', $this->create_login_hash());

		return true;
	}

	/**
	 * Force login user
	 *
	 * @param   string
	 * @return  bool
	 */
	public function force_login($user_id = '')
	{
		if (empty($user_id))
		{
			return false;
		}

		$this->user = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
			->where_open()
			->where('id', '=', $user_id)
			->where_close()
			->from(\Config::get('foolauth.table_name'))
			->execute(\Config::get('foolauth.db_connection'))
			->current();

		if ($this->user == false)
		{
			$this->user = \Config::get('foolauth.guest_login', true) ? static::$guest_login : false;
			\Cookie::set('autologin');
			return false;
		}

		\Cookie::set('autologin', $this->create_login_hash());
		return true;
	}

	/**
	 * Logout user
	 *
	 * @param   bool $all delete all autologins so it's logged out from every device
	 * @return  bool
	 */
	public function logout($all = false)
	{
		if ($all)
		{
			\DB::delete(\Config::get('foolauth.table_autologin_name'))
				->where('user_id', '=', $this->user['id'])
				->execute(\Config::get('foolauth.db_connection'));
		}

		$this->user = \Config::get('foolauth.guest_login', true) ? static::$guest_login : false;
		\Cookie::delete('autologin');
		return true;
	}

	/**
	 * Create new user
	 *
	 * @param   string
	 * @param   string
	 * @param   string  must contain valid email address
	 * @param   int     group id
	 * @param   Array
	 * @return  bool
	 */
	public function create_user($username, $password, $email, $group = 1, Array $profile_fields = array())
	{
		$password = trim($password);
		$email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

		if (empty($username) or empty($password) or empty($email))
		{
			throw new \FoolUserUpdateException('Username, password and email address can\'t be empty.', 1);
		}

		$same_users = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
			->where('username', '=', $username)
			->or_where('email', '=', $email)
			->from(\Config::get('foolauth.table_name'))
			->execute(\Config::get('foolauth.db_connection'));

		if ($same_users->count() > 0)
		{
			if (in_array(strtolower($email), array_map('strtolower', $same_users->current())))
			{
				throw new \FoolUserUpdateException('Email address already exists', 2);
			}
			else
			{
				throw new \FoolUserUpdateException('Username already exists', 3);
			}
		}

		$activated = (bool) \Preferences::get('ff.reg_email_disabled');
		$activation_key = '';

		if (!$activated)
		{
			// get a string for validation email
			$activation_key = \Str::random('sha1');
		}

		$user = array(
			'username'        => (string) $username,
			'password'        => $this->hash_password((string) $password),
			'email'           => $email,
			'group'           => (int) $group,
			'activated'		  => $activated,
			'activation_key'  => $this->hash_password((string) $activation_key),
			'profile_fields'  => serialize($profile_fields),
			'created_at'      => \Date::forge()->get_timestamp()
		);
		$result = \DB::insert(\Config::get('foolauth.table_name'))
			->set($user)
			->execute(\Config::get('foolauth.db_connection'));

		return ($result[1] > 0) ? array($result[0], $activation_key) : false;
	}

	/**
	 * Update the available columns for profile
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function update_profile(Array $data)
	{
		// select only what we can insert
		$data = \Arr::filter_keys($data, array('bio', 'twitter', 'display_name'));

		\DB::update(\Config::get('foolauth.table_name'))
			->where('id', '=', $this->user['id'])
			->set($data)
			->execute(\Config::get('foolauth.db_connection'));

		return true;
	}


	public function get_profile()
	{
		return \DB::select_array(array('bio', 'twitter', 'display_name'))
			->from(\Config::get('foolauth.table_name'))
			->where('id', '=', $this->user['id'])
			->execute(\Config::get('foolauth.db_connection'))
			->current();
	}


	/**
	 * Activates the user account
	 *
	 * @param   string $id
	 * @param   string $activation_key
	 * @return  bool
	 */
	public function activate_user($id, $activation_key)
	{
		// try activating
		$affected_rows = \DB::update(\Config::get('foolauth.table_name'))
			->where('id', $id)
			->where('activation_key', '=', $this->hash_password($activation_key))
			->value('activated', 1)
			->execute(\Config::get('foolauth.db_connection'));

		return $affected_rows ? true : false;
	}

	/**
	 * Change a user's password with id and password_key
	 *
	 * @param   string
	 * @param   string
	 * @param   string  username or null for current user
	 * @return  bool
	 */
	public function change_password($id, $password_key, $new_password)
	{
		$affected_rows = \DB::update(\Config::get('foolauth.table_name'))
			->where('id', '=', $id)
			->where('new_password_key', '=', $this->hash_password($password_key))
			->where('new_password_time', '>', time() - 900) // user has 15 minutes to change the password
			->set(array('new_password_key' => null, 'new_password_time' => null, 'password' => $this->hash_password($new_password)))
			->execute(\Config::get('foolauth.db_connection'));

		if ( ! $affected_rows)
		{
			throw new FoolUserWrongKey;
		}

		$this->logout();
		$this->force_login($id);
		$this->reset_attempts($this->user['username']);

		return true;
	}


	/**
	 * Checks if the pair id/password_key is valid without altering rows
	 *
	 * @param   int     $id
	 * @param   string  $password_key
	 * @return  bool
	 */
	public function check_new_password_key($id, $password_key)
	{
		$query = \DB::select()->from(\Config::get('foolauth.table_name'))
			->where('id', '=', $id)
			->where('new_password_key', '=', $this->hash_password($password_key))
			->where('new_password_time', '>', time() - 900)
			->execute(\Config::get('foolauth.db_connection'));

		return count($query) > 0;
	}


	/**
	 * Generates a code for reaching the change password page
	 *
	 * @param   string  $email
	 * @return  string
	 */
	public function create_forgotten_password_key($email)
	{
		$new_password_key = sha1(\Config::get('foolauth.login_hash_salt').$email.time());

		$affected_rows = \DB::update(\Config::get('foolauth.table_name'))->where('email', $email)->set(array(
				'new_password_key' => $this->hash_password($new_password_key),
				'new_password_time' => time(),
			))->execute(\Config::get('foolauth.db_connection'));

		if ( ! $affected_rows)
		{
			throw new FoolUserWrongEmail;
		}

		return $new_password_key;
	}


	/**
	 * Generates a code for confirming email change
	 *
	 * @param   string  $email
	 * @return  string
	 */
	public function create_change_email_key($email, $password)
	{
		$check_email = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->where('email', '=', $email)
			->or_where_open()
			->where('id', '<>', $this->user['id'])
			->where('new_email', '=', $email)
			->where_close()
			->execute(\Config::get('foolauth.db_connection'));

		if (count($check_email))
		{
			throw new FoolUserEmailExists;
		}

		$new_email_key = sha1(\Config::get('foolauth.login_hash_salt').$email.time());

		$check_password = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->where('id', '=', $this->user['id'])
			->where('password', '=', $this->hash_password($password))
			->execute(\Config::get('foolauth.db_connection'));

		if ( ! count($check_password))
		{
			throw new FoolUserWrongPassword;
		}

		\DB::update(\Config::get('foolauth.table_name'))
			->where('id', '=', $this->user['id'])
			->set(array(
				'new_email' => $email,
				'new_email_key' => $this->hash_password($new_email_key),
				'new_email_time' => time(),
			))->execute(\Config::get('foolauth.db_connection'));


		return $new_email_key;
	}

	/**
	 * Checks if the pair id/password_key is valid without altering rows
	 *
	 * @param   int     $id
	 * @param   string  $email_key
	 * @return  bool
	 */
	public function	change_email($id, $email_key)
	{
		$user = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->where('id', '=', $id)
			->where('new_email_key', '=', $this->hash_password($email_key))
			->where('new_email_time', '>', time() - 86400)
			->execute(\Config::get('foolauth.db_connection'));

		if ( ! count($user))
		{
			throw new FoolUserWrongKey;
		}

		$user = $user->current();

		\DB::update(\Config::get('foolauth.table_name'))
			->where('id', '=', $id)
			->set(array(
				'email' => $user['new_email'],
				'new_email' => null,
				'new_email_key' => null,
				'new_email_time' => null,
			))->execute(\Config::get('foolauth.db_connection'));

		$this->logout();
		$this->force_login($id);

		return true;
	}


	/**
	 * Generates a code for confirming account deletion
	 *
	 * @param   string  $email
	 * @return  string
	 */
	public function create_account_deletion_key($password)
	{

		$check_password = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->where('id', '=', $this->user['id'])
			->where('password', '=', $this->hash_password($password))
			->execute(\Config::get('foolauth.db_connection'));

		if ( ! count($check_password))
		{
			throw new FoolUserWrongPassword;
		}

		$key = sha1(\Config::get('foolauth.login_hash_salt').time());

		\DB::update(\Config::get('foolauth.table_name'))
			->where('id', '=', $this->user['id'])
			->set(array(
				'deletion_key' => $this->hash_password($key),
				'deletion_time' => time(),
			))->execute(\Config::get('foolauth.db_connection'));

		return $key;
	}


	/**
	 * Deletes a given user
	 *
	 * @param   string
	 * @param   string
	 * @return  bool
	 */
	public function delete_account($id, $key)
	{
		$affected_rows = \DB::delete(\Config::get('foolauth.table_name'))
			->where('id', '=', $id)
			->where('deletion_key', '=', $this->hash_password($key))
			->where('deletion_time', '>', time() - 900)
			->execute(\Config::get('foolauth.db_connection'));


		if ($affected_rows < 1)
		{
			throw new FoolUserWrongKey;
		}

		$this->logout();

		return true;
	}

	/**
	 * Creates a temporary hash that will validate the current login
	 *
	 * @return  string
	 */
	public function create_login_hash()
	{
		if (empty($this->user))
		{
			throw new \FoolUserUpdateException('User not logged in, can\'t create login hash.', 10);
		}

		$last_login = \Date::forge()->get_timestamp();
		$login_hash = sha1(\Config::get('foolauth.login_hash_salt').$this->user['username'].$last_login);

		// autologin garbage collection
		if (time() % 25 == 0)
		{
			\DB::delete(\Config::get('foolauth.table_autologin_name'))->where('expiration', '<', time())->execute();
		}

		\DB::insert(\Config::get('foolauth.table_autologin_name'))->set(array(
			'user_id' => $this->user['id'],
			'login_hash' => $this->hash_password($login_hash),
			'expiration' => time() + 604800, // 7 days
			'last_ip' => \Input::ip_decimal(),
			'user_agent' => \Input::user_agent(),
			'last_login' => time()
		))->execute(\Config::get('foolauth.db_connection'));

		\Cookie::set('autologin', $login_hash);

		return $login_hash;
	}

	/**
	 * Get the user's ID
	 *
	 * @return  Array  containing this driver's ID & the user's ID
	 */
	public function get_user_id()
	{
		if (empty($this->user))
		{
			return false;
		}

		return array($this->id, (int) $this->user['id']);
	}

	/**
	 * Get the user's groups
	 *
	 * @return  Array  containing the group driver ID & the user's group ID
	 */
	public function get_groups()
	{
		if (empty($this->user))
		{
			return false;
		}

		return array(array('FoolGroup', $this->user['group']));
	}

	/**
	 * Get the user's emailaddress
	 *
	 * @return  string
	 */
	public function get_email()
	{
		if (empty($this->user))
		{
			return false;
		}

		return $this->user['email'];
	}

	/**
	 * Get the user's screen name
	 *
	 * @return  string
	 */
	public function get_screen_name()
	{
		if (empty($this->user))
		{
			return false;
		}

		return $this->user['username'];
	}


	/**
	 * Get the user's activation code
	 *
	 * @return  string
	 */
	public function get_activation_key()
	{
		if (empty($this->user))
		{
			return false;
		}

		return $this->user['activation_key'];
	}

	/**
	 * Get the user's profile fields
	 *
	 * @return  Array
	 */
	public function get_profile_fields()
	{
		if (empty($this->user))
		{
			return false;
		}

		if (isset($this->user['profile_fields']))
		{
			is_array($this->user['profile_fields']) or $this->user['profile_fields'] = @unserialize($this->user['profile_fields']);
		}
		else
		{
			$this->user['profile_fields'] = array();
		}

		return $this->user['profile_fields'];
	}

	/**
	 * Extension of base driver method to default to user group instead of user id
	 */
	public function has_access($condition, $driver = null, $user = null)
	{
		if (is_null($user))
		{
			$groups = $this->get_groups();
			$user = reset($groups);
		}

		return parent::has_access($condition, $driver, $user);
	}

	/**
	 * Extension of base driver because this supports a guest login when switched on
	 */
	public function guest_login()
	{
		return \Config::get('foolauth.guest_login', true);
	}

}

// end of file foolauth.php
