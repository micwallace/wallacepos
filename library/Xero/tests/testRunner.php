<?php

function testLinks()
{

    if (isset($_SESSION['access_token']) || XRO_APP_TYPE == 'Private')
        echo '<ul>
                <li><a href="?=1">Home</a></li>
                <li><a href="?organisation=1">Organisation</a></li>
                <li><a href="?organisation=1&request=json">Organisation (JSON response)</a></li>
                <li><a href="?accounts=1">Accounts GET</a></li>
                <li><a href="?accountsfilter=1">Accounts GET - Where Type is BANK</a></li>
                <li><a href="?banktransactions=1">BankTransactions GET</a></li>
                <li><a href="?banktransactions=1&method=put">BankTransactions PUT</a></li>
                <li><a href="?contacts=1">Contacts GET</a></li>
                <li><a href="?contacts=1&method=post">Contacts POST</a></li>
                <li><a href="?contacts=1&method=put">Contacts PUT</a></li>
                <li><a href="?payrollemployees=1">Payroll Employees GET</a></li>
                <li><a href="?payruns=1">Payroll Payruns GET</a></li>
                <li><a href="?invoice=1">Invoices GET (with order by Total example)</a></li>
                <li><a href="?invoicesfilter=1">Invoices GET - Where Contact Name contains "Martin"</a></li>
                <li><a href="?invoicesmodified=1">Invoices GET - If-Modified-Since</a></li>
                <li><a href="?invoice=1&method=put">Invoices PUT</a></li>
                <li><a href="?invoice=1&method=4dp">Invoices PUT (4 decimal places)</a></li>
                <li><a href="?invoice=1&method=post">Invoices POST</a></li>
                <li><a href="?invoice=attachment&method=put">Invoice attachment PUT</a></li>
                <li><a href="?invoice=pdf">Invoice PDF</a></li>
                <li><a href="?trialbalance=1">Trial Balance</a></li>
                <li><a href="?trackingcategories=1">Tracking Categories - GET</a></li>
                <li><a href="?trackingcategories=1&method=getarchived">Tracking Categories - GET (+ archived)</a></li>
                <li><a href="?trackingcategories=1&method=put">Tracking Categories - PUT</a></li>
                <li><a href="?trackingcategories=1&method=archive">Tracking Categories - ARCHIVE</a></li>
                <li><a href="?trackingcategories=1&method=restore">Tracking Categories - restore to active</a></li>';

        if (XRO_APP_TYPE == 'Partner')   echo '<li><a href="?refresh=1">Refresh access token</a></li>';
        if (XRO_APP_TYPE !== 'Private' && isset($_SESSION['access_token'])) {
            echo '<li><a href="?wipe=1">Start Over and delete stored tokens</a></li>';
        } elseif(XRO_APP_TYPE !== 'Private') {
            echo '<li><a href="?authenticate=1">Authenticate</a></li>';
            echo '<li><a href="?authenticate=2">Authenticate with Payroll API support (Australia & US organisations only)</a></li>';
        }


    echo '</ul>';

}


/**
 * Persist the OAuth access token and session handle somewhere
 * In my example I am just using the session, but in real world, this is should be a storage engine
 *
 * @param array $params the response parameters as an array of key=value pairs
 */
function persistSession($response)
{
    if (isset($response)) {
        $_SESSION['access_token']       = $response['oauth_token'];
        $_SESSION['oauth_token_secret'] = $response['oauth_token_secret'];
      	if(isset($response['oauth_session_handle']))  $_SESSION['session_handle']     = $response['oauth_session_handle'];
    } else {
        return false;
    }

}

/**
 * Retrieve the OAuth access token and session handle
 * In my example I am just using the session, but in real world, this is should be a storage engine
 *
 */
function retrieveSession()
{
    if (isset($_SESSION['access_token'])) {
        $response['oauth_token']            =    $_SESSION['access_token'];
        $response['oauth_token_secret']     =    $_SESSION['oauth_token_secret'];
        $response['oauth_session_handle']   =    $_SESSION['session_handle'];
        return $response;
    } else {
        return false;
    }

}

function outputError($XeroOAuth)
{
    echo 'Error: ' . $XeroOAuth->response['response'] . PHP_EOL;
    pr($XeroOAuth);
}

/**
 * Debug function for printing the content of an object
 *
 * @param mixes $obj
 */
function pr($obj)
{

    if (!is_cli())
        echo '<pre style="word-wrap: break-word">';
    if (is_object($obj))
        print_r($obj);
    elseif (is_array($obj))
        print_r($obj);
    else
        echo $obj;
    if (!is_cli())
        echo '</pre>';
}

function is_cli()
{
    return (PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']));
}
