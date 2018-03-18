<?php
/**
* XeroIntergration is part of Wallace Point of Sale system (WPOS) API
*
* XeroIntergration is used to provide wrapper functions for interacting with the Xero API
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
* @link       http://www.example.com/package/PackageName
* @author     Michael B Wallace <micwallace@gmx.com>
* @since      File available since 12/04/14 3:44 PM
*/
class XeroIntegration {
    private static $cosumer_key = "FTPU0FOJ8F2SP3BLDOYDPXWIBS0YCY";
    private static $cosumer_secret = "DOCSO7NPEGFPFZJXBWZARPEZR2WTBC";
    private static $auth_scope = "";

    /**
     * Return a configured xeroApi object
     * @return XeroOAuth
     */
    public static function getXeroApi($addtoken=true){
        global $useragent;
        $useragent = "Xero-OAuth-PHP Public";
        define ( "XRO_APP_TYPE", "Public" );
        //define ( "OAUTH_CALLBACK", 'http://localhost/XeroOAuth-PHP/public.php' );
        define ( "OAUTH_CALLBACK", 'https://'.$_SERVER['SERVER_NAME'].'/api/settings/xero/oauthcallback' );
        require_once $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/Xero/lib/XeroOAuth.php';

        $signatures = [
            'consumer_key' => self::$cosumer_key,
            'shared_secret' => self::$cosumer_secret,
            // API versions
            'core_version' => '2.0',
            'payroll_version' => '1.0',
            'file_version' => '1.0'
        ];
        if (XRO_APP_TYPE == "Private" || XRO_APP_TYPE == "Partner") {
            $signatures ['rsa_private_key'] = BASE_PATH . '/certs/privatekey.pem';
            $signatures ['rsa_public_key'] = BASE_PATH . '/certs/publickey.cer';
        }
        if (XRO_APP_TYPE == "Partner") {
            $signatures ['curl_ssl_cert'] = BASE_PATH . '/certs/entrust-cert-RQ3.pem';
            $signatures ['curl_ssl_password'] = '1234';
            $signatures ['curl_ssl_key'] = BASE_PATH . '/certs/entrust-private-RQ3.pem';
        }

        $xeroApi = new XeroOAuth(array_merge ( [
            'application_type' => XRO_APP_TYPE,
            'oauth_callback' => OAUTH_CALLBACK,
            'user_agent' => $useragent
        ], $signatures ));

        // setup session tokens if they exist
        if ($addtoken){
            $xeroCfg = WposAdminSettings::getSettingsObject('accounting');
            $xeroApi->config ['access_token'] = isset($xeroCfg->xerotoken->oauth_token)?$xeroCfg->xerotoken->oauth_token:'';
            $xeroApi->config ['access_token_secret'] = isset($xeroCfg->xerotoken->oauth_token_secret)?$xeroCfg->xerotoken->oauth_token_secret:'';
        }
        // TODO: Refresh token when expired, will implement when we have partner app status

        return $xeroApi;
    }

    public static function initXeroAuth(){
        $XeroOAuth = self::getXeroApi(false);

        // Request token from xero
        $XeroOAuth->request ( 'GET', $XeroOAuth->url ( 'RequestToken', '' ), ['oauth_callback' => OAUTH_CALLBACK ]);
        $response = $XeroOAuth->extract_params ( $XeroOAuth->response ['response'] );
        if ($XeroOAuth->response ['code'] == 200) {
            self::setNewToken($response);
            // generate auth url and redirect user
            $authUrl = $XeroOAuth->url( "Authorize", '' ) . "?oauth_token={$response['oauth_token']}&scope=" . self::$auth_scope;
            header("Location: ".$authUrl);
        } else {
            echo("Xero Api Error: ".$XeroOAuth->response['response']);
        }
        exit;
    }

    public static function processCallbackAuthCode(){
        if (!isset($_REQUEST['oauth_verifier'])|| $_REQUEST['oauth_verifier']==""){
            die("Invalid OAuth verifier provided");
        }
        if (($xresult=self::processXeroAuthCode($_REQUEST['oauth_verifier']))===true){
            die("<html><head></head><body onload='self.close();'></body></html>");
        } else {
            die("Could not authorise with zero: ".$xresult);
        }
    }

    /**
     * Get a xero access token using the provided url
     * @param $verifier
     * @internal param $code
     * @return string
     */
    public static function processXeroAuthCode($verifier){
        $XeroOAuth = self::getXeroApi();
        // exchange auth code for access & save tokens
        $XeroOAuth->request ('GET', $XeroOAuth->url( 'AccessToken', '' ),[
            'oauth_verifier' => $verifier,
            'oauth_token' => $XeroOAuth->config['access_token']
        ]);
        if ($XeroOAuth->response ['code'] == 200) {
            $response = $XeroOAuth->extract_params($XeroOAuth->response ['response']);
            self::setNewToken($response);
            return true;
        }
        // return error
        return "Xero Api Error: ".$XeroOAuth->response['response'];
    }

