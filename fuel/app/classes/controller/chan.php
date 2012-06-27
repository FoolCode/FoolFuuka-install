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

		$this->_theme->bind('is_page', FALSE);
		$this->_theme->bind('disable_headers', FALSE);
		$this->_theme->bind('enabled_tools_modal', FALSE);
		$this->_theme->bind('backend_vars', FALSE);

		$this->_theme->set_partial('tools_search', 'tools_search');
	}


	public function router($method, $params)
	{
		if(Radix::set_selected_by_shortname($method))
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


	public function action_latest()
	{
		$board = new Board();
		$thread = $board->get_latest(Radix::get_selected());
		//Debug::dump($board);
		//return Response::forge(var_)
		return Response::forge(Debug::dump($board));
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
