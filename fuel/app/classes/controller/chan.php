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
			return $this->action_post($num);
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


	public function action_gallery($page = 1)
	{
		try
		{
			$board = Board::forge()->get_threads()->set_radix($this->_radix)->set_page($page)
				->set_options('per_page', 100);

			$comments = $board->get_comments();
		}
		catch (\Model\BoardException $e)
		{
			return $this->error($e->getMessage());
		}

		$this->_theme->bind('board', $board);
		return Response::forge($this->_theme->build('gallery'));

	}


	public function action_post($num)
	{
		try
		{
			$board = Board::forge()->get_post()->set_radix($this->_radix)->set_options('num', $num);

			$comments = $board->get_comments();
		}
		catch (\Model\BoardMalformedInputException $e)
		{
			return $this->error(__('The post number you submitted is invalid.'));
		}
		catch (\Model\BoardPostNotFoundException $e)
		{
			return $this->error(__('The post you are looking for does not exist.'));
		}

		// it always returns an array
		$comment = $comments[0];

		$redirect =  Uri::create($this->_radix->shortname.'/thread/'.$comment->thread_num.'/');

		if (!$comment->op)
		{
			$redirect .= '#'.$comment->num.($comment->subnum ? '_'.$comment->subnum :'');
		}

		$this->_theme->set_title(__('Redirecting'));
		$this->_theme->set_layout('redirect');
		return Response::forge($this->_theme->build('redirect', array('url' => $redirect)));
	}


	/**
	 * Display all of the posts that contain the MEDIA HASH provided.
	 * As of 2012-05-17, fetching of posts with same media hash is done via search system.
	 * Due to backwards compatibility, this function will still be used for non-urlsafe and urlsafe hashes.
	 */
	public function action_image()
	{
		// support non-urlsafe hash
		$uri = Uri::segments();
		array_shift($uri);
		array_shift($uri);

		$imploded_uri = rawurldecode(implode('/', $uri));
		if (mb_strlen($imploded_uri) < 22)
		{
			return $this->error(__('Your image hash is malformed.'));
		}

		// obtain actual media hash (non-urlsafe)
		$hash = mb_substr($imploded_uri, 0, 22);
		if (strpos($hash, '/') !== false || strpos($hash, '+') !== false)
		{
			$hash = Comment::get_media_hash(true, $hash);
		}

		// Obtain the PAGE from URI.
		$page = 1;
		if (mb_strlen($imploded_uri) > 28)
		{
			$page = substr($imploded_uri, 28);
		}

		// Fetch the POSTS with same media hash and generate the IMAGEPOSTS.
		$page = intval($page);
		Response::redirect(Uri::create(array(
			get_selected_radix()->shortname, 'search', 'image', $hash, 'order', 'desc', 'page', $page))
			, 'location', 301);
	}
}
