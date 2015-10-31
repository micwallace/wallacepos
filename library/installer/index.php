<?php
/**
 * installer is part of Wallace Point of Sale system (WPOS) API
 *
 * installer/index.php allows upgrading of the database. It's kept separate from other API functions
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)

 * @link       https://wallacepos.com
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      File available since 14/12/13 07:46 PM
 */
die("Please manually enable the installer before proceeding");

$_SERVER['APP_ROOT'] = "/";
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = "/app"; // this is what dokku uses as docroot, we can catch it because it ain't defined (not using mod_php);
}
if (php_sapi_name() == "cli"){
    $_SERVER['DOCUMENT_ROOT'] = "/app"; // explicitly define docroot
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}
require($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/AutoLoader.php'); //Autoload all the classes.
$dbUpdater = new DbUpdater();
// update
if (isset($_REQUEST['upgrade']) && isset($_REQUEST['version'])){
    $result = $dbUpdater->upgrade($_REQUEST['version']);
    echo($result);
}
// install
if (isset($_REQUEST['install'])){
    $result = $dbUpdater->install();
    echo($result);
}

die("<br/>Please remove the install/index.php file after installing, leaving it after installation is dangerous");