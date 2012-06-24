<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Package_plugin extends Plugins_model
{

	function __construct()
	{
		parent::__construct();
	}


	function initialize_plugin()
	{
		// load the radixes (boards)
		$this->load->model('radix_model', 'radix');
		
		
		$this->plugins->register_admin_sidebar_element('boards',
			array(
				"position" => array(
					"beforeafter" => "before",
					"element" => "auth"
				),
				"name" => __("Boards"),
				"level" => "admin",
				"default" => "manage",
				"content" => array(
					"manage" => array("alt_highlight" => array("board"),
						"level" => "admin", "name" => __("Manage"), "icon" => 'icon-th-list'),
					"add_new" => array("level" => "admin", "name" => __("Add board"), "icon" => 'icon-asterisk'),
					"sphinx" => array("level" => "admin", "name" => __("Sphinx Search"), "icon" => 'icon-search'),
					"asagi" => array("level" => "admin", "name" => __("Asagi Fetcher"), "icon" => 'icon-cogs'),
					"preferences" => array("level" => "admin", "name" => __("Preferences"), "icon" => 'icon-check')
				)
			)
		);
		
		$this->plugins->register_admin_sidebar_element('posts',
			array(
				"position" => array(
					"beforeafter" => "after",
					"element" => "boards"
				),
				"name" => __("Posts"),
				"level" => "mod",
				"default" => "reports",
				"content" => array(
					"reports" => array("level" => "mod", "name" => __("Reports"), "icon" => 'icon-tag'),
				)
			)
		);
	}
}
