<?php

namespace Fuel\Migrations;

class Install
{

    function up()
    {
		$charset = \Config::get('db.default.charset');

		if (!\DBUtil::table_exists('sessions'))
		{
			\DBUtil::create_table('sessions', array(
				'session_id' => array('type' => 'varchar', 'constraint' => 40),
				'previous_id' => array('type' => 'varchar', 'constraint' => 40),
				'user_agent' => array('type' => 'text'),
				'ip_hash' => array('type' => 'char', 'constraint' => 32, 'default' => ''),
				'created' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0),
				'updated' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0),
				'payload' => array('type' => 'longtext')
			), array('session_id'), true, 'innodb', $charset.'_general_ci');

			\DBUtil::create_index('sessions', 'previous_id', 'previous_id_index', 'unique');
		}

		if (!\DBUtil::table_exists('plugins'))
		{
			\DBUtil::create_table('plugins', array(
				'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'autoincrement' => true),
				'slug' => array('type' => 'varchar', 'constraint' => 64),
				'enabled' => array('type' => 'smallint', 'constraint' => 2, 'default' => 1),
				'revision' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => \DB::expr('NULL'), 'null' => true)
			), array('id'), true, 'innodb', 'utf8_general_ci');

			\DBUtil::create_index('plugins', 'slug', 'slug_index', 'unique');
		}

		if (!\DBUtil::table_exists('preferences'))
		{
			\DBUtil::create_table('preferences', array(
				'name' => array('type' => 'varchar', 'constraint' => 64),
				'value' => array('type' => 'text', 'null' => true),
			), array('name'), true, 'innodb', $charset.'_general_ci');
		}

		if (!\DBUtil::table_exists('users'))
		{
			\DBUtil::create_table('users', array(
				'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'autoincrement' => true),
				'username' => array('type' => 'varchar', 'constraint' => 50),
				'password' => array('type' => 'varchar', 'constraint' => 255),
				'group' => array('type' => 'int', 'constraint' => 11, 'default' => 1),
				'email' => array('type' => 'varchar', 'constraint' => 255, 'default' => \DB::expr('NULL'), 'null' => true),
				'last_login' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
				'new_email' => array('type' => 'varchar', 'constraint' => 255, 'default' => \DB::expr('NULL'), 'null' => true),
				'new_email_key' => array('type' => 'varchar', 'constraint' => 128, 'default' => \DB::expr('NULL'), 'null' => true),
				'new_email_time' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => \DB::expr('NULL'), 'null' => true),
				'activated' => array('type' => 'tinyint', 'constraint' => 2, 'unsigned' => true),
				'activation_key' => array('type' => 'varchar', 'constraint' => 128, 'null' => true, 'default' => \DB::expr('NULL')),
				'new_password_key' => array('type' => 'varchar', 'constraint' => 128, 'null' => true, 'default' => \DB::expr('NULL')),
				'new_password_time' => array('type' => 'int', 'constraint' => 11, 'null' => true, 'default' => \DB::expr('NULL')),
				'deletion_key' => array('type' => 'varchar', 'constraint' => 128, 'null' => true, 'default' => \DB::expr('NULL')),
				'deletion_time' => array('type' => 'int', 'constraint' => 11, 'null' => true, 'default' => \DB::expr('NULL')),
				'profile_fields' => array('type' => 'text'),
				'bio' => array('type' => 'text'),
				'twitter' => array('type'=> 'varchar', 'constraint' => 32),
				'created_at' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true)
			), array('id'), true, 'innodb', $charset.'_general_ci');

			\DBUtil::create_index('users', array('username', 'email'), 'username_email_index', 'unique');
		}

		if (!\DBUtil::table_exists('user_autologin'))
		{
			\DBUtil::create_table('user_autologin', array(
				'user_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
				'login_hash' => array('type' => 'varchar', 'constraint' => 255),
				'expiration' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
				'last_ip' => array('type' => 'decimal', 'constraint' => '39,0'),
				'user_agent' => array('type' => 'varchar', 'constraint' => 150),
				'last_login' => array('type' => 'int', 'constraint' => 11)
			), array('login_hash'), true, 'innodb', 'utf8_general_ci');

			\DBUtil::create_index('user_autologin', 'user_id', 'user_id_index');
		}

	}

    function down()
    {
       \DBUtil::drop_table('sessions');
       \DBUtil::drop_table('plugins');
       \DBUtil::drop_table('preferences');
       \DBUtil::drop_table('users');
       \DBUtil::drop_table('users_autologin');
    }
}