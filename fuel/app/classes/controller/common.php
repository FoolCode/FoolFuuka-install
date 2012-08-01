<?php


class Controller_Common extends Controller
{

	public function before()
	{
		if (!\Config::get('foolframe.install.installed'))
		{
			throw new HttpNotFoundException;
		}

		parent::before();
	}
	

}