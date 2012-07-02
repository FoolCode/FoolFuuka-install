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

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */

return array(

	/**
	 * DB connection, leave null to use default
	 */
	'db_connection' => null,

	/**
	 * DB table name for the user table
	 */
	'table_name' => 'users',

	/**
	 * Choose which columns are selected, must include: username, password, email, last_login,
	 * login_hash, group & profile_fields
	 */
	'table_columns' => array('*'),

	/**
	 * This will allow you to use the group & acl driver for non-logged in users
	 */
	'guest_login' => true,

	/**
	 * Groups as id => array(name => <string>, roles => <array>)
	 */
	'groups' => array(
		0    => array('name' => 'Guests', 'roles' => array()),
		1    => array('name' => 'Users', 'roles' => array('user')),
		50   => array('name' => 'Moderators', 'roles' => array('mod')),
		100  => array('name' => 'Administrators', 'roles' => array('admin')),
	),

	/**
	 * Roles as name => array(location => rights)
	 */
	'roles' => array(
		'user' => array('access' => array('user', 'member'), 'maccess' => array('user')),
		'mod' => array(
			'access' => array('mod'),
			'maccess' => array('user', 'mod'),
			'comment' => array('passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode')
		),
		'admin' => array(
			'access' => array('admin'),
			'maccess' => array('user', 'mod', 'admin'),
			'boards' => array('edit'),
			'comment' => array('passwordless_deletion', 'limitless_comment', 'reports'. 'mod_capcode', 'admin_capcode')
		),
		/**
		 * Examples
		 * ---
		 *
		 * Regular example with role "user" given create & read rights on "comments":
		 *   'user'  => array('comments' => array('create', 'read')),
		 * And similar additional rights for moderators:
		 *   'moderator'  => array('comments' => array('update', 'delete')),
		 *
		 * Wildcard # role (auto assigned to all groups):
		 *   '#'  => array('website' => array('read'))
		 *
		 * Global disallow by assigning false to a role:
		 *   'banned' => false,
		 *
		 * Global allow by assigning true to a role (use with care!):
		 *   'super' => true,
		 */
	),

	/**
	 * Salt for the login hash
	 */
	'login_hash_salt' => 'put_some_salt_in_here',

	/**
	 * $_POST key for login username
	 */
	'username_post_key' => 'username',

	/**
	 * $_POST key for login password
	 */
	'password_post_key' => 'password',
);
