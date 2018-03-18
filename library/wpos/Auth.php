<?php
/**
 * Auth is part of Wallace Point of Sale system (WPOS) API
 *
 * Auth is used to authenticate users with the server,
 * store/provide associated session values & update/check permissions
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
 * @author     Michael B Wallace <micwallace@gmx.com>, Adam Jacquier-Parr <aljparr0@gmail.com>
 * @since      Class created 11/24/13 12:01 PM
 */
class Auth{

    /**
     * @var array API calls that are restricted to admin users
     */
    private $resApiCalls = ['file/upload', 'logs/read', 'logs/list', 'db/backup', 'node/start', 'node/stop',
                            'node/restart', 'node/status', 'settings/pos/set', 'settings/general/set', 'settings/pos/get', 'settings/general/get', 'settings/invoice/get', 'settings/invoice/get',
                            'users/get', 'users/add', 'users/edit', 'users/delete', 'user/disable', 'devices/add', 'devices/edit', 'devices/delete', 'device/disable',
                            'location/add', 'location/edit', 'location/delete', 'location/disable', 'devices/setup'];

    /**
     * @var AuthModel
     */
    private $authMdl = null;

    /**
     * Auth tokens
     * @var
     */
    private $authTokens = null;

    /**
     * Start session if not already started
     */
    public function __construct(){
        if (session_id() == '') {
            session_start();
        }
    }

    private function initAuthModel(){
        if ($this->authMdl==null){
            $this->authMdl = new AuthModel();
        }
    }

    /**
     * @return null current user UUID
     */
    public function getUUID(){
        if (isset($_SESSION['uuid'])) {
            return $_SESSION['uuid'];
        }

        return null;
    }

    /**
     * @return array|null array of user data or null on failure
     */
    public function getUser(){
        if (isset($_SESSION['userId'])) {
            $user = ["id"=>$_SESSION['userId'], "username"=>$_SESSION['username'], "isadmin"=>$_SESSION['isadmin'], "sections"=>$_SESSION['permissions']['sections']];
            // add auth tokens if set
            if ($this->authTokens!==null){
                $user = array_merge($user, $this->authTokens);
            }
            return $user;
        }
        return null;
    }

    /**
     * @return null user id or null on failure
     */
    public function getUserId(){
        if (isset($_SESSION['userId'])) {
            return $_SESSION['userId'];
        }

        return null;
    }

