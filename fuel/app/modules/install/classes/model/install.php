<?php

namespace Install\Model;

class Install extends \Model
{

	/**
	 * Checks a few basic requirements to run the framework
	 */
	public static function check_system()
	{
		$checked = array();

		$checked['php'] = array(
			'string' => \Str::tr(__('Checking if PHP :version or newer is available. PHP is the engine running the system.'), array(
				'version' => \Config::get('foolframe.install.requirements.min_php_version'
			))),
			'not_available_string' => __('Without a compatible version of PHP, you\'re likely to run into compilation errors. You must upgrade PHP.'),
			'result' => (version_compare(PHP_VERSION, \Config::get('foolframe.install.requirements.min_php_version')) >= 0),
		);

		$checked['finfo'] = array(
			'string' => __('Checking if FileInfo is available. FileInfo allows discovering the format of files from their contents.'),
			'not_available_string' => __('FileInfo is necessary for checking validity of uploads. PHP 5.3 and newer usually have it inbuilt, but in some cases you may have to install it through your OS\'s packaging system or by adding a DLL.'),
			'result' => function_exists('finfo_file'),
		);

		$checked['mbstring'] = array(
			'string' => __('Checking if multibyte support is available. Multibyte is necessary for correctly managing strings with special characters.'),
			'not_available_string' => __('Without multibyte support, asian and special characters may make the system unstable. You must install it with PHP or through your OS\'s packaging system.'),
			'result' => defined('MBSTRING'),
		);

		return $checked;
	}


	public static function check_database($array)
	{
		switch ($array['type'])
		{
			case 'mysqli':
				$test = @new \MySQLi($array['hostname'], $array['username'], $array['password'], $array['database'], 3306);
				return mysqli_connect_errno();
		}
	}


	public static function save_database($array)
	{
		\Config::load('db', 'db');

		\Config::set('db.default', array(
			'type' => $array['type'],
			'connection' => array(
				'hostname' => $array['hostname'],
				'port' => '3306',
				'database' => $array['database'],
				'username' => $array['username'],
				'password' => $array['password'],
				'persistent' => false,
			),
			'identifier' => '',
			'table_prefix' => $array['prefix'],
			'charset'      => 'utf8',
			'enable_cache' => true,
			'profiling'    => false,
		));

		\Config::save('db', 'db');

		// check if mb4 is supported and in case enable it
		\DBUtil::set_connection('default');
		$query = \DB::query("SHOW CHARACTER SET WHERE Charset = 'utf8mb4'", \DB::SELECT)->execute();
		if (count($query))
		{
			\Config::set('db.default.charset', 'utf8mb4');
			\Config::save('db', 'db');
		}
	}


	public static function create_salts()
	{
		\Config::load('foolframe', 'foolframe');
		\Config::set('foolframe.config.cookie_prefix','foolframe'.\Str::random('alnum', 3).'_');
		\Config::save('foolframe', 'foolframe');

		\Config::load('auth', 'auth');
		\Config::set('auth.salt', \Str::random('alnum', 24));
		\Config::save('auth', 'auth');

		\Config::load('foolauth', 'foolauth');
		\Config::set('foolauth.login_hash_salt', \Str::random('alnum', 24));
		\Config::save('foolauth', 'foolauth');
	}

}