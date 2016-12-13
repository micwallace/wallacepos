<?php
/**
 * customerapi.php is part of Wallace Point of Sale system (WPOS) API
 *
 * customerapi.php is used to route incoming customer API requests and provide authentication control
 * It also allows the processing of multiple api requests in one go.
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
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      Class created 15/1/13 12:01 PM
 */

$_SERVER['APP_ROOT'] = "/";

require($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/config.php');
// setup api error handling
set_error_handler("errorHandler", E_ERROR | E_PARSE);
set_error_handler("warningHandler", E_WARNING);
set_exception_handler("exceptionHandler");

// load classes and start session
require($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/AutoLoader.php'); //Autoload all the classes.
$auth = new Auth();
// enable cross origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: OPTIONS, POST, GET");

if (!isset($_REQUEST['a'])) {
    exit;
}
$result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

// Check for auth request
if ($_REQUEST['a'] == "auth") {
    $data = json_decode($_REQUEST['data']);
    if ($data !== false) {
        if (($authres = $auth->customerLogin($data->username, $data->password)) === true) {
            $result['data'] = $auth->getCustomer();
        } else if ($authres == -1) {
            $result['errorCode'] = "authdenied";
            $result['error'] = "Your account has been disabled, please contact your system administrator!";
        } else if ($authres == -2) {
            $result['errorCode'] = "authdenied";
            $result['error'] = "Your account has not yet been activated, please activate your account or reset your password.";
        } else {
            $result['errorCode'] = "authdenied";
            $result['error'] = "Access Denied!";
        }
    } else {
        $result['errorCode'] = "jsondec";
        $result['error'] = "Error decoding the json request!";
    }
    returnResult($result);
} else if ($_REQUEST['a'] == "logout") {
    $auth->logout();
    returnResult($result);
}
// the hello request checks server connectivity aswell as providing the status of the logged in user
if ($_REQUEST['a'] == "hello") {
    $result['data'] = new stdClass();
    if ($auth->isCustomerLoggedIn()) {
        $result['data']->user = $auth->getCustomer();
    } else {
        $result['data']->user = false;
    }
    // unlike other hello requests, this also provide some current business info.
    $conf = WposAdminSettings::getSettingsObject('general');
    $result['data']->bizname = $conf->bizname;
    $result['data']->bizlogo = $conf->bizlogo;

    returnResult($result);
}
// Decode JSON data if provided
if ($_REQUEST['data']!=""){
    if (($requests=json_decode($_REQUEST['data']))==false){
        $result['error'] = "Could not parse the provided json request";
        returnResult($result);
    }
} else {
    $requests = new stdClass();
}
// Route the provided requests
if ($_REQUEST['a']!=="multi"){
    // route a single api call
    $result = routeApiCall($_REQUEST['a'], $requests, $result);
} else {
    // run a multi api call
    if (empty($requests)){
        $result['error'] = "No API request data provided";
        returnResult($result);
    }
    // loop through each request, stop & return the first error if encountered
    foreach ($requests as $action=>$data){
        if ($data==null) {
            $data = new stdClass();
        }
        $tempresult = routeApiCall($action, $data, $result);
        if ($tempresult['error']=="OK"){
            // set data and move to the next request
            $result['data'][$action] = $tempresult['data'];
        } else {
            $result['error'] = $tempresult['error'];
            break;
        }
    }
}
returnResult($result);

// API FUNCTIONS
/**
 * routes api calls and returns the result, allows for multiple API calls at once
 * @param $action
 * @param $data
 * @param $result
 * @return array|mixed
 */
function routeApiCall($action, $data, $result) {
    global $auth;
    $notinprev = false;
    switch ($action){
        case 'register':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->register($result);
            break;
        case 'resetpasswordemail':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->sendResetPasswordEmail($result);
            break;
        case 'resetpassword':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->doPasswordReset($result);
            break;
        case 'config':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->getSettings($result);
            break;
        //case 'sales/dopaypalpayment':
            //$wCust = new WposEccomerce($data);
            //$result = $wCust->doPaypalTransaction($result);
            //break;
        default:
            $notinprev = true;
    }
    if ($notinprev == false) { // an action has been executed: return the data
        return $result;
    }
    // check login status and exit if not logged in
    if (!$auth->isCustomerLoggedIn()) {
        $result['errorCode'] = "auth";
        $result['error'] = "Access Denied!";
        return $result;
    }
    // Check for action in unprotected area (does not use permission system)
    switch ($action) {
        case 'mydetails/get':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->getCurrentCustomerDetails($result);
            break;
        case 'mydetails/save':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->saveCustomerDetails($result);
            break;
        case 'transactions/get':
            $wCust = new WposCustomerAccess($data);
            $result = $wCust->getCustomerTransactions($result);
            break;
        case 'invoice/generate':
            $wCust = new WposCustomerAccess();
            $wCust->generateCustomerInvoice($_REQUEST['id']);
            break;
        default:
            $result["error"] = "Action not defined: ".$action;
            break;
    }
    return $result;
}
/**
 * Encodes and returns the json result object
 * @param $result
 */
function returnResult($result){
    if (($resstr = json_encode($result)) === false) {
        echo(json_encode(["error" => "Failed to encode the reponse data into json"]));
    } else {
        echo($resstr);
    }
    die();
}

?>
