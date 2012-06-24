<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
	var $_config = FALSE;

	function __construct()
	{
		parent::__construct();
		
		// create an array for the set_notice system
		$this->notices = array();
		$this->flash_notice_data = array();

		if (!file_exists(FCPATH . "config.php"))
		{
			if ($this->uri->segment(1) != "install")
				return show_error("If you are here, and have no clue why {{FOOL_NAME}} is not working, start by reading the <a href='{{FOOL_MANUAL_INSTALL_URL}}'>installation manual</a>.");
		}
		else
		{
			// we have found a config file.
			$this->_config = TRUE;

			//$this->output->enable_profiler(TRUE);
			$this->load->database();
			$this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'dummy'));
			$this->load->library('session');
			$this->load->library('tank_auth', array(), 'auth');

			// loads variables from database for get_setting()
			load_settings();

			// plugin system as early we can without losing on security
			$this->load->model('plugins_model', 'plugins');
			
			require_once 'application/packages/foolfuuka/package_plugin.php';
			$this->plugins->inject_plugin('package', 'Package_plugin');

			$this->plugins->load_plugins();
			
			$this->plugins->run_hook('ff_my_controller_after_load_settings');

			// This is the first chance we get to load the right translation file
			$available_langs = config_item('ff_available_languages');
			$lang = $this->input->cookie('language');
			if(!$lang || !array_key_exists($lang, $available_langs))
			{
				$lang = get_setting('ff_lang_default', FOOL_LANG_DEFAULT);
			}

			$locale = $lang . '.utf8';
			putenv('LANG=' . $locale);
			putenv('LANGUAGE=' . $locale);
			if ($locale != "tr_TR.utf8")
			{
				setlocale(LC_ALL, $locale);
			}
			else // workaround to make turkish work
			{
				setlocale(LC_COLLATE, $locale);
				setlocale(LC_MONETARY, $locale);
				setlocale(LC_NUMERIC, $locale);
				setlocale(LC_TIME, $locale);
				setlocale(LC_MESSAGES, $locale);
				setlocale(LC_CTYPE, "sk_SK.utf8");
			}

			bindtextdomain($lang, FCPATH . "assets/locale");
			bind_textdomain_codeset($lang, 'UTF-8');
			textdomain($lang);

			// a good time to change some of the defauly settings dynamically
			$this->config->config['tank_auth']['allow_registration'] = !get_setting('fs_reg_disabled');

			$this->config->config['tank_auth']['email_activation'] = ((get_setting('fs_reg_email_disabled'))
						? FALSE : TRUE);

			$captcha_public = get_setting('fs_reg_recaptcha_public');
			if ($captcha_public != "")
			{
				$captcha_secret = get_setting('fs_reg_recaptcha_secret');
				if ($captcha_secret != "")
				{
					$this->config->config['tank_auth']['use_recaptcha'] = TRUE;
					$this->config->config['tank_auth']['recaptcha_public_key'] = $captcha_public;
					$this->config->config['tank_auth']['recaptcha_secret_key'] = $captcha_secret;
				}
			}
		}
	}


	/**
	 * Alternative remap function that works with the plugin system
	 *
	 * @param string $method
	 * @param type $params
	 * @return type
	 */
	public function _remap($method, $params = array())
	{
		if ($method == 'plugin')
		{
			// don't let people go directly to the plugin system
			return FALSE;
		}

		// access plugin system only if backend has been configured
		if ($this->_config == TRUE && $this->plugins->is_controller_function($this->uri->segment_array()))
		{
			$plugin_controller = $this->plugins->get_controller_function($this->uri->segment_array());
			$uri_array = $this->uri->segment_array();
			array_shift($uri_array);
			array_shift($uri_array);
			array_shift($uri_array);

			return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']),
				$uri_array);
		}

		// trying to access an internal method, should never reach here, but safety is never enough
		if(substr($method, 0, 1) == '_')
		{
			return FALSE;
		}

		// we don't want to send back to Chan controller, but everywhere else it's good to go
		if (method_exists($this, $method) && get_class($this) != 'Chan')
		{
			return call_user_func_array(array($this, $method), $params);
		}

		return FALSE;
	}


}
