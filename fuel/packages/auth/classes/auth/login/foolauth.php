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
			return false;
		}

		$password = $this->hash_password($password);
		$this->user = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
			->where_open()
			->where('username', '=', $username_or_email)
			->or_where('email', '=', $username_or_email)
			->where_close()
			->where('password', '=', $password)
			->from(\Config::get('foolauth.table_name'))
			->execute(\Config::get('foolauth.db_connection'))->current();

		return $this->user ?: false;
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
		if ( ! ($this->user = $this->validate_user($username_or_email, $password)))
		{
			$this->user = \Config::get('foolauth.guest_login', true) ? static::$guest_login : false;
			\Cookie::delete('autologin');
			return false;
		}

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
	 * @return  bool
	 */
	public function logout()
	{
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
			$activation_key = Str::random('sha1');
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
	 * Update a user's properties
	 * Note: Username cannot be updated, to update password the old password must be passed as old_password
	 *
	 * @param   Array  properties to be updated including profile fields
	 * @param   string
	 * @return  bool
	 */
	public function update_user($values, $username = null)
	{
		$username = $username ?: $this->user['username'];
		$current_values = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
			->where('username', '=', $username)
			->from(\Config::get('foolauth.table_name'))
			->execute(\Config::get('foolauth.db_connection'));

		if (empty($current_values))
		{
			throw new \FoolUserUpdateException('Username not found', 4);
		}

		$update = array();
		if (array_key_exists('username', $values))
		{
			throw new \FoolUserUpdateException('Username cannot be changed.', 5);
		}
		if (array_key_exists('password', $values))
		{
			if (empty($values['old_password'])
				or $current_values->get('password') != $this->hash_password(trim($values['old_password'])))
			{
				throw new \FoolUserWrongPassword('Old password is invalid');
			}

			$password = trim(strval($values['password']));
			if ($password === '')
			{
				throw new \FoolUserUpdateException('Password can\'t be empty.', 6);
			}
			$update['password'] = $this->hash_password($password);
			unset($values['password']);
		}
		if (array_key_exists('old_password', $values))
		{
			unset($values['old_password']);
		}
		if (array_key_exists('email', $values))
		{
			$email = filter_var(trim($values['email']), FILTER_VALIDATE_EMAIL);
			if ( ! $email)
			{
				throw new \FoolUserUpdateException('Email address is not valid', 7);
			}
			$update['email'] = $email;
			unset($values['email']);
		}
		if (array_key_exists('group', $values))
		{
			if (is_numeric($values['group']))
			{
				$update['group'] = (int) $values['group'];
			}
			unset($values['group']);
		}
		if ( ! empty($values))
		{
			$profile_fields = @unserialize($current_values->get('profile_fields')) ?: array();
			foreach ($values as $key => $val)
			{
				if ($val === null)
				{
					unset($profile_fields[$key]);
				}
				else
				{
					$profile_fields[$key] = $val;
				}
			}
			$update['profile_fields'] = serialize($profile_fields);
		}

		$affected_rows = \DB::update(\Config::get('foolauth.table_name'))
			->set($update)
			->where('username', '=', $username)
			->execute(\Config::get('foolauth.db_connection'));

		// Refresh user
		if ($this->user['username'] == $username)
		{
			$this->user = \DB::select_array(\Config::get('foolauth.table_columns', array('*')))
				->where('username', '=', $username)
				->from(\Config::get('foolauth.table_name'))
				->execute(\Config::get('foolauth.db_connection'))->current();
		}

		return $affected_rows > 0;
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
	public function change_email($id, $email_key)
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

		return true;
	}

	/**
	 * Deletes a given user
	 *
	 * @param   string
	 * @return  bool
	 */
	public function delete_user($username)
	{
		if (empty($username))
		{
			throw new \FoolUserUpdateException('Cannot delete user with empty username', 9);
		}

		$affected_rows = \DB::delete(\Config::get('foolauth.table_name'))
			->where('username', '=', $username)
			->execute(\Config::get('foolauth.db_connection'));

		return $affected_rows > 0;
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
