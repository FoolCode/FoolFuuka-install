<?php
/**
 * Set error reporting and display errors settings.  You will want to change these when in production.
 */
error_reporting(-1);
ini_set('display_errors', 1);

/**
 * Website document root
 */
define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR);

/**
 * The path to the Composer vendor directory.
 */
define('VENDPATH', realpath(__DIR__.'/../vendor/').DIRECTORY_SEPARATOR);

/**
 * The "VENDOR APP" directory where live content can be stored
 */
define('VAPPPATH', realpath(__DIR__.'/../app/').DIRECTORY_SEPARATOR);

function e($string)
{
    return htmlentities($string);
}

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

// Boot the app
require VENDPATH.'autoload.php';

(new Foolz\Foolframe\Model\Context())->handleWeb();
