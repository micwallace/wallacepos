<?php

/**
 * WposCustomersAccess is part of Wallace Point of Sale system (WPOS) API
 *
 * WposCustomersAccess is used to modify administrative items including stored items, suppliers, customers and users.
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
 * @since      File available since 24/12/13 2:05 PM
 */
class WposCustomerAccess {
    private $data;

    /**
     * Set any provided data
     * @param $data
     */
    function __construct($data=null)
    {
        // parse the data and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
    }

    public function register($result){
        // validate input + additional validation
        $jsonval = new JsonValidate($this->data, '{"name":"", "email":"@", "address":"", "suburb":"", "postcode":"", "state":"", "country":"", "pass":"", "captcha":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        if (!$this->data->phone && !$this->data->mobile){
            $result['error'] = "At least one contact phone number must be specified.";
            return $result;
        }
        // validate captcha
        require $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'assets/secureimage/securimage.php';
        $img = new Securimage;
        // if the code checked is correct, it is destroyed to prevent re-use
        if ($img->check($this->data->captcha) == false) {
            $result['error'] = "Incorrect security code entered";
            return $result;
        }
        // create customer, check for error ( this does email check)
        $wposCust = new WposAdminCustomers();
        $res = $wposCust->addCustomerData($this->data);
        if (!is_numeric($res)){
            $result['error'] = $res;
            return $result;
        }
        // set activation url with random hash as a token
        $token = WposAdminUtilities::getToken();
        $link= "https://".$_SERVER['SERVER_NAME']."/myaccount/activate.php?token=".$token;
        // set token
        $custMdl = new CustomerModel();
        if ($custMdl->setAuthToken($res, $token)===false){
            $result['error'] = "Could not set auth token: ".$custMdl->errorInfo;
        }
        // send reset email
        $linkhtml = '<a href="'.$link.'">'.$link.'</a>';
        $mailer = new WposMail();
        if (($mres = $mailer->sendPredefinedMessage($this->data->email, 'register_email', ['name'=>$this->data->name, 'link'=>$linkhtml]))!==true){
            $result['error'] = $mres;
        }
        $mailer->sendPredefinedMessage("micwallace@gmx.com", 'register_notify', ['name'=>"Michael", 'custname'=>$this->data->name]);
        return $result;
    }

    /**
     * Activate the customers account using the given token
     * @param $token
     * @return bool|string
     */
    public function activateAccount($token){
        if ($token==''){
            return 'No valid auth token received.';
        }
        $custMdl = new CustomerModel();
        $result = $custMdl->tokenActivate($token);
        if ($result==0 || $result==false){
            return 'Failed to activate the account using the given token.<br/>The token may have expired.';
        }
        return true;
    }

    /**
     * Send password reset to the given user
     * @param $result
     * @return mixed
     */
    public function sendResetPasswordEmail($result){
        // validate input + additional validation
        $jsonval = new JsonValidate($this->data, '{"email":"@","captcha":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        // validate captcha
        require $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'assets/secureimage/securimage.php';
        $img = new Securimage;
        // if the code checked is correct, it is destroyed to prevent re-use
        if ($img->check($this->data->captcha) == false) {
            $result['error'] = "Incorrect security code entered";
            return $result;
        }
        // check for account
        $custMdl = new CustomerModel();
        $customers = $custMdl->get(null, $this->data->email);
        if (empty($customers)){
            $result['error'] = "There is no account with the specified email address.";
            return $result;
        }
        // run normal email password reset routine from admin functions
        $data = new stdClass();
        $data->id = $customers[0]['id'];
        $wAdminCust = new WposAdminCustomers($data);
        $result = $wAdminCust->sendResetEmail($result);
        return $result;
    }

    /**
     * Perform user initiated password reset using the given token
     * @param $result
     * @return mixed
     */
    public function doPasswordReset($result){
        // validate input + additional validation
        $jsonval = new JsonValidate($this->data, '{"pass":"", "token":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $custMdl = new CustomerModel();
        $tokres = $custMdl->tokenReset($this->data->token, $this->data->pass);
        if ($tokres===0){
            $result['error'] =  "Failed to reset password using the given token\nThe token may have expired.";
        } else if ($tokres===false){
            $result['error'] =  "Failed to update your password: ".$custMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Get general config used by customer dashboard
     * @param $result
     * @return mixed
     */
    public function getSettings($result){
        $settings = WposAdminSettings::getSettingsObject('general');
        unset($settings->gcontacttoken);
        $taxMdl = new TaxItemsModel();
        $taxes = $taxMdl->get();
        $taxobj = [];
        foreach ($taxes as $tax){
            $taxobj[$tax['id']] = $tax;
        }
        $setobj = ["general"=>$settings, "tax"=>$taxobj];
        $result['data'] = $setobj;

        return $result;
    }

    /**
     * Get the current customers details
     * @param $result
     * @return mixed
     */
    public function getCurrentCustomerDetails($result){
        // Safety check
        if (!isset($_SESSION['cust_id'])){
            $result['error'] = "Customer ID not found in current session";
            return $result;
        }
        $result['data'] = WposAdminCustomers::getCustomerData($_SESSION['cust_id']);
        return $result;
    }

    /**
     * Update the current customers details
     * @param $result
     * @return mixed
     */
    public function saveCustomerDetails($result){
        // Safety check
        if (!isset($_SESSION['cust_id'])){
            $result['error'] = "Customer ID not found in current session";
            return $result;
        }
        // input validation
        $jsonval = new JsonValidate($this->data, '{"name":"", "email":"@", "address":"", "suburb":"", "postcode":"", "state":"", "country":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        if (!$this->data->phone && !$this->data->mobile){
            $result['error'] = "At least one contact phone number must be specified.";
            return $result;
        }
        // set id
        $this->data->id = $_SESSION['cust_id'];

        $dres = WposAdminCustomers::updateCustomerData($this->data);
        if ($dres===false){
            $result['error']="Failed to update customer details.";
        }

        return $result;
    }

    /**
     * Get all transactions for the current customer
     * @param $result
     * @return mixed
     */
    public function getCustomerTransactions($result){
        // Safety check
        if (!isset($_SESSION['cust_id'])){
            $result['error'] = "Customer ID not found in current session";
            return $result;
        }
        // Get customer transactions
        $transMdl = new TransactionsModel();
        $trans = $transMdl->getByCustomer($_SESSION['cust_id']);
        if ($trans===false){
            $result['error'] = "Could not fetch your transactions: ".$transMdl->errorInfo;
        } else {
            $result['data'] = [];
            // decode JSON and add extras
            foreach ($trans as $tran){
                $record = json_decode($tran['data']);
                $record->type = $tran['type'];
                $result['data'][$tran['ref']] = $record;
            }
        }
        return $result;
    }

    /**
     * Generate invoice for the customers specified transaction
     * @param $id
     */
    public function generateCustomerInvoice($id){
        // Safety check
        if (!isset($_SESSION['cust_id'])){
            die("Customer ID not found in current session");
        }
        $Wtrans = new WposTransactions(null, $id, true);
        // check for customerId match
        if ($Wtrans->getCurrentTransaction()->custid!==$_SESSION['cust_id']){
            die("You are not authorised to view this transaction");
        }

        $Wtrans->generateInvoice(); // exits
    }
}