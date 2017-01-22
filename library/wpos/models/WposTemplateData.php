<?php
/**
 * WposTemplateData is part of Wallace Point of Sale system (WPOS) API
 *
 * WposTemplateData formats & provides data to render mustache receipt and invoice templates
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
 * @since      File available since 10/12/15 14:38 PM
 */
class WposTemplateData
{

    public $ref;
    public $sale_ref;
    public $sale_dt;
    public $sale_items;
    public $sale_numitems;
    public $sale_tax;
    public $sale_discount;
    public $sale_discountamt;
    public $sale_subtotal;
    public $sale_total;
    public $sale_payments;
    public $sale_void;
    public $sale_hasrefunds;
    public $sale_refunds;
    public $show_subtotal;
    public $header_line1;
    public $header_line2;
    public $header_line3;
    public $logo_url;
    public $footer;
    public $thermalprint = false;
    public $showqrcode;
    public $eftpos_receipts = '';

    public $business_name;
    public $business_address;
    public $business_suburb;
    public $business_state;
    public $business_postcode;
    public $business_country;
    public $business_number;
    public $invoice_duedt;
    public $customer = false;

    public $Utils;

    /**
     * Format currency for mustache template
     * @return function
     */
    public function currency(){
        return function($text, Mustache_LambdaHelper $helper){
            return $this->Utils->currencyFormat($helper->render($text));
        };
    }

    private $taxitems;
    private function getTaxArray($taxdata){
        $taxArr = [];
        if (!isset($this->taxitems))
            $this->taxitems = WposAdminUtilities::getTaxTable()['items'];

        foreach ($taxdata as $key=>$value) {
            $tax = $this->taxitems[$key];
            $taxObj = new stdClass();
            $taxObj->label = $tax['name'] . ' (' . $tax['value'] . '%)';
            $taxObj->altlabel = $tax['altname'] . ' (' . $tax['value'] . '%)';
            $taxObj->value = $value;
            $taxArr[] = $taxObj;
        }
        return $taxArr;
    }

    /**
     * Decode provided JSON and extract commonly used variables
     * @param $data
     * @param array $config
     * @param bool $invoice
     */
    public function WposTemplateData($data, $config=[], $invoice = false){
        $this->Utils = new WposAdminUtilities();
        $this->Utils->setCurrencyFormat($config['general']->currencyformat);

        $this->sale_id = $data->id;
        $this->sale_ref = $data->ref;
        $this->sale_dt = $this->Utils->getDateFromTimestamp($data->processdt, $config['general']->dateformat);
        $this->sale_items = $data->items;
        $this->sale_numitems = $data->numitems;
        $this->sale_discount = floatval($data->discount);
        $this->sale_discountamt = $this->Utils->currencyFormat(abs(floatval($data->total) - (floatval($data->subtotal) + floatval($data->tax))));
        $this->sale_subtotal = $data->subtotal;
        $this->sale_total = $data->total;
        $this->sale_void = isset($data->voiddata);
        $this->sale_hasrefunds = isset($data->refunddata);
        $this->show_subtotal  = (count($data->taxdata) > 0 || $data->discount > 0);

        // format tax data
        $this->sale_tax = $this->getTaxArray($data->taxdata);

        // format payments and collect eftpos receipts
        $altlabels = $config['general']->altlabels;
        $this->sale_payments = [];
        foreach ($data->payments as $payment) {
            $method = $payment->method;
            $amount = $payment->amount;
            // check for special payment values
            if (isset($payment->paydata)) {
                // check for integrated eftpos receipts
                if (isset($payment->paydata->customerReceipt)) {
                    $this->eftpos_receipts .= $payment->paydata->customerReceipt;
                }
                // catch cash-outs
                if (isset($payment->paydata->cashOut)) {
                    $method = "cashout";
                    $amount = (-$amount);
                }
            }
            $obj = new stdClass();
            $obj->altlabel = isset($altlabels->{$method})?$altlabels->{$method}:ucfirst($method);
            $obj->label = ucfirst($method);
            $obj->amount = $amount;

            $this->sale_payments[] = $obj;
            if ($method == 'cash') {
                // If cash print tender & change.
                $obj = new stdClass();
                $obj->label = "Tendered";
                $obj->altlabel = $altlabels->tendered;
                $obj->amount = $payment->tender;
                $this->sale_payments[] = $obj;
                $obj = new stdClass();
                $obj->label = "Change";
                $obj->altlabel = $altlabels->change;
                $obj->amount = $payment->change;
                $this->sale_payments[] = $obj;
            }
        }

        // invoice only fields
        if ($invoice){
            $this->logo_url  = ($_SERVER['HTTPS']!==""?"https://":"http://").$_SERVER['SERVER_NAME'].$config['general']->bizlogo;
            $this->business_name  = $config['general']->bizname;
            $this->business_address  = $config['general']->bizaddress;
            $this->business_suburb  = $config['general']->bizsuburb;
            $this->business_state  = $config['general']->bizstate;
            $this->business_postcode  = $config['general']->bizpostcoe;
            $this->business_country  = $config['general']->bizcountry;
            $this->business_number  = $config['general']->biznumber;
            $this->invoice_duedt = $this->Utils->getDateFromTimestamp($data->duedt, $config['general']->dateformat);
            $this->invoice_paid = $data->total - $data->balance;
            $this->invoice_balance = $data->balance;
            if (isset($data->custid) && $data->custid>0) {
                $custMdl = new WposAdminCustomers();
                $this->customer = $custMdl->getCustomerData($data->custid);
            }
            $this->payment_instructions = $config['invoice']->payinst;
            // invoice needs item tax calculated
            foreach ($this->sale_items as $key=>$value){
                $value->tax->items = $this->getTaxArray($value->tax->values);
                $this->sale_items[$key] = $value;
            }
        } else {
            // POS sale only fields
            $this->header_line1  = $config['general']->bizname;
            $this->header_line2  = $config['pos']->recline2;
            $this->header_line3  = $config['pos']->recline3;
            $this->logo_url  = ($_SERVER['HTTPS']!==""?"https://":"http://").$_SERVER['SERVER_NAME'].$config['pos']->recemaillogo;
            $this->footer  = $config['pos']->recfooter;
            $this->qrcode_url = $config['pos']->recqrcode!=""?($_SERVER['HTTPS']!==""?"https://":"http://").$_SERVER['SERVER_NAME']."/docs/qrcode.png":null;

            // format refunds
            if (isset($data->refunddata)) {
                $this->sale_refunds = [];
                $lastrefindex = 0; $lastreftime = 0;
                foreach ($data->refunddata as $key=>$refund) {
                    // find last refund for integrated eftpos receipt
                    if ($refund->processdt > $lastreftime) {
                        $lastrefindex = $key;
                    }
                    $obj = new stdClass();
                    $obj->datetime = $this->Utils->getDateFromTimestamp($refund->processdt, $config['general']->dateformat);
                    $obj->numitems = count($refund->items);
                    $obj->method =  ucfirst($refund->method);
                    $obj->altmethod = isset($altlabels->{$refund->method})?$altlabels->{$refund->method}:ucfirst($refund->method);
                    $obj->amount = $this->Utils->currencyFormat($refund->amount);

                    $this->sale_refunds[] = $obj;
                }
                // check for integrated receipt and replace if found
                if (isset($data->refunddata[$lastrefindex]->paydata) && isset($data->refunddata[$lastrefindex]->paydata->customerReceipt)) {
                    $this->eftpos_receipts = $data->refunddata[$lastrefindex]->paydata->customerReceipt;
                }
            }
        }
    }
}