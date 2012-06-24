<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Fuuka_Controller extends MY_Controller
{
	var $_config = FALSE;

	function __construct()
	{
		parent::__construct();
		
		// load the radixes (boards)
		$this->load->model('radix_model', 'radix');
		
		Fuuka_Controller::cron();
	}

	/**
	 * Returns the basic variables that are used by the public interface and the admin panel for dealing with posts
	 *
	 * @return array the settings to be sent directly to JSON
	 */
	public function get_backend_vars()
	{
		return array(
				'site_url'  => site_url(),
				'default_url'  => site_url('@default'),
				'archive_url'  => site_url('@archive'),
				'system_url'  => site_url('@system'),
				'api_url'   => site_url('@system'),
				'cookie_domain' => config_item('cookie_domain'),
				'cookie_prefix' => config_item('cookie_prefix'),
				'selected_theme' => isset($this->theme)?$this->theme->get_selected_theme():'',
				'csrf_hash' => $this->security->get_csrf_hash(),
				'images' => array(
					'banned_image' => site_url() . 'content/themes/default/images/banned-image.png',
					'banned_image_width' => 150,
					'banned_image_height' => 150,
					'missing_image' => site_url() . 'content/themes/default/images/missing-image.jpg',
					'missing_image_width' => 150,
					'missing_image_height' => 150,
				),
				'gettext' => array(
					'submit_state' => __('Submitting'),
					'thread_is_real_time' => __('This thread is being displayed in real time.'),
					'update_now' => __('Update now')
				)
			);
	}

	/**
	 * A function shared between admin panel and boards for admins to manage the posts
	 */
	public function mod_post_actions()
	{
		if (!$this->auth->is_mod_admin())
		{
			$this->output->set_status_header(403);
			$this->output->set_output(json_encode(array('error' => __('You\'re not allowed to perform this action'))));
		}

		if (!$this->input->post('actions') || !$this->input->post('doc_id') || !$this->input->post('board'))
		{
			$this->output->set_status_header(404);
			$this->output->set_output(json_encode(array('error' => __('Missing arguments'))));
		}


		// action should be an array
		// array('ban_md5', 'ban_user', 'remove_image', 'remove_post', 'remove_report');
		$actions = $this->input->post('actions');
		if (!is_array($actions))
		{
			$this->output->set_status_header(404);
			$this->output->set_output(json_encode(array('error' => __('Invalid action'))));
		}

		$doc_id = $this->input->post('doc_id');
		$board = $this->radix->get_by_shortname($this->input->post('board'));

		$this->load->model('post_model', 'post');
		$post = $this->post->get_by_doc_id($board, $doc_id);

		if ($post === FALSE)
		{
			$this->output->set_status_header(404);
			$this->output->set_output(json_encode(array('error' => __('Post not found'))));
		}

		if (in_array('ban_md5', $actions))
		{
			$this->post->ban_media($post->media_hash, TRUE);
			$actions = array_diff($actions, array('remove_image'));
		}

		if (in_array('remove_post', $actions))
		{
			$this->post->delete(
				$board,
				array(
				'doc_id' => $post->doc_id,
				'password' => '',
				'type' => 'post'
				)
			);

			$actions = array_diff($actions, array('remove_image', 'remove_report'));
		}

		// if we banned md5 we already removed the image
		if (in_array('remove_image', $actions))
		{
			$this->post->delete_media($board, $post);
		}

		if (in_array('ban_user', $actions))
		{
			$this->load->model('poster_model', 'poster');
			$this->poster->ban(
				inet_ptod($post->poster_ip), isset($data['length']) ? $data['length'] : NULL,
				isset($data['reason']) ? $data['reason'] : NULL
			);
		}

		if (in_array('remove_report', $actions))
		{
			$this->load->model('report_model', 'report');
			$this->report->remove_by_doc_id($board, $doc_id);
		}

		$this->output->set_output(json_encode(array('success' => TRUE)));
	}

	/**
	 * Controller for cron triggered by any visit
	 * Currently defaulted crons:
	 * -updates every 13 hours the blocked IPs
	 *
	 * @author Woxxy
	 */
	public function cron()
	{
		$last_check = get_setting('fs_cron_stopforumspam');

		// every 10 minutes
		// only needed for asagi autorun
		if(get_setting('fs_asagi_autorun_enabled') && time() - $last_check > 600)
		{
			set_setting('fs_cron_10m', time());

			if('fs_asagi_autorun_enabled')
			{
				$this->load->model('asagi_model', 'asagi');
				$this->asagi->run();
			}

		}

		// every 13 hours
		if (false && time() - $last_check > 86400)
		{
			set_setting('fs_cron_13h', time());

			$url = 'http://www.stopforumspam.com/downloads/listed_ip_90.zip';
			if (function_exists('curl_init'))
			{
				$this->load->library('curl');
				$zip = $this->curl->simple_get($url);
			}
			else
			{
				$zip = file_get_contents($url);
			}
			if (!$zip)
			{
				log_message('error',
					'MY_Controller cron(): impossible to get the update from stopforumspam');
				set_setting('fs_cron_13h', time());
				return FALSE;
			}

			delete_files('content/cache/stopforumspam/', TRUE);
			if (!is_dir('content/cache/stopforumspam'))
				mkdir('content/cache/stopforumspam');
			write_file('content/cache/stopforumspam/stopforumspam.zip', $zip);
			$this->load->library('unzip');
			$this->unzip->extract('content/cache/stopforumspam/stopforumspam.zip');
			$ip_list = file_get_contents('content/cache/stopforumspam/listed_ip_90.txt');

			$this->db->truncate('stopforumspam');
			$ip_array = array();
			foreach (preg_split("/((\r?\n)|(\r\n?))/", $ip_list) as $line)
			{
				$ip_array[] = '(INET_ATON(' . $this->db->escape($line) . '))';
			}
			$this->db->query('
				INSERT IGNORE INTO ' . $this->db->protect_identifiers('stopforumspam',
					TRUE) . '
				VALUES ' . implode(',', $ip_array) . ';');


			delete_files('content/cache/stopforumspam/', TRUE);
		}
	}

}
