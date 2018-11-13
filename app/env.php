<?php

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 */

$environment = trim(strtolower(getenv('ENVIRONMENT')));

if (empty($environment)) {
    $environment = trim(strtolower(getenv('REDIRECT_ENVIRONMENT')));
}

if (PHP_SAPI === 'cli' && extension_loaded('xdebug')) {
    $environment = 'local';
}

if (empty($environment)) {
    $environment = 'production';
}

define('ENVIRONMENT', $environment);

/*
 *---------------------------------------------------------------
 * SITE
 *---------------------------------------------------------------
 */

$site = trim(strtolower(getenv('SITE')));

if (empty($site)) {
    $site = trim(strtolower(getenv('REDIRECT_SITE')));
}

if (empty($site)) {
    $site = trim(strtolower(getenv('HTTP_HOST')));
}

define('SITE', $site);

/*
 *---------------------------------------------------------------
 * PROTOCOL
 *---------------------------------------------------------------
 */

$protocol = 'http';

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $protocol = 'https';
}

define('PROTOCOL', $protocol);
