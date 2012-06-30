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

		header('X-UA-Compatible: IE=edge,chrome=1');
		header('imagetoolbar: false');
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

		$this->_theme->set_partial('tools_reply_box', 'tools_reply_box');
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


	public function error($error)
	{
		return Response::forge($this->_theme->build('error', array('error' => $error)));
	}


	public function action_page($page = 1, $by_thread = FALSE, $options = array())
	{
		if (empty($options))
		{
			$options['per_page'] = $this->_radix->threads_per_page;
			$options['per_thread'] = 6;
			$options['order'] = Cookie::get('default_theme_by_thread'.
					($this->_radix->archive ? '_archive' : '_board')) ? 'by_thread' : 'by_post';
		}

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
			$this->_theme->set_title(__('Page').' '.$page);
			$this->_theme->bind('section_title', ($by_thread ? __('Latest by Thread').' - ' : '').__('Page').' '.$page);
		}

		$this->_theme->bind(array(
			'is_page' => true,
			'board' => $board,
			'posts_per_thread' => $options['per_thread'] - 1,
			'order' => $by_thread ? 'by_thread' : 'by_post',
			'pagination' => array(
				'base_url' => Uri::create(array($this->_radix->shortname, ($by_thread ? 'by_thread' : 'page'))),
				'current_page' => $page,
				'total' => $board->get_count()
			)
		));

		return Response::forge($this->_theme->build('board'));
	}


	public function action_thread($num = 0, $limit = 0)
	{
		$num = str_replace('S', '', $num);

		try
		{
			$board = Board::forge()->get_thread($num)->set_radix($this->_radix);

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

		// get the latest doc_id and latest timestamp
		$latest_doc_id = $board->get_highest('doc_id');
		$latest_timestamp = $board->get_highest('timestamp');

		// check if we can determine if posting is disabled
		$tools_reply_box = TRUE;
		$disable_image_upload = FALSE;
		$thread_dead = FALSE;

		// no image posting in archive, hide the file input
		if (Radix::get_selected()->archive)
		{
			$disable_image_upload = TRUE;
		}

		// in the archive you can only ghostpost, so it's an easy check
		if (Radix::get_selected()->archive && Radix::get_selected()->disable_ghost)
		{
			$tools_reply_box = FALSE;
		}
		else
		{
			// we're only interested in knowing if we should display the reply box
			if (isset($thread_check['ghost_disabled']) && $thread_check['ghost_disabled'] == TRUE)
				$tools_reply_box = FALSE;

			if (isset($thread_check['disable_image_upload']) && $thread_check['disable_image_upload'] == TRUE)
				$disable_image_upload = TRUE;

			if (isset($thread_check['thread_dead']) && $thread_check['thread_dead'] == TRUE)
				$thread_dead = TRUE;
		}

		$this->_theme->set_title(Radix::get_selected()->formatted_title.' &raquo; '.__('Thread').' #'.$num);
		$this->_theme->bind('thread_id', $num);
		$this->_theme->bind('board', $board);
		$this->_theme->bind('is_thread', TRUE);
		$this->_theme->bind('disable_image_upload', $disable_image_upload);
		$this->_theme->bind('thread_dead', $thread_dead);
		if ($tools_reply_box)
			$this->_theme->set_partial('tools_reply_box', 'tools_reply_box');

		$this->_theme->bind(array(
			'thread_id' => $num,
			'latest_doc_id' => $latest_doc_id,
			'latest_timestamp' => $latest_timestamp,
			'thread_op_data' => $thread[$num]['op']
		));

		return Response::forge($this->_theme->build('board'));
	}

}
