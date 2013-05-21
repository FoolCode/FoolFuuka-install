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
 * Path to the application directory.
 */
define('APPPATH', realpath(__DIR__.'/../fuel/app/').DIRECTORY_SEPARATOR);

/**
 * Path to the default packages directory.
 */
define('PKGPATH', realpath(__DIR__.'/../fuel/packages/').DIRECTORY_SEPARATOR);

/**
 * The path to the framework core.
 */
define('COREPATH', realpath(__DIR__.'/../fuel/core/').DIRECTORY_SEPARATOR);

/**
 * The path to the Composer vendor directory.
 */
define('VENDPATH', realpath(__DIR__.'/../vendor/').DIRECTORY_SEPARATOR);

/**
 * The "VENDOR APP" directory where live content can be stored
 */
define('VAPPPATH', realpath(__DIR__.'/../app/').DIRECTORY_SEPARATOR);

// Get the start time and memory for use later
defined('FUEL_START_TIME') or define('FUEL_START_TIME', microtime(true));
defined('FUEL_START_MEM') or define('FUEL_START_MEM', memory_get_usage());

// Boot the app
require VENDPATH.'autoload.php';

require APPPATH.'bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Foolz\Foolframe\Model\Framework;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

Debug::enable();
ErrorHandler::register();
ExceptionHandler::register();

$request = Request::createFromGlobals();

new Framework($request);