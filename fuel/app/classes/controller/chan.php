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

	protected $_theme;


	public function before()
	{
		parent::before();

		header('X-UA-Compatible: IE=edge,chrome=1');
		header('imagetoolbar: false');
		$this->_theme = new \Theme();

		$this->_theme->set_theme('default');
		$this->_theme->set_layout('chan');

		$this->_theme->bind('disable_headers', FALSE);
		$this->_theme->bind('is_page', FALSE);
		$this->_theme->bind('is_thread', FALSE);
		$this->_theme->bind('is_last50', FALSE);
		$this->_theme->bind('order', FALSE);
		$this->_theme->bind('modifiers', array());
		$this->_theme->bind('backend_vars', array());

		$this->_theme->set_partial('tools_reply_box', 'tools_reply_box');
		$this->_theme->set_partial('tools_modal', 'tools_modal');
		$this->_theme->set_partial('tools_search', 'tools_search');
	}


	public function router($method, $params)
	{
		if (Radix::set_selected_by_shortname($method))
		{
			return call_user_func_array(array($this, 'action_'.array_shift($params)), $params);
		}
		else
		{
			return call_user_func_array(array($this, 'action_404'), $params);
		}
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
		return Response::forge('404', 404);
	}


	public function action_page($page = 1, $by_thread = FALSE, $options = array())
	{
		$page = intval($page);
		if ($page < 1)
		{
			return $this->action_404();
		}

		$options = (!empty($options)) ? $options :
			array(
			'per_page' => \Radix::get_selected()->threads_per_page,
			'type' => (\Cookie::get('default_theme_by_thread'.
				(\Radix::get_selected()->archive ? '_archive' : '_board')) ? 'by_thread' : 'by_post')
			);

		$board = new Board();
		$threads = $board->get_latest(Radix::get_selected(), $page, $options);

		$this->_theme->bind('is_page', TRUE);
		$this->_theme->set_title(\Radix::get_selected()->formatted_title.(($page > 1 ) ? ' &raquo; '.__('Page').' '.$page : ''));
		$this->_theme->bind('section_title',
			(($page > 1) ? (($by_thread ? __('Latest by Thread').' - ' : '').__('Page').' '.$page) : NULL));
		$this->_theme->bind('posts', $threads['result']);
		$this->_theme->bind('board', \Radix::get_selected());
		$this->_theme->bind('posts_per_thread', 5);
		$this->_theme->bind('pagination',
			array(
			'base_url' => Uri::create(array(\Radix::get_selected()->shortname, ($by_thread ? 'by_thread' : 'page'))),
			'current_page' => $page,
			'total' => $threads['pages']
		));
		$this->_theme->bind('order', $by_thread ? 'by_thread' : 'by_post');

		return Response::forge($this->_theme->build('board', array('')));
	}


	public function action_thread($num = 0, $limit = 0)
	{
		$num = intval(str_replace('S', '', $num));

		if($num <= 0)
		{
			return $this->action_404();
		}

		$board = new Board();
		$thread_data = $board->get_thread(\Radix::get_selected(), $num);
		$thread = $thread_data['result'];
		$thread_check = $thread_data['thread_check'];


		// don't throw 404, try looking for such a post
		if (!is_array($thread))
		{
			return $this->post($num);
		}

		// the post references wasn't op but it's a thread for sure
		if (!isset($thread[$num]['op']))
		{
			return $this->post($num);
		}

		// get the latest doc_id and latest timestamp
		$latest_doc_id = (isset($thread[$num]['op'])) ? $thread[$num]['op']->doc_id : 0;
		$latest_timestamp = (isset($thread[$num]['op'])) ? $thread[$num]['op']->timestamp : 0;
		if (isset($thread[$num]['posts']))
		{
			foreach ($thread[$num]['posts'] as $post)
			{
				if ($latest_doc_id < $post->doc_id)
				{
					$latest_doc_id = $post->doc_id;
				}

				if ($latest_timestamp < $post->timestamp)
				{
					$latest_timestamp = $post->timestamp;
				}
			}
		}

		// check if we can determine if posting is disabled
		$tools_reply_box = TRUE;
		$disable_image_upload = FALSE;
		$thread_dead = FALSE;

		// no image posting in archive, hide the file input
		if(Radix::get_selected()->archive)
		{
			$disable_image_upload = TRUE;
		}

		// in the archive you can only ghostpost, so it's an easy check
		if(Radix::get_selected()->archive && Radix::get_selected()->disable_ghost)
		{
			$tools_reply_box = FALSE;
		}
		else
		{
			// we're only interested in knowing if we should display the reply box
			if(isset($thread_check['ghost_disabled']) && $thread_check['ghost_disabled'] == TRUE)
				$tools_reply_box = FALSE;

			if(isset($thread_check['disable_image_upload']) && $thread_check['disable_image_upload'] == TRUE)
				$disable_image_upload = TRUE;

			if (isset($thread_check['thread_dead']) && $thread_check['thread_dead'] == TRUE)
				$thread_dead = TRUE;
		}

		$this->_theme->set_title(Radix::get_selected()->formatted_title . ' &raquo; ' . __('Thread') . ' #' . $num);
		$this->_theme->bind('thread_id', $num);
		$this->_theme->bind('posts', $thread);
		$this->_theme->bind('board', \Radix::get_selected());
		$this->_theme->bind('is_thread', TRUE);
		$this->_theme->bind('disable_image_upload', $disable_image_upload);
		$this->_theme->bind('thread_dead', $thread_dead);
		$this->_theme->bind('thread_id', $num);
		$this->_theme->bind('thread_id', $num);
		if($tools_reply_box) $this->_theme->set_partial('tools_reply_box', 'tools_reply_box');

			array(
				'thread_id' => $num,
				'latest_doc_id' => $latest_doc_id,
				'latest_timestamp' => $latest_timestamp,
				'thread_op_data' => $thread[$num]['op']
			);

		return Response::forge($this->_theme->build('board'));

	}
}
