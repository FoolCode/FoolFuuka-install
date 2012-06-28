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


	public function action_latest($page = 1, $by_thread = FALSE, $options = array())
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
		$this->_theme->bind('posts_per_thread', 5);
		$this->_theme->bind('pagination', array(
			'base_url' => Uri::create(array(\Radix::get_selected()->shortname, ($by_thread ? 'by_thread' : 'page'))),
			'current_page' => $page,
			'total' => $threads['pages']
		));
		$this->_theme->bind('order', $by_thread ? 'by_thread' : 'by_post');

		return Response::forge($this->_theme->build('board', array('')));
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

}