    public static function removeXeroAuth(){
        // nullify access tokens
        WposAdminSettings::putValue('accounting', 'xerotoken', '');
        // turn off intergration
        WposAdminSettings::putValue('accounting', 'xeroenabled', 0);
    }

    /**
     * Set new token set in the config
     * @param $token
     */
    private static function setNewToken($token){
        // add expiry
        $token['expiredt'] = time() + 1795; // -5 seconds for better user experience //TODO: change when partner xero app
        // set new access token in the config
        WposAdminSettings::putValue('accounting', 'xerotoken', $token);
        WposAdminSettings::putValue('accounting', 'xeroenabled', 1);
    }

    public static function getXeroAccounts($result){
        $XeroOAuth = self::getXeroApi();
        if (!$XeroOAuth->config['access_token']){
            $result['error'] = "Xero account has not been authorised, connect account first";
            return $result;
        }
        $response = $XeroOAuth->request('GET', 'https://api.xero.com/api.xro/2.0/Accounts', [], '', 'json');
        if ($response ['code'] == 200) {
            $result['data'] = json_decode($response['response']);
        } else {
            // TODO: Remove when we become xero partner
            if (strpos($response['response'], 'oauth_problem=token_expired')!==-1){
                $result['error'] = "Your xero connection has expired, please reconnect your xero account.";
            } else {
                $result['error'] = "Xero Api Error: ".$response['response'];
            }
        }
        return $result;
    }

    public static function getXeroTaxes($result){
        $XeroOAuth = self::getXeroApi();
        if (!$XeroOAuth->config['access_token']){
            $result['error'] = "Xero account has not been authorised, connect account first";
            return $result;
        }
        $response = $XeroOAuth->request('GET', 'https://api.xero.com/api.xro/2.0/TaxRates', [], '', 'json');
        if ($response ['code'] == 200) {
            $result['data'] = json_decode($response['response']);
        } else {
            // TODO: Remove when we become xero partner
            if (strpos($response['response'], 'oauth_problem=token_expired')!==-1){
                $result['error'] = "Your xero connection has expired, please reconnect your xero account.";
            } else {
                $result['error'] = "Xero Api Error: ".$response['response'];
            }
        }
        return $result;
    }

    public static function getXeroConfigValues($result){
        $accounts = XeroIntegration::getXeroAccounts($result);
        if ($accounts['error']!="OK"){
            return $accounts;
        }
        $taxes = XeroIntegration::getXeroTaxes($result);
        if ($taxes['error']!="OK"){
            return $taxes;
        }
        $result['data']['Accounts'] = $accounts['data']->Accounts;
        $result['data']['TaxRates'] = $taxes['data']->TaxRates;

        return $result;
    }

    private function postXML($method='PUT', $url, $xml){
        // setup Xero API
        $XeroOAuth = self::getXeroApi();

        // send data and handle response
        $response = $XeroOAuth->request($method, $url, [], $xml->asXML(), 'json');
        if ($response ['code'] == 200) {
            $result =  json_decode($response['response']);
        } else {
            // TODO: Remove when we become xero partner
            if (strpos($response['response'], 'oauth_problem=token_expired')!==false){
                $result = "Your xero connection has expired, please reconnect your xero account.";
            } else {
                $result = "Xero Api Error: ".$response['response'];
            }
        }
        return $result;
    }

