<?php

namespace Install\Model;

class Install extends \Model
{

	/**
	 * Checks a few basic requirements to run the framework
	 */
	public static function system_check()
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

}