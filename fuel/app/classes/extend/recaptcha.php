<?php

use Foolz\Foolframe\Model\Preferences;

class ReCaptcha extends \ReCaptcha\ReCaptcha
{

	public static function _init()
	{
		parent::_init();

		\Config::set('recaptcha.public_key', Preferences::get('foolframe.auth.recaptcha_public', ''));
		\Config::set('recaptcha.private_key', Preferences::get('foolframe.auth.recaptcha_private', ''));
	}


	public static function available()
	{
		return Preferences::get('foolframe.auth.recaptcha_private', false) && Preferences::get('foolframe.auth.recaptcha_private', false);
	}
}
