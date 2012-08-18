<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

$charset = \Config::get('db.default.charset');

if (!\DBUtil::table_exists('plugin_ff-articles'))
{
	\DBUtil::create_table('plugin_ff-articles', array(
		'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
		'slug' => array('type' => 'varchar', 'constraint' => 128),
		'title' => array('type' => 'varchar', 'constraint' => 256),
		'url' => array('type' => 'text'),
		'article' => array('type' => 'text'),
		'active' => array('type' => 'smallint', 'constraint' => 2),
		'top' => array('type' => 'smallint', 'constraint' => 2),
		'bottom' => array('type' => 'smallint', 'constraint' => 2),
		'edited' => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP'), 'on_update' => \DB::expr('CURRENT_TIMESTAMP')),
	), array('id'), true, 'innodb', $charset.'_general_ci');

	\DBUtil::create_index('plugin_ff-articles', 'slug', 'slug_index');
}