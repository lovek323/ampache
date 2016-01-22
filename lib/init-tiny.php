<?php

// Minimal init for use in install

// Do a check for PHP5.4 because nothing will work without it
if (version_compare(phpversion(), '5.4.0', '<')) {
    echo "ERROR: Ampache requires PHP version >= 5.4";
    exit;
}

error_reporting(E_ERROR); // Only show fatal errors in production

$load_time_begin = microtime(true);

$ampache_path = dirname(__FILE__);
$prefix       = realpath($ampache_path . "/../");
$configfile   = $prefix . '/config/ampache.cfg.php';

// We still allow scripts to run (it could be the purpose of the maintenance)
if (!defined('CLI')) {
    if (file_exists($prefix . '/.maintenance')) {
        require_once($prefix . '/.maintenance');
    }
}

require_once $prefix . '/lib/general.lib.php';
require_once $prefix . '/lib/class/ampconfig.class.php';
require_once $prefix . '/lib/class/core.class.php';

// Define some base level config options
AmpConfig::set('prefix', $prefix);

// Register autoloaders
spl_autoload_register(['Core', 'autoload'], true, true);
$composer_autoload = $prefix . '/lib/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
    require_once $prefix . '/lib/vendor/Afterster/php-echonest-api/lib/EchoNest/Autoloader.php';
    EchoNest_Autoloader::register();
}

// Check to see if this is http or https
if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
    || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
    $http_type = 'https://';
} else {
    $http_type = 'http://';
}

if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
    $http_port = $_SERVER['HTTP_X_FORWARDED_PORT'];
} else {
    if (isset($_SERVER['SERVER_PORT'])) {
        $http_port = $_SERVER['SERVER_PORT'];
    }
}
if (!isset($http_port) || empty($http_port)) {
    $http_port = 80;
}

// Define that we've loaded the INIT file
define('INIT_LOADED', 1);

// Core includes we can't do with the autoloader
require_once $prefix . '/lib/preferences.php';
require_once $prefix . '/lib/debug.lib.php';
require_once $prefix . '/lib/log.lib.php';
require_once $prefix . '/lib/ui.lib.php';
require_once $prefix . '/lib/i18n.php';
require_once $prefix . '/lib/batch.lib.php';
require_once $prefix . '/lib/themes.php';
require_once $prefix . '/lib/class/localplay_controller.abstract.php';
require_once $prefix . '/lib/class/AbstractDatabaseObject.php';
require_once $prefix . '/lib/class/mediaInterface.interface.php';
require_once $prefix . '/lib/class/PlayableItemInterface.php';
require_once $prefix . '/lib/class/libraryItemInterface.interface.php';
require_once $prefix . '/modules/horde/Browser.php';

/* Set up the flip class */
UI::flip_class(['odd', 'even']);

// Merge GET then POST into REQUEST effectively stripping COOKIE without
// depending on a PHP setting change for the effect
$_REQUEST = array_merge($_GET, $_POST);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
