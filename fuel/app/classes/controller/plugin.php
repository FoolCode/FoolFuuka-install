<?php


class Controller_Plugin extends Controller
{

	public function router($method, $params)
	{
		// first the ID, then the params modeled against the $map in \Plugins::register_route($path, $map, $method)
		return \Plugins::run_controller(array_shift($params), $params);
	}

}