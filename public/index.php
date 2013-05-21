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

// Boot the app
require VENDPATH.'autoload.php';

require APPPATH.'bootstrap.php';

(new Foolz\Foolframe\Model\Framework())->handleWeb();