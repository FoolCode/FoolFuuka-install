<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Admin_Controller extends MY_Controller
{

	var $sidebar = NULL;
	var $sidebar_dynamic = NULL;


	public function __construct()
	{
		parent::__construct();

		// auth controller can protect itself, other controllers not so sure,
		if (!$this->auth->is_logged_in() && $this->uri->segment(2) != 'auth')
		{
			$this->session->set_userdata('login_redirect', $this->uri->uri_string());
			redirect('@system/admin/auth');
		}
		
		// if user is a member, the default must go to the auth system
		if($this->auth->is_member() && $this->uri->uri_string() == 'admin')
		{
			redirect('@system/admin/auth/change_email');
		}
		
		// if user is a member, the default must go to the reports system
		if($this->auth->is_mod() && $this->uri->uri_string() == 'admin')
		{
			redirect('@system/admin/posts/reports');
		}
		
		// returns the static sidebar array (can't use functions in )
		$this->sidebar = $this->get_sidebar_values();

		// merge if there were sidebar elements added dynamically
		if (!is_null($this->sidebar_dynamic))
		{
			$this->sidebar = $this->merge_sidebars($this->sidebar, $this->sidebar_dynamic);
		}
		// removes the sidebar elements for which user has no permissions
		// and adds some data (active, checks URLs...)
		$viewdata["sidebar"] = $this->get_sidebar($this->sidebar);
		$this->viewdata['sidebar'] = $this->load->view('admin/sidebar', $viewdata,
			TRUE);


		$this->viewdata['topbar'] = $this->load->view('admin/navbar', '', TRUE);

		// load the preferences model since the admin panel is full of submit buttons
		$this->load->model('preferences_model', 'preferences');

		// Check if the database is upgraded to the the latest available
		if ($this->auth->is_admin() && $this->uri->uri_string() != 'admin/database/upgrade' && $this->uri->uri_string() != 'admin/database/do_upgrade')
		{
			$this->config->load('migration');
			$config_version = $this->config->item('migration_version');
			$db_version = $this->db->get('migrations')->row()->version;
			if ($db_version != $config_version)
			{
				redirect('@system/admin/database/upgrade/');
			}
			$this->cron();
		}
	}


	/**
	 * Non-dynamic sidebar array.
	 * Permissions are set inside
	 *
	 * @author Woxxy
	 * @return sidebar array
	 */
	function get_sidebar_values()
	{

		$sidebar = array();

		$sidebar["auth"] = array(
			"name" => __("Account"),
			"level" => "member",
			"default" => "change_email",
			"content" => array(
				"change_email" => array("level" => "member", "name" => __("Change Email"), "icon" => 'icon-envelope'),
				"change_password" => array("level" => "member", "name" => __("Change Password"), "icon" => 'icon-lock'),
				"unregister" => array("level" => "member", "name" => __("Unregister"), "icon" => 'icon-remove-circle')
			)
		);

		$sidebar["members"] = array(
			"name" => __("Members"),
			"level" => "mod",
			"default" => "members",
			"content" => array(
				"members" => array("alt_highlight" => array("member"),
					"level" => "mod", "name" => __("Member List"), "icon" => 'icon-user'),
			)
		);
		
		$sidebar["preferences"] = array(
			"name" => __("Preferences"),
			"level" => "admin",
			"default" => "general",
			"content" => array(
				"theme" => array("level" => "admin", "name" => __("Theme"), "icon" => 'icon-picture'),
				"registration" => array("level" => "admin", "name" => __("Registration"), "icon" => 'icon-book'),
				"advertising" => array("level" => "admin", "name" => __("Advertising"), "icon" => 'icon-lock'),
			)
		);
		
		$sidebar["system"] = array(
			"name" => __("System"),
			"level" => "admin",
			"default" => "system",
			"content" => array(
				"information" => array("level" => "admin", "name" => __("Information"), "icon" => 'icon-info-sign'),
				"preferences" => array("level" => "admin", "name" => __("Preferences"), "icon" => 'icon-check'),
				"upgrade" => array("level" => "admin", "name" => __("Upgrade") . ((get_setting('fs_cron_autoupgrade_version') && version_compare(FOOL_VERSION,
						get_setting('fs_cron_autoupgrade_version')) < 0) ? ' <span class="label label-success">' . __('New') . '</span>'
							: ''), "icon" => 'icon-refresh'),
			)
		);

		$sidebar["plugins"] = array(
			"name" => __("Plugins"),
			"level" => "admin",
			"default" => "manage",
			"content" => array(
				"manage" => array("level" => "admin", "name" => __("Manage"), "icon" => 'icon-gift'),
			)
		);

		$sidebar["meta"] = array(
			"name" => "Meta", // no gettext because meta must be meta
			"level" => "member",
			"default" => "http://ask.foolrulez.com",
			"content" => array(
				"https://github.com/FoOlRulez/FoOlFuuka/issues" => array("level" => "member", "name" => __("Bug tracker"), "icon" => 'icon-exclamation-sign'),
			)
		);

		return $sidebar;
	}


	/**
	 * Sets new sidebar elements, the array must match the defaults' structure.
	 * It can override the methods.
	 * 
	 * @param array $array 
	 */
	public function add_sidebar_element($array)
	{
		if (is_null($this->sidebar_dynamic))
		{
			$this->sidebar_dynamic = array();
		}

		$this->sidebar_dynamic[] = $array;
	}


	/**
	 * Merges without destroying twi sidebars, where $array2 overwrites values of
	 * $array1.
	 * 
	 * @param array $array1 sidebar array to be merged into
	 * @param array $array2 sidebar array with elements to merge
	 * @return array resulting sidebar
	 */
	public function merge_sidebars($array1, $array2)
	{
		// there's a numbered index on the outside!
		foreach ($array2 as $key_top => $item_top)
		{
			foreach($item_top as $key => $item)
			{
				// are we inserting in an already existing method?
				if (isset($array1[$key]))
				{
					// overriding the name
					if (isset($item['name']))
					{
						$array1[$key]['name'] = $item['name'];
					}

					// overriding the permission level
					if (isset($item['level']))
					{
						$array1[$key]['level'] = $item['level'];
					}

					// overriding the default url to reach
					if (isset($item['default']))
					{
						$array1[$key]['default'] = $item['default'];
					}

					// overriding the default url to reach
					if (isset($item['icon']))
					{
						$array1[$key]['icon'] = $item['icon'];
					}

					// adding or overriding the inner elements
					if (isset($item['content']))
					{
						if (isset($array1[$key]['content']))
						{
							$array1[$key]['content'] = $this->merge_sidebars($array1[$key]['content'], $item);
						}
						else
						{
							$array1[$key]['content'] = $this->merge_sidebars(array(), $item);
						}
					}
				}
				else
				{
					// the element doesn't exist at all yet
					// let's trust the plugin creator in understanding the structure
					// extra control: allow him to put the plugin after or before any function
					if (isset($item['position']) && is_array($item['position']))
					{
						$before = $item['position']['beforeafter'] == 'before' ? TRUE : FALSE;
						$element = $item['position']['element'];

						$array_temp = $array1;
						$array1 = array();
						foreach ($array_temp as $subkey => $temp)
						{
							if ($subkey == $element)
							{
								if ($before)
								{
									$array1[$key] = $item;
									$array1[$subkey] = $temp;
								}
								else
								{
									$array1[$subkey] = $temp;
									$array1[$key] = $item;
								}

								unset($array_temp[$subkey]);

								// flush the rest
								foreach ($array_temp as $k => $t)
								{
									$array1[$k] = $t;
								}

								break;
							}
							else
							{
								$array1[$subkey] = $temp;
								unset($array_temp[$subkey]);
							}
						}
					}
					else
					{
						$array1[$key] = $item;
					}
				}
			}
		}
		
		/*
		echo '<pre>';
		print_r($array2);
		print_r($array1);
		echo '</pre>';
		 * 
		 */
		return $array1;
	}


	/**
	 * Returns the sidebar array
	 *
	 * @todo comment this
	 */
	public function get_sidebar($array)
	{
		// not logged in users don't need the sidebar
		if (!$this->auth->is_logged_in())
			return array();

		$result = array();
		foreach ($array as $key => $item)
		{
			if (($item["level"] == 'member' && $this->auth->is_logged_in() 
					|| $item["level"] == 'mod' && $this->auth->is_mod_admin() 
					|| $item["level"] == 'admin' && $this->auth->is_admin()) 
				&& !empty($item))
			{
				$subresult = $item;

				// segment 2 contains what's currently active so we can set it lighted up
				if ($this->uri->segment(2) == $key)
				{
					$subresult['active'] = TRUE;
				}
				else
				{
					$subresult['active'] = FALSE;
				}

				// we'll cherry-pick the content next
				unset($subresult['content']);

				// recognize plain URLs
				if ((substr($item['default'], 0, 7) == 'http://') ||
					(substr($item['default'], 0, 8) == 'https://'))
				{
					// nothing to do here, just copy the URL
					$subresult['href'] = $item['default'];
				}
				else
				{
					// else these are internal URIs
					// what if it uses more segments or is even an array?
					if (!is_array($item['default']))
					{
						$default_uri = explode('/', $item['default']);
					}
					else
					{
						$default_uri = $item['default'];
					}
					array_unshift($default_uri, 'admin', $key);
					$subresult['href'] = site_url($default_uri);
				}

				$subresult['content'] = array();

				// cherry-picking subfunctions
				foreach ($item['content'] as $subkey => $subitem)
				{
					$subsubresult = array();
					$subsubresult = $subitem;
					if (($subitem["level"] == 'member' && $this->auth->is_logged_in() 
						|| $subitem["level"] == 'mod' && $this->auth->is_mod_admin() 
						|| $subitem["level"] == 'admin' && $this->auth->is_admin())
						)
					{
						if ($subresult['active'] && ($this->uri->segment(3) == $subkey ||
							(
							isset($subitem['alt_highlight']) &&
							in_array($this->uri->segment(3), $subitem['alt_highlight'])
							)
							))
						{
							$subsubresult['active'] = TRUE;
						}
						else
						{
							$subsubresult['active'] = FALSE;
						}

						// recognize plain URLs
						if ((substr($subkey, 0, 7) == 'http://') ||
							(substr($subkey, 0, 8) == 'https://'))
						{
							// nothing to do here, just copy the URL
							$subsubresult['href'] = $subkey;
						}
						else
						{
							// else these are internal URIs
							// what if it uses more segments or is even an array?
							if (!is_array($subkey))
							{
								$default_uri = explode('/', $subkey);
							}
							else
							{
								$default_uri = $subkey;
							}
							array_unshift($default_uri, 'admin', $key);
							$subsubresult['href'] = site_url($default_uri);
						}

						$subresult['content'][] = $subsubresult;
					}
				}

				$result[] = $subresult;
			}
		}
		return $result;
	}

	/**
	 * Controller for cron triggered by admin panel
	 * Currently defaulted crons:
	 * -check for updates
	 * -remove one week old logs
	 *
	 * @author Woxxy
	 */
	public function cron()
	{
		if ($this->auth->is_admin())
		{
			$last_check = get_setting('fs_cron_autoupgrade');

			// hourly cron
			if (time() - $last_check > 3600)
			{
				// update autoupgrade cron time
				set_setting('fs_cron_autoupgrade', time());

				// load model
				$this->load->model('upgrade_model', 'upgrade');
				// check
				$versions = $this->upgrade->check_latest(TRUE);
				
				// if a version is outputted, save the new version number in database
				if ($versions[0])
				{
					set_setting('fs_cron_autoupgrade_version', end($versions)->name);
				}

				// remove one week old logs
				$files = glob($this->config->item('log_path') . 'log*.php');
				foreach ($files as $file)
				{
					if (filemtime($file) < strtotime('-7 days'))
					{
						unlink($file);
					}
				}

				// reload the settings
				load_settings();
			}
		}
	}

}