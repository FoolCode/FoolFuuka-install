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

		$checked['extensions_required'] = array(
			'string' => __('Required extensions'),
			'checks' => array(
				'php_version' => array(
					'string' => 'PHP version >= '.\Config::get('foolframe.install.requirements.min_php_version'),
					'result' => (version_compare(PHP_VERSION, \Config::get('foolframe.install.requirements.min_php_version')) >= 0),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'crit'
				),

				'extension_mysqli' => array(
					'string' => 'MySQLi extension',
					'result' => extension_loaded('mysqli'),
					'error'  =>  \Str::tr(__(''), array()),
					'level'  => 'crit'
				),

				'extension_pdo_mysql' => array(
					'string' => 'PDO MySQL extension',
					'result' => extension_loaded('pdo_mysql'),
					'error' => \Str::tr(__(''), array()),
					'level'  => 'crit'
				),

				'extension_fileinfo' => array(
					'string' => 'FileInfo extension',
					'result' => extension_loaded('fileinfo'),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'crit'
				),

				'extension_mbstring' => array(
					'string' => 'MBString extension',
					'result' => extension_loaded('mbstring'),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'crit'
				),
			)
		);



		$checked['extension_extras'] = array(
			'string' => 'Additional Extensions',
			'checks' => array(
				'extension_apc' => array(
					'string' => 'APC extension',
					'result' => extension_loaded('apc'),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'warn'
				),

				'extension_bcmath' => array(
					'string' => 'BCMath extension',
					'result' => extension_loaded('bcmath'),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'warn'
				),

				'extension_exif' => array(
					'string' => 'EXIF extension',
					'result' => extension_loaded('exif'),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'warn'
				),

				'extension_json' => array(
					'string' => 'JSON extension',
					'result' => extension_loaded('json'),
					'error'  => \Str::tr(__(''), array()),
					'level'  => 'warn'
				)
			)
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


	public static function setup_database($array)
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