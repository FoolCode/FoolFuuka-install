<?php

// we don't want to use the massive Security::htmlentities() function
function e($string)
{
	return htmlentities($string);
}

if (function_exists('_'))
{
	function _i()
	{
		$argc = func_num_args();
		$args = func_get_args();
		$args[0] = gettext($args[0]);

		if ($argc <= 1)
		{
			return $args[0];
		}

		return call_user_func_array('sprintf', $args);
	}

	function _n()
	{
		$args = func_get_args();
		$args[0] = ngettext($args[0], $args[1], $args[2]);

		array_splice($args, 1, 1);

		return call_user_func_array('sprintf', $args);
	}

	/**
	 * @deprecated
	 */
	function __($text)
	{
		$text = _($text);
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
		return $text;
	}

	/**
	 * @deprecated
	 */
	function _ngettext($msgid1, $msgid2, $n)
	{
		return ngettext($msgid1, $msgid2, $n);
	}
}
else
{
	function _i()
	{
		$argc = func_num_args();
		$args = func_get_args();

		if ($argc <= 1)
		{
			return $args[0];
		}

		return call_user_func_array('sprintf', $args);
	}

	function _n()
	{
		$args = func_get_args();
		$args[0] = ($args[2] != 1) ? $args[1] : $args[0];

		array_splice($args, 1, 1);

		return call_user_func_array('sprintf', $args);
	}

	/**
	 * @deprecated
	 */
	function __($text)
	{
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
		return $text;
	}

	/**
	 * @deprecated
	 */
	function _ngettext($msgid1, $msgid2, $n)
	{
		if($n !== 1)
			return __($msgid2);

		return __($msgid1);
	}
}