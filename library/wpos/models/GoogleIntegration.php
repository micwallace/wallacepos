<?php
/**
 * GoogleIntergration is part of Wallace Point of Sale system (WPOS) API
 *
 * GoogleIntergration is used to provide wrapper functions for interacting with the Google API
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
 * @since      File available since 12/04/14 3:44 PM
 */

class GoogleIntegration {
    private static $client_id = '454006942772-d44o95d02ctqpfa341sbm9gj23kif5e2.apps.googleusercontent.com';
    private static $client_secret = 'i7zxnfJuTv58iwZgQFyiXuhw';
    private static $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';

    public static function initGoogleAuth(){
        require_once $_SERVER['DOCUMENT_ROOT'].'/library/Google/Client.php';
        $client = new Google_Client();
        $client->setClientId(self::$client_id);
        $client->setClientSecret(self::$client_secret);
        $client->setRedirectUri(self::$redirect_uri);
        $client->setAccessType('offline');
        $client->setScopes('https://www.google.com/m8/feeds');
        // Request token from google
        $authUrl = $client->createAuthUrl();
        // Redirect to oauth url
        header("Location: ".$authUrl);
        exit;
    }

    /**
     * Get an auth code using the given token
     * @param $code
     * @return string
     */
    public static function processGoogleAuthCode($code){
        require_once $_SERVER['DOCUMENT_ROOT'].'/library/Google/Client.php';
        $client = new Google_Client();
        $client->setClientId(self::$client_id);
        $client->setClientSecret(self::$client_secret);
        $client->setRedirectUri(self::$redirect_uri);
        // exchange auth code for access & refresh tokens
        $client->authenticate($code);
        $tokens = $client->getAccessToken();
        // return tokens
        return $tokens;
    }

    public static function removeGoogleAuth(){
        // nullify access tokens
        WposAdminSettings::putValue('general', 'gcontacttoken', '');
        // turn off intergration
        WposAdminSettings::putValue('general', 'gcontact', 0);
    }

    /**
     * Set new token set in the config
     * @param $token
     */
    private static function setNewAccessToken($token){
        // set new access token in the config
        WposAdminSettings::putValue('general', 'gcontacttoken', $token);
    }
    /**
     * Adds or updates the google contact entry for the specified account
     * @param $settings
     * @param $data
     * @param string $googleId
     * @return bool|int|mixed
     */
    public static function setGoogleContact($settings, $data, $googleId=''){
        require_once $_SERVER['DOCUMENT_ROOT'].'/library/Google/Client.php';
        require_once $_SERVER['DOCUMENT_ROOT'].'/library/Google/Http/Request.php';
        // init google client & set tokens/ids
        $client = new Google_Client();
        $client->setClientId(self::$client_id);
        $client->setClientSecret(self::$client_secret);
        $client->setAccessToken(json_encode($settings->gcontacttoken));
        // Check if access token needs renewal
        if ($client->isAccessTokenExpired()){
            // Renew access token and save to config
            $client->refreshToken($settings->gcontacttoken->refresh_token);
            $curtokenset = $settings->gcontacttoken;
            $curtokenset->access_token = json_decode($client->getAccessToken())->access_token;
            self::setNewAccessToken($curtokenset);
        }

        try {
            // create new entry
            $doc  = new DOMDocument();
            $doc->formatOutput = true;
            $entry = $doc->createElement('atom:entry');
            $entry->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:atom', 'http://www.w3.org/2005/Atom');
            $entry->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:gd', 'http://schemas.google.com/g/2005');
            $doc->appendChild($entry);
            // add name element
            $name = $doc->createElement('gd:name');
            $entry->appendChild($name);
            $fullName = $doc->createElement('gd:fullName', htmlspecialchars($data->name));
            $name->appendChild($fullName);
            // add email element
            $ema = $doc->createElement('gd:email');
            $ema->setAttribute('address' , $data->email);
            $ema->setAttribute('rel' ,'http://schemas.google.com/g/2005#home');
            $entry->appendChild($ema);
            // add phone elements
            if ($data->phone!=""){
                $pho = $doc->createElement('gd:phoneNumber', $data->phone);
                $pho->setAttribute('label' ,'Phone');
                $entry->appendChild($pho);
            }
            if ($data->mobile!=""){
                $mob = $doc->createElement('gd:phoneNumber', $data->mobile);
                $mob->setAttribute('label', 'Mobile');
                $entry->appendChild($mob);
            }
            // add address
            $homeAddress=$doc->createElement('gd:structuredPostalAddress');
            $homeAddress->setAttribute('rel', 'http://schemas.google.com/g/2005#home');
            $entry->appendChild($homeAddress);
            // city/suburb
            $homeCity = $doc->createElement('gd:city', $data->suburb);
            $homeAddress->appendChild($homeCity);
            // address
            $homeStreet = $doc->createElement('gd:street', $data->address);
            $homeAddress->appendChild($homeStreet);
            // state
            $homeProvince = $doc->createElement('gd:region', $data->state);
            $homeAddress->appendChild($homeProvince);
            // postcode
            $homeZipCode = $doc->createElement('gd:postcode', $data->postcode);
            $homeAddress->appendChild($homeZipCode);
            // country
            $homeCountry = $doc->createElement('gd:country', $data->country);
            $homeAddress->appendChild($homeCountry);

            // insert entry
            if ($googleId!=''){
                $id = $doc->createElement('id', $googleId);
                $entry->appendChild($id);
                $url = str_replace('http', 'https', $googleId);
                $meth = 'PUT';
            } else {
                $url = 'https://www.google.com/m8/feeds/contacts/default/full/';
                $meth = 'POST';
            }
            $req = new Google_Http_Request($url, $meth, ['GData-Version' => 3.0, 'Content-type' => 'application/atom+xml; charset=UTF-8; type=entry', 'If-Match'=>'*'], $doc->saveXML());
            $client->getAuth()->authenticatedRequest($req);
            // The contacts api only returns XML responses
            //print_r($req->getResponseBody());
            $xml = simplexml_load_string($req->getResponseBody());
            $xml->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');
            //print_r($xml);
            if (isset($xml->error)){
                return [false, $xml->error->internalReason];
            } else {
                return [true, $xml->id];
            }
        } catch (Exception $e) {
            return false;
        }
    }
}