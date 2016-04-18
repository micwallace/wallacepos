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
// load timezone config if available
// TODO: cache this somehow
$timezone = "Australia/Sydney";
if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/wpos/.config.json")){
    $GLOBALS['config'] = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/wpos/.config.json"));
    if (isset($GLOBALS['config']->timezone))
        $timezone = $GLOBALS['config']->timezone;
}
// Date & Time
//putenv("WPOS_TIMEZONE=".$timezone);
ini_set('date.timezone', $timezone);

// Error handling
ini_set('display_errors', 'On');
error_reporting(E_ERROR | E_WARNING | E_PARSE);




