<?php
/**
 * Base Database Config.
 *
 * See the individual environment DB configs for specific config information.
 */

return array(
	'active' => 'default',

	/**
	 * Base config, just need to set the DSN, username and password in env. config.
	 */
	'default' => array(
		'type'        => 'mysqli',
		'connection'  => array(
			'hostname' => 'localhost',
			'port' => '3306',
			'database' => '',
			'username' => '',
			'password' => '',
			'persistent' => false,
		),
		'identifier'   => '`',
		'table_prefix' => 'fu_',
		'charset'      => 'utf8',
		'enable_cache' => true,
		'profiling'    => false,
	),

);
