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

	public function before()
	{
		header('X-UA-Compatible: IE=edge,chrome=1');
		header('imagetoolbar: false');
		$this->_theme = new \Theme();



		$this->_theme->set_theme('default');
		$this->_theme->set_layout('chan');
	}

	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$this->_theme->bind('is_page', FALSE);
		$this->_theme->bind('disable_headers', FALSE);
		$this->_theme->bind('is_statistics', FALSE);
		$this->_theme->bind('enabled_tools_modal', FALSE);
		return $this->_theme->build('index');//Response::forge('herez');
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
