<?php


/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Chan extends Controller_Common
{

	protected $_theme = null;
	protected $_radix = null;


	public function before()
	{
		parent::before();

		$this->_theme = new \Theme();

		$this->_theme->set_theme('default');
		$this->_theme->set_layout('chan');

		if (!is_null($this->_radix))
		{
			$this->_theme->set_title($this->_radix->formatted_title);
		}

		$pass = \Cookie::get('reply_password');
		$name = \Cookie::get('reply_name');
		$email = \Cookie::get('reply_email');

		// get the password needed for the reply field
		if(!$pass || $pass < 3)
		{
			$pass = \Str::random('alnum', 7);
			\Cookie::set('reply_password', $pass, 60*60*24*30);
		}

		$this->_theme->bind(array(
			'user_name' => $name,
			'user_email' => $email,
			'user_pass' => $pass,
			'disable_headers' => false,
			'is_page' => false,
			'is_thread' => false,
			'is_last50' => false,
			'order' => false,
			'modifiers' => array(),
			'backend_vars' => array()
		));

		$this->_theme->set_partial('tools_modal', 'tools_modal');
		$this->_theme->set_partial('tools_search', 'tools_search');
	}


	public function router($method, $params)
	{
		$this->_radix = Radix::set_selected_by_shortname($method);
		$this->_theme->bind('radix', $this->_radix?:null);
		if ($this->_radix)
		{
			$method = array_shift($params);
		}

		if (method_exists($this, 'action_'.$method))
		{
			return call_user_func_array(array($this, 'action_'.$method), $params);
		}

		return call_user_func_array(array($this, 'action_404'), $params);
	}


	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$this->_theme->bind('disable_headers', TRUE);
		return Response::forge($this->_theme->build('index'));
	}


	/**
	 * The 404 action for the application.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_404()
	{
		return Response::forge($this->_theme->build('error',
					array(
					'error' => __('Page not found. You can use the search if you were looking for something!')
				)));
	}


	protected function error($error = null)
	{
		if (is_null($error))
		{
			return Response::forge($this->_theme->build('error', array('error' => __('We encountered an unexpected error.'))));
		}
		return Response::forge($this->_theme->build('error', array('error' => $error)));
	}


	public function action_page_mode($_mode = 'by_post')
	{
		$mode = $_mode === 'by_thread' ? 'by_thread' : 'by_post';
		$type = $this->_radix->archive ? 'archive' : 'board';
		Cookie::set('default_theme_page_mode_'.$type, $mode);

		Response::redirect($this->_radix->shortname);
	}


	public function action_page($page = 1)
	{
		$order = Cookie::get('default_theme_page_mode_'. ($this->_radix->archive ? 'archive' : 'board')) === 'by_thread'
			? 'by_thread' : 'by_post';

		$options = array(
			'per_page' => $this->_radix->threads_per_page,
			'per_thread' => 6,
			'order' => $order
		);

		return $this->latest($page, $options);
	}


	public function action_ghost($page = 1)
	{
		$options = array(
			'per_page' => $this->_radix->threads_per_page,
			'per_thread' => 6,
			'order' => 'ghost'
		);

		return $this->latest($page, $options);
	}


	protected function latest($page = 1, $options = array())
	{
		try
		{
			$board = Board::forge()->get_latest()->set_radix($this->_radix)->set_page($page)->set_options($options);

			// execute in case there's more exceptions to handle
			$board->get_comments();
			$board->get_count();
		}
		catch (\Model\BoardException $e)
		{
			return $this->error($e->getMessage());
		}

		if ($page > 1)
		{
			switch($options['order'])
			{
				case 'by_post':
					$order_string = __('Threads by latest replies');
					break;
				case 'by_thread':
					$order_string = __('Threads by creation');
					break;
				case 'ghost':
					$order_string = __('Threads by latest ghost replies');
					break;
			}

			$this->_theme->set_title(__('Page').' '.$page);
			$this->_theme->bind('section_title', $order_string.' - '.__('Page').' '.$page);
		}

		$this->_theme->bind(array(
			'is_page' => true,
			'board' => $board,
			'posts_per_thread' => $options['per_thread'] - 1,
			'order' => $options['order'],
			'pagination' => array(
				'base_url' => Uri::create(array($this->_radix->shortname, $options['order'])),
				'current_page' => $page,
				'total' => $board->get_count()
			)
		));

		if (!$this->_radix->archive)
		{
			$this->_theme->set_partial('tools_new_thread_box', 'tools_reply_box');
		}

		return Response::forge($this->_theme->build('board'));
	}



	public function action_thread($num = 0)
	{
		return $this->thread($num);
	}

	public function action_last50($num = 0)
	{
		Response::redirect($this->_radix->shortname.'/last/50/'.$num);
	}

	public function action_last($limit = 0, $num = 0)
	{
		if (!Board::is_natural($limit) || $limit < 1)
		{
			return $this->action_404();
		}

		return $this->thread($num, array('type' => 'last_x', 'last_limit' => $limit));
	}


	protected function thread($num = 0, $options = array())
	{
		$num = str_replace('S', '', $num);

		try
		{
			$board = Board::forge()->get_thread($num)->set_radix($this->_radix)->set_options($options);

			// execute in case there's more exceptions to handle
			$thread = $board->get_comments();
		}
		catch(\Model\BoardThreadNotFoundException $e)
		{
			return $this->post($num);
		}
		catch (\Model\BoardException $e)
		{
			return $this->error($e->getMessage());
		}

		// get the latest doc_id and latest timestamp for realtime stuff
		$latest_doc_id = $board->get_highest('doc_id')->doc_id;
		$latest_timestamp = $board->get_highest('timestamp')->timestamp;

		// check if we can determine if posting is disabled
		try
		{
			$thread_status = $board->check_thread_status();
		}
		catch (\Model\BoardThreadNotFoundException $e)
		{
			return $this->error();
		}

		$this->_theme->set_title(Radix::get_selected()->formatted_title.' &raquo; '.__('Thread').' #'.$num);
		$this->_theme->bind(array(
			'thread_id' => $num,
			'board' => $board,
			'is_thread' => TRUE,
			'disable_image_upload' => $thread_status['disable_image_upload'],
			'thread_dead' => $thread_status['dead'],
			'latest_doc_id' => $latest_doc_id,
			'latest_timestamp' => $latest_timestamp,
			'thread_op_data' => $thread[$num]['op']
		));

		if (!$thread_status['dead'] || ($thread_status['dead'] && !$this->_radix->disable_ghost))
		{
			$this->_theme->set_partial('tools_reply_box', 'tools_reply_box');
		}

		return Response::forge($this->_theme->build('board'));
	}

}
