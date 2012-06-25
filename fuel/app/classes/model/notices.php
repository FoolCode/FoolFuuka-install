<?php

namespace Model;


class Notices extends \Model
{

	private static $_flash_notices = array();
	private static $_notices = array();


	public static function flash()
	{
		return Session::get_flash('notices');
	}


	public static function set_flash($level, $message)
	{
		self::$_flash_notices[] = array('level' => $level, 'message' => $message);
		Session::get_flash('notices', self::$_flash_notices);
	}

	public static function get()
	{
		return self::$_notices;
	}

	public static function set()
	{
		self::$_notices[] = array('level' => $level, 'message' => $message);
	}

}

/* end of file preferences.php */