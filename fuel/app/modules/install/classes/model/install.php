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

		$checked['software'] = array(
			'string' => __('Software'),
			'checks' => array(
				'php_version' => array(
					'string' => 'PHP Version',
					'result' => (version_compare(PHP_VERSION, \Config::get('foolframe.install.requirements.min_php_version')) >= 0),
					'value'  => PHP_VERSION,
					'debug'  => \Str::tr(__('This will check and display the current version of PHP running on your server.')),
					'error'  => \Str::tr(__('You are currently running an old version of PHP. Please upgrade to :version or higher to install FoolFrame.'),
						array('version' => \Config::get('foolframe.install.requirements.min_php_version'))),
					'level'  => 'crit'
				),
			)
		);

		$checked['config'] = array(
			'string' => 'PHP Configuration',
			'checks' => array(
				'php_safe_mode' => array(
					'string' => 'safe_mode',
					'result' => (ini_get('safe_mode') == 0),
					'value'  => ((ini_get('safe_mode')) ? __('On') : __('Off')),
					'debug'  => \Str::tr(__('This variable attempts to resolve security problems found on shared hosting and disables many important PHP functions required for FoolFrame to function properly.')),
					'error'  => \Str::tr(__('Safe Mode has been enabled on your PHP installation. It is recommended that this setting is disabled to allow FoolFrame to function properly.')),
					'level'  => 'crit'
				),

				'php_allow_url_fopen' => array(
					'string' => 'allow_url_fopen',
					'result' => (ini_get('allow_url_fopen') == 1),
					'value'  => ((ini_get('allow_url_fopen')) ? __('On') : __('Off')),
					'debug'  => \Str::tr(__('This variable determines if PHP allows URL-aware fopen wrappers to access remote files via the FTP or HTTP protocol. If cURL is not installed on the system, this will affect FoolFrame functions that require accessing remote files.')),
					'error'  => \Str::tr(__('Your PHP installation does not support accessing remote files via the FTP or HTTP protocol with URL-aware fopen wrappers. It is recommended that this setting is enabled to ensure full compatibility.')),
					'level'  => 'warn'
				),

				'php_max_execution_time' => array(
					'string' => 'max_execution_time',
					'result' => (intval(ini_get('max_execution_time')) >= 120),
					'value'  => ini_get('max_execution_time'),
					'debug'  => \Str::tr(__('This variable determines the maximum time in seconds a script is allowed to run before it is terminated by the parser.')),
					'error'  => \Str::tr(__('Your current value for max execution time is fairly low. It is recommended that you raise this value when PHP terminates FoolFrame operations prematurely.')),
					'level'  => 'warn'
				),

				'php_file_uploads' => array(
					'string' => 'file_uploads',
					'result' => (ini_get('file_uploads') == 1),
					'value'  => ((ini_get('file_uploads')) ? __('Enabled') : __('Disabled')),
					'debug'  => \Str::tr(__('This variable determines whether or not to allow HTTP file uploads.')),
					'error'  => \Str::tr(__('Your PHP installation currently has file uploads disabled. This must be enabled to allow FoolFrame to operate correctly.')),
					'level'  => 'warn'
				),

				'php_max_file_uploads' => array(
					'string' => 'max_file_uploads',
					'result' => (intval(ini_get('max_file_uploads')) >= 60),
					'value'  => ini_get('max_file_uploads'),
					'debug'  => \Str::tr(__('This variable sets the maximum number of files allowed to be uploaded simultaneously.'), array()),
					'error'  => \Str::tr(__('Your current value for max execution time is fairly low. It is recommended that you raise this value when PHP terminates FoolFrame uploads prematurely.')),
					'level'  => 'warn'
				),

				'php_post_max_size' => array(
					'string' => 'post_max_size',
					'result' => (intval(substr(ini_get('post_max_size'), 0, -1)) >= 8),
					'value'  => ini_get('post_max_size'),
					'debug'  => \Str::tr(__('This variable sets the maximum size of POST data allowed. This variable determines the maximum size of POST data allowed to be sent in PHP.')),
					'error'  => \Str::tr(__('Your current value for max execution time is fairly low. It is recommended that you raise this value when PHP terminates FoolFrame uploads prematurely.')),
					'level'  => 'warn'
				),

				'php_upload_max_filesize' => array(
					'string' => 'upload_max_filesize',
					'result' => (intval(substr(ini_get('post_max_size'), 0, -1)) >= 8),
					'value'  => ini_get('upload_max_filesize'),
					'debug'  => \Str::tr(__('This variable sets the maximum size allowed for an uploaded file.')),
					'error'  => \Str::tr(__('Your current value for max execution time is fairly low. It is recommended that you raise this value when PHP terminates FoolFrame uploads prematurely.')),
					'level'  => 'warn'
				),
			)
		);

		$checked['extensions'] = array(
			'string' => 'PHP Extensions',
			'checks' => array(
				'extension_apc' => array(
					'string' => 'APC',
					'result' => extension_loaded('apc'),
					'value'  => ((extension_loaded('apc') == true) ? __('Installed') : __('Missing')),
					'level'  => 'warn'
				),

				'extension_bcmath' => array(
					'string' => 'BCMath',
					'result' => extension_loaded('bcmath'),
					'value'  => ((extension_loaded('bcmath') == true) ? __('Installed') : __('Missing')),
					'level'  => 'warn'
				),

				'extension_curl' => array(
					'string' => 'cURL',
					'result' => extension_loaded('curl'),
					'value'  => ((extension_loaded('curl') == true) ? __('Installed') : __('Missing')),
					'level'  => 'warn'
				),

				'extension_exif' => array(
					'string' => 'EXIF',
					'result' => extension_loaded('exif'),
					'value'  => ((extension_loaded('exif') == true) ? __('Installed') : __('Missing')),
					'level'  => 'warn'
				),

				'extension_fileinfo' => array(
					'string' => 'FileInfo',
					'result' => extension_loaded('fileinfo'),
					'value'  => ((extension_loaded('fileinfo') == true) ? __('Installed') : __('Missing')),
					'level'  => 'crit'
				),

				'extension_gd' => array(
					'string' => 'GD',
					'result' => extension_loaded('gd'),
					'value'  => ((extension_loaded('gd') == true) ? __('Installed') : __('Missing')),
					'level'  => 'crit'
				),

				'extension_json' => array(
					'string' => 'JSON',
					'result' => extension_loaded('json'),
					'value'  => ((extension_loaded('json') == true) ? __('Installed') : __('Missing')),
					'level'  => 'warn'
				),

				'extension_mbstring' => array(
					'string' => 'MBString',
					'result' => extension_loaded('mbstring'),
					'value'  => ((extension_loaded('mbstring') == true) ? __('Installed') : __('Missing')),
					'level'  => 'crit'
				),

				'extension_mysqli' => array(
					'string' => 'MySQLi',
					'result' => extension_loaded('mysqli'),
					'value'  => ((extension_loaded('mysqli') == true) ? __('Installed') : __('Missing')),
					'level'  => 'crit'
				),

				'extension_pdo_mysql' => array(
					'string' => 'PDO MySQL',
					'result' => extension_loaded('pdo_mysql'),
					'value'  => ((extension_loaded('pdo_mysql') == true) ? __('Installed') : __('Missing')),
					'level'  => 'crit'
				),
			)
		);

		$checked['permissions'] = array(
			'string' => 'File Permissions',
			'checks' => array()
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

		\Config::save(\Fuel::$env.DS.'db', 'db');

		// check if mb4 is supported and in case enable it
		\DBUtil::set_connection('default');
		$query = \DB::query("SHOW CHARACTER SET WHERE Charset = 'utf8mb4'", \DB::SELECT)->execute();
		if (count($query))
		{
			\Config::set('db.default.charset', 'utf8mb4');
			\Config::save(\Fuel::$env.DS.'db', 'db');
		}
	}

	public static function clear_database_users()
	{
		\Config::load('foolauth', 'foolauth');

		\DBUtil::set_connection('default');
		\DBUtil::truncate_table(\Config::get('foolauth.table_name'));
	}


	public static function create_salts()
	{
		\Config::load('foolframe', 'foolframe');
		\Config::set('foolframe.config.cookie_prefix', 'foolframe_'.\Str::random('alnum', 3).'_');
		\Config::save(\Fuel::$env.DS.'foolframe', 'foolframe');

		\Config::load('auth', 'auth');
		\Config::set('auth.salt', \Str::random('alnum', 24));
		\Config::save(\Fuel::$env.DS.'auth', 'auth');

		\Config::load('foolauth', 'foolauth');
		\Config::set('foolauth.login_hash_salt', \Str::random('alnum', 24));
		\Config::save(\Fuel::$env.DS.'foolauth', 'foolauth');

		\Config::load('cache', 'cache');
		\Config::set('cache.apc.cache_id', 'foolframe_'.\Str::random('alnum', 3).'_');
		\Config::set('cache.memcached.cache_id', 'foolframe_'.\Str::random('alnum', 3).'_');
		\Config::save(\Fuel::$env.DS.'cache', 'cache');
		
		$crypt = array();
		
		foreach(array('crypto_key', 'crypto_iv', 'crypto_hmac') as $key)
		{
			$crypto = '';
			for ($i = 0; $i < 8; $i++)
			{
				$crypto .= static::safe_b64encode(pack('n', mt_rand(0, 0xFFFF)));
			}
			
			$crypt[$key] = $crypto;
		}
		
		\Config::set('crypt', $crypt);
		\Config::save(\Fuel::$env.DS.'crypt', 'crypt');
	}


	public static function modules()
	{
		$modules = array(
			'foolfuuka' => array(
				'title' => __('FoolFuuka Imageboard'),
				'description' => __('FoolFuuka is one of the most advanced imageboard software written.'),
				'disabled' => false,
			),

			'foolpod' => array(
				'title' => __('FoolPod Distribution Repository'),
				'description' => __('FoolPod is the distribution system that provides FoolFrame installations with updates. It provides updates for the FoolFrame framework, modules, themes, and plugins.'),
				'disabled' => false,
			),

			'foolslide' => array(
				'title' => __('FoolSlide Online Reader'),
				'description' => __('FoolSlide provides a clean visual interface to view multiple images in reading format. It can be used standalone to offer users the best reading experience available online.'),
				'disabled' => true,
			),
		);

		return $modules;
	}
}