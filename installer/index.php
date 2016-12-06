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
ini_set('display_errors', 'On');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$_SERVER['APP_ROOT'] = "/";
require($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/AutoLoader.php'); //Autoload all the classes.

function checkDependencies(){
    $result = [
        "apache"=>true,
        "php"=>true,
        "node"=>true,
        "all"=>true
    ];

    // check app root
    if (!$result['app_root'] = file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/AutoLoader.php'))
        $result['all'] = false;

    // check apache version
    $version = str_replace("Apache/", "", apache_get_version());
    $version = str_replace(" (Ubuntu)", "", $version);
    if (version_compare($result['apache_version']=$version, "2.4.7")<0){
        $result['all'] = false;
        $result['apache'] = false;
    }

    // check php version
    $phpversion = phpversion();
    if (strpos($phpversion, "-")!==false)
        $phpversion = explode("-", $phpversion)[0];
    if (version_compare($result['php_version']=$phpversion, "5.4")<0){
        $result['all'] = false;
        $result['php'] = false;
    }

    // check node installation
	chdir($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'api/'); // node extension gets installed along with server.js
	$nodeextensions = json_decode(shell_exec("npm ls --json")); // detect node.js existence using npm
	if ($nodeextensions == NULL) {
		$result['node'] = false;
		$result['node_socketio'] = false;
		$result['all'] = false;
	} else {
        if (!$result['node_socketio']=(isset($nodeextensions->dependencies->{"socket.io"}) && !$nodeextensions->dependencies->{"socket.io"}->missing))
            $result['all'] = false;
	}

    // required apache modules
    $apache_mods = apache_get_modules();
    if (!$result['apache_wstunnel']=in_array("mod_proxy_wstunnel", $apache_mods))
        $result['all'] = false;
    if (!$result['apache_rewrite']=in_array("mod_rewrite", $apache_mods))
        $result['all'] = false;

    // required php extensions
    $php_mods = get_loaded_extensions();
    if (!$result['php_pdomysql']=in_array("pdo_mysql", $php_mods))
        $result['all'] = false;
    if (!$result['php_curl']=in_array("curl", $php_mods))
        $result['all'] = false;
    if (!$result['php_gd']=in_array("gd", $php_mods))
        $result['all'] = false;

    // folder permissions (needed for installing docs folder)
    if (!$result['permissions_root']=is_writable($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']))
        $result['all'] = false;

    // file_permissions
    $result['permissions_files'] = true;
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']));
    foreach($objects as $name => $object){
        if (strpos($name, $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs')===false &&
            $name!=$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.config.json' &&
            $name!=$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.dbconfig.json' &&
            (is_file($name) && is_writable($name))){
            $result['permissions_files'] = false;
            $result['all'] = false;
            break;
        }
    }
    $result['permissions_docs'] = true;
    if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/')) {
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/'));
        foreach ($objects as $name => $object) {
            if (strpos($name, $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/templates')===false && !is_writable($name)) {
                $result['permissions_docs'] = false;
                $result['all'] = false;
                break;
            }
        }
    }
    if (!$result['permissions_config']=(!is_writable($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.config.json') ||
        !is_writable($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.dbconfig.json')))
        $result['all'] = false;

    // apache node.js config
    if ($result['php_curl']){
        $handle = curl_init((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!="off"?"https":"http").$_SERVER['SERVER_NAME']."/");
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if($httpCode == 404 || $httpCode == 500) {
            $result['node_redirect'] = false;
            $result['all'] = false;
        } else {
            $result['node_redirect'] = true;
        }
        curl_close($handle);
    } else {
        $result['node_redirect'] = false;
    }

    //print_r($result);
    return $result;
}

function getCurrentVersion(){
    try {
        $settings = WposAdminSettings::getSettingsObject("general");
        if (isset($settings->version))
            return $settings->version;
    } catch (Exception $ex){
    }
    return 0;
}

function writeDatabaseConfig(){
    $dbconfig = [
        "host"=> $_REQUEST['host'],
        "port"=> $_REQUEST['port'],
        "database"=> $_REQUEST['database'],
        "user"=> $_REQUEST['username'],
        "pass"=> $_REQUEST['password']
    ];
    if (!file_put_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.dbconfig.json', json_encode($dbconfig))){
        return false;
    }
    return true;
}

function addAnalytics($type){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://admin.wallaceit.com.au/customerapi/stats/add/'.$type.'?hostname='.$_SERVER['SERVER_NAME'].'&version='.(isset($_REQUEST['version']) ? $_REQUEST['version'] : DbUpdater::getLatestVersionName()),
        CURLOPT_USERAGENT => 'WallacePOS_Installer'
    ));
    curl_exec($curl);
    curl_close($curl);
}

session_start();
// installer scripts
// update
if (isset($_REQUEST['upgrade'])){
    $dbUpdater = new DbUpdater();
    $result = $dbUpdater->upgrade((isset($_REQUEST['version']) ? $_REQUEST['version'] : null));
    // register analytics
    addAnalytics("upgrade");
    echo($result);
    exit;
}
// install
if (isset($_REQUEST['install'])){
    if (isset($_SESSION['setupvars']))
        $_REQUEST['setupvars'] = $_SESSION['setupvars'];
    $dbUpdater = new DbUpdater();
    $result = $dbUpdater->install();
    // register analytics
    addAnalytics("install");
    echo($result);
    exit;
}
// gui installer
if (!isset($_SESSION['install_screen'])){
    $_SESSION['install_screen'] = 1;
} else {
    if (isset($_REQUEST['screen']) && $_REQUEST['screen']!=""){
        $_SESSION['install_screen'] = $_REQUEST['screen'];
    }
}

if (isset($_REQUEST['checkdb'])){
    // check database config, if successful write config file and proceed to the next screen
    $dbresult = DbConfig::testConf($_REQUEST['host'], $_REQUEST['port'], $_REQUEST['database'], $_REQUEST['username'], $_REQUEST['password']);
    if ($dbresult===true){
        if (writeDatabaseConfig()){
            $_SESSION['install_screen'] = 3;
        } else {
            $errormessage = "Failed to write database configuration file";
        }
    } else {
        $errormessage = $dbresult;
    }
}

if (isset($_REQUEST['doinstall'])){
    $_SESSION['setupvars'] = json_encode(["adminhash"=>hash('sha256', $_REQUEST['password'])]);
    $_SESSION['install_screen'] = 4;
}

if (isset($_REQUEST['doupgrade'])){
    $_SESSION['setupvars'] = json_encode(["adminhash"=>hash('sha256', $_REQUEST['password'])]);
    $_SESSION['install_screen'] = 4;
}

switch($_SESSION['install_screen']){
    case 1: // Check Dependencies
        $deps = checkDependencies();
        $curversion = getCurrentVersion();
        include "views/header.html";
        include "views/requirements.php";
        include "views/footer.html";
        break;
    case 2: // Configure database
        include "views/header.html";
        include "views/database.php";
        include "views/footer.html";
        break;
    case 3: // Configure system
        include "views/header.html";
        include "views/setup.php";
        include "views/footer.html";
        break;
    case 4: // Install system
        include "views/header.html";
        include "views/install.php";
        include "views/footer.html";
        $_SESSION['install_screen'] = 1; // reset installer
        break;
}