    private static function getXeroXml($stime, $etime){
        $Wstat = new WposAdminStats();
        $Wstat->setRange($stime, $etime);
        $Wstat->setType('sale');
        $taxStats = $Wstat->getTaxStats([]);
        if (!$taxStats['data']){
            return "Could not generate export item data: ".$taxStats['error'];
        }
        $payStats = $Wstat->getCountTakingsStats([]);
        if (!$payStats['data']){
            return "Could not generate export payment data ".$taxStats['error'];
        }
        // get account map
        $accnmap = WposAdminSettings::getSettingsObject("accounting")->xeroaccnmap;
        if ($accnmap==''){
            return "Xero integration setup not completed, please save account mappings first.";
        }
        // Setup invoice xml
        $invoice = new SimpleXMLElement("<Invoice/>");
        $date = date("Y-m-d", round($etime/1000));
        $invoice->addChild("Type", "ACCREC");
        $invoice->addChild("Date", $date);
        $invoice->addChild("DueDate", $date);
        $invoice->addChild("InvoiceNumber", "POS-".str_replace('-', '', $date));
        $invoice->addChild("Reference", "POS Sales");
        $invoice->addChild("LineAmountTypes", "Inclusive");
        $invoice->addChild("Status", "AUTHORISED");
        $contact = $invoice->addChild("Contact");
        $contact->addChild("Name", "POS Sales");
        // Setup refunds xml
        $cnote = new SimpleXMLElement("<CreditNote/>");
        $cnote->addChild("Type", "ACCRECCREDIT");
        $cnote->addChild("Date", $date);
        $cnote->addChild("CreditNoteNumber", "POSR-".str_replace('-', '', $date));
        $cnote->addChild("Reference", "POS Refunds");
        $cnote->addChild("LineAmountTypes", "Inclusive");
        $cnote->addChild("Status", "AUTHORISED");
        $ccontact = $cnote->addChild("Contact");
        $ccontact->addChild("Name", "POS Sales");
        // Generate line items for each payment method and add types
        $lineItems = $invoice->addChild("LineItems");
        $clineItems = $cnote->addChild("LineItems");
        foreach ($taxStats['data'] as $key=>$data){
            if ($key!=0){
                $taxType = (isset($accnmap->{"tax-".$key})?$accnmap->{"tax-".$key}:'');
                // Add sales
                $accountCode = (isset($accnmap->sales)?$accnmap->sales:'');
                if ($data->saletotal>0){
                    $lineItem = $lineItems->addChild("LineItem");
                    $lineItem->addChild("Quantity", 1);
                    $lineItem->addChild("Description", $data->name." Sales");
                    $lineItem->addChild("UnitAmount", str_replace(',', '', ($data->saletotal+$data->saletax)));
                    $lineItem->addChild("AccountCode", $accountCode);
                    $lineItem->addChild("TaxType", $taxType);
                }
                // Add refunds
                if ($data->refundtotal>0){
                    //$accountCode = (isset($accnmap->refunds)?$accnmap->refunds:'');
                    $clineItem = $clineItems->addChild("LineItem");
                    $clineItem->addChild("Quantity", 1);
                    $clineItem->addChild("Description", $data->name." Refunds");
                    $clineItem->addChild("UnitAmount", str_replace(',', '', $data->refundtotal+$data->refundtax));
                    $clineItem->addChild("AccountCode", $accountCode);
                    $clineItem->addChild("TaxType", $taxType);
                }
            } else if ($data->total!=0) {
                // add cash rounding
                $taxType = (isset($accnmap->{"tax-".$key})?$accnmap->{"tax-".$key}:'');
                $accountCode = (isset($accnmap->sales)?$accnmap->sales:'');
                $clineItem = $lineItems->addChild("LineItem");
                $clineItem->addChild("Quantity", 1);
                $clineItem->addChild("Description", "Cash Rounding");
                $clineItem->addChild("UnitAmount", str_replace(',', '', $data->total));
                $clineItem->addChild("AccountCode", $accountCode);
                $clineItem->addChild("TaxType", $taxType);
            }
        }
        // Setup payments xml
        $payments = new SimpleXMLElement("<Payments/>");
        foreach ($payStats['data'] as $key=>$data){
            if ($key!='Unaccounted'){
                if ($data->saletotal>0){
                    // Add Payment
                    $payment = $payments->addChild("Payment");
                    $payment->addChild("Date", $date);
                    $payment->addChild("Reference", ucfirst($key)." POS Payments");
                    $payment->addChild("Amount", str_replace(',', '', $data->saletotal));

                    $pinv = $payment->addChild("Invoice");
                    $pinv->addChild("InvoiceNumber","POS-".str_replace('-', '', $date));

                    if ($key=="eftpos" || $key=="credit"){
                        $key = "card";
                    }
                    $accountCode = (isset($accnmap->{"pay-".$key})?$accnmap->{"pay-".$key}:'');
                    $paccn = $payment->addChild("Account");

                    $paccn->addChild("Code", $accountCode);
                }

                if ($data->refundtotal>0){
                    // Add Payment
                    $payment = $payments->addChild("Payment");
                    $payment->addChild("Date", $date);
                    $payment->addChild("Reference", ucfirst($key)." POS Refunds");
                    $payment->addChild("Amount", str_replace(',', '', $data->refundtotal));

                    $pinv = $payment->addChild("CreditNote");
                    $pinv->addChild("CreditNoteNumber","POSR-".str_replace('-', '', $date));

                    if ($key=="eftpos" || $key=="credit" || $key=="tyro"){
                        $key = "card";
                    }
                    $accountCode = (isset($accnmap->{"pay-".$key})?$accnmap->{"pay-".$key}:'');
                    $paccn = $payment->addChild("Account");

                    $paccn->addChild("Code", $accountCode);
                }
            }
        }

        return ['invoice'=>$invoice, 'creditnote'=>($clineItems->count()>0?$cnote:false), 'payments'=>$payments];
    }

    public static function exportXeroSales($stime, $etime, $result=["error"=>"OK"]){
        $data = self::getXeroXml($stime, $etime);
        if (is_string($data)){
            $result['error'] = $data;
            return $result;
        }

        //print_r($data['invoice']->asXML());
        //print_r($data['payments']->asXML());
        //die();

        $apires = self::postXML('POST', 'https://api.xero.com/api.xro/2.0/Invoices', $data['invoice']);
        if (is_string($apires)){
            $result['error'] = $apires;
            return $result;
        }

        if ($data['creditnote']!==false){
            $apires = self::postXML('POST', 'https://api.xero.com/api.xro/2.0/CreditNotes', $data['creditnote']);
            if (is_string($apires)){
                $result['error'] = $apires;
                return $result;
            }
        }
        $apires = self::postXML('PUT', 'https://api.xero.com/api.xro/2.0/Payments', $data['payments']);
        if (is_string($apires)){
            $result['error'] = $apires;
        }

        return $result;
    }
}