    /**
     * @return null username or null on failure
     */
    public function getUsername(){
        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(){
        if (isset($_SESSION['username'])) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isAdmin(){
        if (isset($_SESSION['isadmin'])) {
            if ($_SESSION['isadmin']==1)
                return true;
        }
        return false;
    }

    /**
     * @param bool $sectiononly
     * @return null
     */
    public function getPermissions($sectiononly=true){
        if (isset($_SESSION['permissions'])) {
            if ($sectiononly){
                return $_SESSION['permissions']['sections'];
            } else {
                return $_SESSION['permissions'];
            }
        }
        return null;
    }

    /**
     * Checks whether the current user has permission to execute the requested API operation
     * @param $apiAction
     * @return bool
     */
    public function isUserAllowed($apiAction){
        // if admin just return true
        if ($this->isAdmin()){
            return true;
        }
        // check if it's an admin only api call
        if (array_search($apiAction, $this->resApiCalls)!==false){
            // disallow user
            return false;
        }
        // check in users permissions
        if (array_search($apiAction, $_SESSION['permissions']['apicalls'])!==false){
            // allow user if action defined in permisions
            return true;
        }
        return false;
    }

    /**
     * Attempt a login; on success setup session vars and send to node server else log the failed attempt
     * @param $username
     * @param $password
     * @param bool $getToken When true,sets up an extended session token and auth_hash, which is returned to the client
     * @return bool|int
     */
    public function login($username, $password, $getToken=false){
        $this->initAuthModel();
        $user = $this->authMdl->login($username, $password, true);
        if ($user==-1){
            // log data
            Logger::write("An authentication attempt was made by ".$username." but the user has been disabled.", "AUTH", null, false);
            return -1; // the user is disabled
        }
        if (is_array($user)) {
            // check for
            $_SESSION['username'] = $username;
            $_SESSION['userId']   = $user['id'];
            $_SESSION['isadmin']  = $user['admin'];
            $_SESSION['permissions']  = json_decode($user['permissions'], true);

            if ($getToken!==false)
                $this->setNewSessionToken($user['id'], $user['hash']);

            // log data
            Logger::write("Authentication successful for user:".$username, "AUTH", null, false);

            // Send to node JS
            $this->authoriseWebsocket();

            return true;
        } else{
            // log data
            Logger::write("Authentication failed for user:".$username." from IP address: ".WposAdminUtilities::getRemoteAddress(), "AUTH", null, false);

            return false;
        }
    }

    /**
     * Sends the users session_id to the node.js websocket server for client websocket authorisation.
     */
    public function authoriseWebsocket(){
        $socket = new WposSocketIO();
        return $socket->sendSessionData(session_id());
    }

    /**
     *
     * @param $username
     * @param $auth_hash
     * @return bool|int
     */
    public function renewTokenSession($username, $auth_hash){
        $this->initAuthModel();
        $user=$this->authMdl->get(null, $username, null, null, true)[0];
        if (is_array($user)) {
            // check disabled
            if ($user['disabled']==1){
                // log data
                Logger::write("Session renew failed for ".$username.", the user has been disabled.", "AUTH");
                return -1; // the user is disabled
            }
            // check tokens
            $validation_hash = hash('sha256', $user['hash'].$user['token']);
            if ($auth_hash==$validation_hash){
                // set session values
                $_SESSION['username'] = $username;
                $_SESSION['userId']   = $user['id'];
                $_SESSION['isadmin']  = $user['admin'];
                $_SESSION['permissions']  = json_decode($user['permissions'], true);
                //$this->hash = $user['hash'];
                $this->setNewSessionToken($user['id'], $user['hash']);
                // log data
                Logger::write("Authentication successful for user:".$username, "AUTH");

                // Send to node JS
                $socket = new WposSocketIO();
                $socket->sendSessionData(session_id());
                /*if (!$socket->sendSessionData(session_id())){
                    return -2;
                }*/
                return true;
            } else {
                // log data
                Logger::write("Session renew failed for ".$username.", token mismatch.", "AUTH");
            }
        } else{
            // log data
            Logger::write("Session renew failed for ".$username.", user not found.", "AUTH");
        }
        return false;
    }

    /**
     * Generate a new token and auth_hash, save the token in the database
     * @param $id
     * @param $password_hash
     */
    private function setNewSessionToken($id, $password_hash){
        // create unique token
        $tokens = ['token'=>WposAdminUtilities::getToken()];
        // create auth_hash
        $tokens['auth_hash'] = hash('sha256', $password_hash.$tokens['token']);
        // save tokens
        $this->authMdl->setAuthToken($id, $tokens['token']);
        $this->authTokens = $tokens;
    }
    // Customer Authentication methods
    /**
     * @return array|null array of user data or null on failure
     */
    public function getCustomer(){
        if (isset($_SESSION['cust_id'])) {
            $customer = ["id"=>$_SESSION['cust_id'], "username"=>$_SESSION['cust_username'], "name"=>$_SESSION['cust_name']];
            return $customer;
        }
        return null;
    }

    /**
     * @return null customer id or null on failure
     */
    public function getCustomerId(){
        if (isset($_SESSION['cust_id'])) {
            return $_SESSION['cust_id'];
        }
        return null;
    }

    /**
     * @return null customer id or null on failure
     */
    public function getCustomerUsername(){
        if (isset($_SESSION['cust_username'])) {
            return $_SESSION['cust_username'];
        }
        return null;
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn(){
        if (isset($_SESSION['cust_username'])) {
            return true;
        }
        return false;
    }

    /**
     * @return String, customer/user/both or null on failure
     */
    public function getUserType(){
        if (isset($_SESSION['userId']) && isset($_SESSION['cust_id'])) {
            return 'both';
        } else if (isset($_SESSION['userId'])) {
            return 'user';
        } else if (isset($_SESSION['cust_id'])){
            return 'customer';
        }
        return null;
    }
    /**
     * Attempt a login; on success setup session vars and send to node server else log the failed attempt
     * @param $username
     * @param $password
     * @return bool|int
     */
    public function customerLogin($username, $password){
        $custMdl = new CustomerModel();
        $customer=$custMdl->login($username, $password, true);
        if ($customer==-1){
            // log data
            Logger::write("An authentication attempt was made by ".$username." but the customer has been disabled.", "AUTH", null, false);
            return -1; // the user is disabled
        }
        if ($customer==-2){
            return -2; // the user is not activated
        }
        if (is_array($customer)) {
            // check for
            $_SESSION['cust_username'] = $username;
            $_SESSION['cust_name'] = $customer['name'];
            $_SESSION['cust_id']   = $customer['id'];
            $_SESSION['cust_hash'] = $customer['pass'];;
            // log data
            Logger::write("Authentication successful for customer:".$username, "AUTH", null, false);

            return true;
        } else{
            // log data
            Logger::write("Authentication failed for customer:".$username." from IP address: ".WposAdminUtilities::getRemoteAddress(), "AUTH", null, false);

            return false;
        }
    }

    /**
     * Destroy the current session and notify the node server
     * @return bool
     */
    public function logout(){
        // Send to node JS
        $socket = new WposSocketIO();
        $socket->sendSessionData(session_id(), true);
        return session_destroy();
    }

}