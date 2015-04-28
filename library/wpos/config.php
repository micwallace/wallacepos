<?php
/*
 * WallacePOS API configuration file
 */
$wposConfig = [];

// Paths
$_SERVER['APP_ROOT'] = "/";
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = "/app"; // this is what dokku uses as docroot, for some reason it's not set
}

// Date & Time
ini_set('date.timezone', 'Australia/Sydney');

// Error handling
ini_set('display_errors', 'On');
error_reporting(E_ERROR | E_WARNING | E_PARSE);




