<?php

// we don't want to use the massive Security::htmlentities() function
function e($string)
{
	return htmlentities($string);
}

if (function_exists('_'))
{
	function __($text)
	{
		$text = _($text);
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
		return $text;
	}

	function _ngettext($msgid1, $msgid2, $n)
	{
		return ngettext($msgid1, $msgid2, $n);
	}
}
else
{
	function __($text)
	{
		$text = str_replace('{{FOOL_NAME}}', \Config::get('foolframe.main.name'), $text);
		return $text;
	}

	function _ngettext($msgid1, $msgid2, $n)
	{
		if($n !== 1)
			return __($msgid2);

		return __($msgid1);
	}
}