<?php

namespace Model;


class Notices extends \Model
{

	private static $_flash_notices = array();
	private static $_notices = array();


	public static function flash()
	{
		$array = \Session::get_flash('notices');
		return is_array($array)?$array:array();
	}


	public static function set_flash($level, $message)
	{
		self::$_flash_notices[] = array('level' => $level, 'message' => $message);
		\Session::set_flash('notices', self::$_flash_notices);
	}

	public static function get()
	{
		return self::$_notices;
	}

	public static function set($level, $message)
	{
		self::$_notices[] = array('level' => $level, 'message' => $message);
	}

}

/* end of file preferences.php */