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
		'group_id' => '0',
		'login_hash' => false,
		'email' => false
	);

	/**
	 * @var  array  value for cli login
	 */
	protected static $cli_login = array(
		'id' => 0,
		'username' => 'cli_admin',
		'group_id' => '100',
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
				$autologin_query = \DC::qb()
					->select('*')
					->from(\DC::p(\Config::get('foolauth.table_autologin_name')), 'la')
					->where('la.login_hash = :login_hash')
					->andWhere('la.expiration > :time')
					->setParameters([':login_hash' => $this->hash_password($autologin_hash), ':time' => time()])
					->execute()
					->fetch();

				if ($autologin_query)
				{
					$this->user = \DC::qb()
						->select('*')
						->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
						->where('l.id = :id')
						->setParameter(':id', $autologin_query['user_id'])
						->execute()
						->fetch();
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

		$this->user = \DC::qb()
			->select('*')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('username = :username')
			->andWhere('password = :password')
			->setParameters([':username' => $username_or_email, ':password' => $password])
			->execute()
			->fetch();

		if ($this->user)
		{
			return $this->user;
		}
		else
		{
			\DC::forge()->insert(\DC::p(\Config::get('foolauth.table_login_attempts_name')), [
				'username' => $username_or_email,
				'ip' => \Input::ip_decimal(),
				'time' => time()
			]);

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
		return \DC::qb()
			->select('COUNT(*) as count')
			->from(\DC::p(\Config::get('foolauth.table_login_attempts_name')), 'lt')
			->where('lt.username = :username')
			->setParameter(':username', $username)
			->execute()
			->fetch()['count'];
	}


	/**
	 * Reset attempts have been made to login
	 *
	 * @param  string $username the submitted username
	 */
	public function reset_attempts($username)
	{
		\DC::qb()
			->delete(\DC::p(\Config::get('foolauth.table_login_attempts_name')))
			->where('username = :username')
			->setParameter(':username', $username)
			->execute();
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

		$this->user = \DC::qb()
			->select('*')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('id = :user_id')
			->setParameter(':user_id', $user_id)
			->execute()
			->fetch();

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
			\DC::qb()
				->delete(\DC::p(\Config::get('foolauth.table_autologin_name')))
				->where('user_id = :user_id')
				->setParameter(':user_id', $this->user['id'])
				->execute();
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

		$same_users = \DC::qb()
			->select('*')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('l.username = :username')
			->orWhere('l.email = :email')
			->setParameters([':username' => $username, ':email' => $email])
			->execute()
			->fetchAll();

		if (count($same_users) > 0)
		{
			if (in_array(strtolower($email), array_map('strtolower', current($same_users))))
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
			'group_id'           => (int) $group,
			'activated'		  => $activated,
			'activation_key'  => $this->hash_password((string) $activation_key),
			'profile_fields'  => serialize($profile_fields),
			'created_at'      => \Date::forge()->get_timestamp()
		);

		$result = \DC::forge()->insert(\DC::p(\Config::get('foolauth.table_name')), $user);

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

		$query = \DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->where('id = :id')
			->setParameter(':id', $this->user['id']);

		foreach ($data as $key => $item)
		{
			$query->set(\DC::forge()->quoteIdentifier($key), \DC::forge()->quote($item));
		}

		$query->execute();

		return true;
	}


	public function get_profile()
	{
		return \DC::qb()
			->select('bio, twitter, display_name')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('id = :id')
			->setParameter(':id', $this->user['id'])
			->execute()
			->fetch();
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
		$affected_rows = \DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->set('activated', ':activated')
			->where('id = :id')
			->andWhere('activation_key = :activation_key')
			->setParameters([':activated' => true, ':id' => $id, ':activation_key' => $this->hash_password($activation_key)])
			->execute();

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
		$affected_rows = \DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->where('id = :id')
			->andWhere('new_password_key = :new_password_key')
			->andWhere('new_password_time > :new_password_time')
			->set('new_password_key', null)
			->set('new_password_time', null)
			->set('password', $this->hash_password($new_password))
			->setParameters([
				'id' => $id,
				'new_password_key' => $this->hash_password($new_password),
				'new_password_time' => time() - 900
			])
			->execute();

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
		$count = \DC::qb()
			->select('COUNT(*) as count')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('id = :id')
			->andWhere('new_password_key = :new_password_key')
			->andWhere('new_password_time > :new_password_time')
			->setParameters([
				'id' => $id,
				'new_password_key' => $this->hash_password($password_key),
				'new_password_time' => time() - 900,
			])
			->execute()
			->fetch()['count'];

		return $count > 0;
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

		$affected_rows = \DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->set('new_password_key', $this->hash_password($new_password_key))
			->set('new_password_time', time())
			->where('email = :email')
			->setParameter(':email', $email)
			->execute();

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
		$check_email = \DC::qb()
			->select('*')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('email = :email')
			->orWhere('(id <> :user_id AND new_email = :new_email)')
			->execute()
			->fetch();

		if ($check_email)
		{
			throw new FoolUserEmailExists;
		}

		$new_email_key = sha1(\Config::get('foolauth.login_hash_salt').$email.time());

		$check_password = \DC::qb()
			->select('*')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('id = :id')
			->andWhere('password = :password')
			->setParameters([':id' => $this->user['id'], ':password' => $this->hash_password($password)])
			->execute()
			->fetch();

		if ( ! $check_password)
		{
			throw new FoolUserWrongPassword;
		}

		\DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->set('new_email', $email)
			->set('new_email_key', $this->hash_password($new_email_key))
			->set('new_email_time', time())
			->where('id = :id')
			->setParameter(':id', $this->user['id'])
			->execute();

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
		$user = \DC::qb()
			->select('*')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('id = :id')
			->andWhere('new_email_key = :new_email_key')
			->andWhere('new_email_time > :new_email_time')
			->setParameters([
				':id' => $id,
				':new_email_key' => $this->hash_password($email_key),
				':new_email_time' => time() - 86400
			])
			->execute()
			->fetch();

		if ( ! $user)
		{
			throw new FoolUserWrongKey;
		}

		\DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->set('email', $user['new_email'])
			->set('new_email', null)
			->set('new_email_key', null)
			->set('new_email_time', null)
			->where('id = :id')
			->setParameter(':id', $id)
			->execute();

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
		$check_password = \DC::qb()
			->select('COUNT(*) as count')
			->from(\DC::p(\Config::get('foolauth.table_name')), 'l')
			->where('id = :id')
			->andWhere('password = :password')
			->setParameters([':id' => $this->user['id'], ':password' => $this->hash_password($password)])
			->execute()
			->fetch()['count'];

		if ( ! $check_password)
		{
			throw new FoolUserWrongPassword;
		}

		$key = sha1(\Config::get('foolauth.login_hash_salt').time());

		\DC::qb()
			->update(\DC::p(\Config::get('foolauth.table_name')))
			->set('deletion_key', $this->hash_password($key))
			->set('deletion_time', time())
			->where('id = :id')
			->setParameter(':id', $this->user['id'])
			->execute();

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
		$affected_rows = \DC::qb()
			->delete(\DC::p(\Config::get('foolauth.table_name')))
			->where('id = :id')
			->andWhere('deletion_key = :deletion_key')
			->andWhere('deletion_time > :deletion_time')
			->setParameters([
				':id' => $id,
				':deletion_key' => $this->hash_password($key),
				':deletion_time' => time() - 900
			])
			->execute();

		if ( ! $affected_rows)
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

		\DC::forge()->insert(\DC::p(\Config::get('foolauth.table_autologin_name')),[
			'user_id' => $this->user['id'],
			'login_hash' => $this->hash_password($login_hash),
			'expiration' => time() + 604800, // 7 days
			'last_ip' => \Input::ip_decimal(),
			'user_agent' => \Input::user_agent(),
			'last_login' => time()
		]);

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

		return array(array('FoolGroup', $this->user['group_id']));
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
