<?php
/**
 * WposSale is part of Wallace Point of Sale system (WPOS) API
 *
 * WposSale is used to process orders, sales, refunds and void
 * It additionally emails receipts and processes new and updated customer records
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
 */
class WposPosSale {
    /**
     * @var SalesModel
     */
    private $salesMdl;
    /**
     * @var String sale reference extracted from json data
     */
    private $ref;
    /**
     * @var int global transaction id
     */
    private $id;
    /**
     * @var String sale processing time
     */
    private $dt;
    /**
     * @var stdClass customer data
     */
    private $custdata;
    /**
     * @var bool email receipt flag
     */
    private $emailrec = false;
    /**
     * @var stdClass decoded sales object
     */
    private $jsonobj;
    /**
     * @var stdClass decoded refund object only
     */
    private $refunddata;
    /**
     * @var stdClass decoded void object only
     */
    private $voiddata;
    /**
     * @var String updated sale notes
     */
    private $salenotes;
    /**
     * @var int processing device id
     */
    private $deviceid;
    /**
     * @var boolean Tells system not to broadcast added sales, used for data import.
     */
    private $nobroadcast = false;


    /**
     * Init, decode any included data
     * @param $jsondata
     * @param bool $newtransaction
     */
    public function __construct($jsondata, $newtransaction = true)
    {
        if ($jsondata) {
            if ($newtransaction == true) {
                $this->jsonobj = $jsondata;
                $this->extractSaleData();
            } else {
                $this->extractVoidData($jsondata);
            }
        }
        $this->salesMdl = new SalesModel();
    }
    /**
     * Sets broadcasting of the sale
     * @param bool $set
     */
    public function setNoBroadcast($set = true){
        $this->nobroadcast = $set;
    }

    /**
     * Retrieves needed database fields from the clients json transaction data ($sale).
     */
    private function extractSaleData()
    {
        $this->ref = $this->jsonobj->ref;
        $this->custdata = (isset($this->jsonobj->custdata) ? $this->jsonobj->custdata : null);
        // check for void or refund data
        $this->refunddata = (isset($this->jsonobj->refunddata) ? $this->jsonobj->refunddata : null);
        $this->voiddata = (isset($this->jsonobj->voiddata) ? $this->jsonobj->voiddata : null);
    }

    /**
     * Fetch a current transaction for a void/refund transaction without current data provided
     */
    private function extractDbData($data){
        $this->jsonobj = json_decode($data);
        $this->id = $this->jsonobj->id;
        $this->ref = $this->jsonobj->ref;
    }

    /**
     * Extract data from a void/refund record
     */
    private function extractVoidData($data)
    {;
        $this->ref = $data->ref;
        $this->salenotes = (isset($data->notes) ? $data->notes : null);
        // set void and refund data
        $this->refunddata = (isset($data->refunddata) ? $data->refunddata : null);
        $this->voiddata = (isset($data->voiddata) ? $data->voiddata : null);
    }

    /**
     * Save a new order or update an existing, this updates the data column of the database using provided reference
     * @param $result
     * @return mixed
     */
    public function setOrder($result){
        // Check for existing order, add or update accordingly
        if (($sale=$this->salesMdl->getByRef($this->ref))===false){
            $result["error"] = "Could not lookup order: ".$this->salesMdl->errorInfo;
            return $result;
        }
        if (sizeof($sale)===0) {
            // create a new order
            if (($gid=$this->insertTransactionRecord(0, 0))===false){ // status 0 indicates an incomplete order
                $result["error"] = "Could not add order: ".$this->salesMdl->errorInfo;
                return $result;
            }
            $this->id = $gid;
            $this->jsonobj->id = $gid; // set gid and update json in db
            $this->jsonobj->dt = date("Y-m-d H:i:s");
            if ($this->salesMdl->edit($gid, null, json_encode($this->jsonobj))===false){
                $this->removeTransactionRecords();
                $result["error"] = "Could not update order ID, changes have been rolled back: ".$this->salesMdl->errorInfo;
                return $result;
            }
            // log data
            Logger::write("Order added with ref: ".$this->ref." (ID:".$this->id.")", "ORDER", json_encode($this->jsonobj));
        } else {
            if ($this->updateOrderRecord()===false){
                $result["error"] = "Could not update order: ".$this->salesMdl->errorInfo;
                return $result;
            }
            // log data
            Logger::write("Order updated with ref: ".$this->ref." (ID:".$this->id.")", "ORDER", json_encode($this->jsonobj));
        }

        $result['data'] = $this->jsonobj;

        // broadcast to other devices
        $this->broadcastSale($this->jsonobj->devid);

        return $result;
    }

    /**
     * Remove an order using the specified reference
     * @param $result
     * @return mixed
     */
    public function removeOrder($result){
        // Check for existing order
        if (($sale=$this->salesMdl->getByRef($this->ref))===false){
            $result["error"] = "Could not lookup order: ".$this->salesMdl->errorInfo;
            return $result;
        }
        if (sizeof($sale)===0) {
            $result["error"] = "Could not find record to update";
            return $result;
        }
        if ($this->removeOrderRecord()===false){
            $result["error"] = "Could not remove order: ".$this->salesMdl->errorInfo;
            return $result;
        }
        // broadcast to other devices; include the delete flag
        $this->broadcastSale($this->jsonobj->devid, false, true);

        // log data
        Logger::write("Order deleted with ref: ".$this->ref." (ID:".$this->id.")", "ORDER");

        return $result;
    }

    /**
     * Updates the current order
     * @return bool
     */
    private function updateOrderRecord(){
        if ($this->salesMdl->edit(null, $this->ref, json_encode($this->jsonobj))===false){
            return false;
        }
        return true;
    }

    /**
     * Removes the current order
     * @return bool
     */
    private function removeOrderRecord(){
        if ($this->salesMdl->removeOrder($this->ref)===false){
            return false;
        }
        return true;
    }

    /**
     * processes a sale or a sale + refunds,voids; if an order for this transaction is already present in the system, it is updated/committed as a sale
     * @param $result
     * @return mixed
     */
    public function insertTransaction($result)
    {
        $jsonval = new JsonValidate($this->jsonobj, '{"ref":"", "userid":1, "devid":1, "locid":1, "items":"[", "payments":"[", "cost":1, "total":1, "processdt":1}');
        if (($errors = $jsonval->validate())!==true){
            // fix for offline sales not containing cost field and getting stuck
            if (strpos($errors, "cost must be specified")!==false){
                $this->jsonobj->cost = 0.00;
            } else {
                $result['error'] = $errors;
                return $result;
            }
        }
        // check for existing record, if record exists (it's an order), we need to clear the old data to add the most current.
        if (($sale=$this->salesMdl->getByRef($this->ref))===false){
            $result["error"] = "Could not look for existing records: ".$this->salesMdl->errorInfo;
            return $result;
        }
        // set flag to update existing order record.
        $orderid = (sizeof($sale)>0?$sale[0]['id']:0);
        // process customer data and get id
        $this->processCustomer();
        // set email receipt flag
        $this->emailrec = isset($this->jsonobj->emailrec);
        unset($this->jsonobj->emailrec); // unset, we don't need to store this in the database
        // insert the transaction record
        if (($gid = $this->insertTransactionRecord(1, $orderid))) {
            $this->id = $gid;
            $this->dt = date("Y-m-d H:i:s");
            // insert items and payments. If these fail, try to reverse the transaction; incomplete transactions should not exist
            if (!$this->insertTransactionItems() || !$this->insertTransactionPayments()) {
                // If the sale is a current order, roll back to previous order data, otherwise remove the sale object
                if ($orderid>0){
                    $this->jsonobj = json_decode($sale[0]['data']);
                    $rollbackRes = $this->insertTransactionRecord(0, $orderid);
                } else {
                    $rollbackRes = $this->removeTransactionRecords();
                }
                if ($rollbackRes!==false) {
                    $result['error'] = "My SQL server error: the transactions did not complete successfully and has been rolled back: ".$this->itemErr.$this->paymentErr;
                } else {
                    $result['error'] = "My SQL server error: the transaction did not complete and the changes failed to roll back please contact support to remove invalid records: ".$this->itemErr.$this->paymentErr;
                }
                return $result;
            }
            // add global id & dt stamp to json data and set updated response
            $this->jsonobj->id = $this->id;
            $this->jsonobj->dt = $this->dt;
            $this->jsonobj->balance = 0; //TODO: Actually validate that balance is 0!
            $this->jsonobj->status = 1;

            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $this->jsonobj->userid, "Created", "Sale created");
            // log data
            Logger::write("Sale Processed with ref: ".$this->ref." (ID:".$this->id.")", "SALE", json_encode($this->jsonobj));
        } else {
            if ($orderid==0)
                $this->removeTransactionRecords(); // This is probably not needed but oh well
            $result['error'] = "My SQL server error: the transaction did not complete successfully and has been rolled back. ".$this->salesMdl->errorInfo;
            return $result;
        }
        // check if the sale has void/refund data and process it
        if ($result['error'] == "OK" and (isset($this->voiddata) or isset($this->refunddata))) {
            $result = $this->insertVoid($result);
        } else {
            // commit new data, this saved item/payment ids into main json
            $this->salesMdl->edit(null, $this->ref, json_encode($this->jsonobj));
        }
        // broadcast to other devices
        if ($this->nobroadcast==false){
            $this->broadcastSale($this->jsonobj->devid);
        }
        // send receipt if specified
        $result = $this->processReceipt($result);

        $result['data'] = $this->jsonobj;
        return $result;
    }

    /**
     * Process refund & void records only (the sale already has a ID)
     * @param $result
     * @return mixed
     */
    public function insertVoid($result)
    {
        $this->salesMdl = new SalesModel();
        $hasrefund = ($this->refunddata!==null?true:false);
        $hasvoid = ($this->voiddata!==null?true:false);
        $status = (($hasrefund or $hasvoid)?($hasvoid?3:2):1);
        $newtran = true;
        // validate values
        if ($hasvoid){
            $jsonval = new JsonValidate($this->voiddata, '{"userid":1, "deviceid":1, "locationid":1, "reason":"", "processdt":1}');
            if (($errors = $jsonval->validate())!==true){
                $result['error'] = $errors;
                return $result;
            }
        }
        if ($hasrefund){
            foreach ($this->refunddata as $refund){
                $jsonval = new JsonValidate($refund, '{"userid":1, "deviceid":1, "locationid":1, "reason":"", "processdt":1, "items":"[", "method":"", "amount":1}');
                if (($errors = $jsonval->validate())!==true){
                    $result['error'] = $errors;
                    return $result;
                }
            }
        }
        // processing for the current transaction?, if not we need to fetch the record from the database and update the JSON object
        if ($this->jsonobj == null) {
            $newtran = false; // void/refund of an old transaction
            // get record with the current ref
            if (($dbresult = $this->salesMdl->getByRef($this->ref))!==false) {
                // load sales json vars
                $this->extractDbData($dbresult[0]['data']);
            } else {
                $result["error"] = "Could not find record in the database to update.";
                return $result;
            }
            // update json sale data with new void/refund data
            if ($hasrefund) {
                $this->jsonobj->refunddata = $this->refunddata;
            }

            if ($hasvoid) {
                $this->jsonobj->voiddata = $this->voiddata;
            }
        }

        $this->jsonobj->status = $status;

        // check for void record and insert
        $result = $this->insertVoidRecords($hasrefund, $hasvoid, $result);

        if ($result["error"] == "OK"){
        // update database with new json data and void indicator
        if ($this->salesMdl->edit(null, $this->ref, json_encode($this->jsonobj), $status) !== false) {
            if (!$newtran){
                $result['data'] = $this->jsonobj; // only need to update if an old transaction
                // broadcast to other devices
                $this->broadcastSale($this->deviceid, true); // add flag indicating updated sale (for admin dashboard)
            }
        } else {
            $result["error"] = $this->salesMdl->errorInfo;
        }
        }
        return $result;
    }

    /**
     * Update notes for a current transaction
     * @param $result
     * @return mixed
     */
    public function updateTransationNotes($result){
        if ($this->salenotes==null){
            $result["error"] = "notes must be provided";
            return $result;
        }
        if (($sale=$this->salesMdl->getByRef($this->ref))===false){
            $result["error"] = $this->salesMdl->errorInfo;
            return $result;
        }
        // sale
        if (sizeof($sale)===0){
            $result["error"] = "Could not find the specified sale for updating!";
            return $result;
        }
        // update the notes
        $this->jsonobj = json_decode($sale[0]['data']);
        if ($this->jsonobj === false){
            $result["error"] = "Failed to decode the current sales data, it may be corrupt!";
            return $result;
        }
        $this->jsonobj->notes = $this->salenotes;

        if ($this->salesMdl->edit($sale[0]['id'], null, json_encode($this->jsonobj))===false){
            $result["error"] = "Failed to update sales notes!";
        }

        return $result;
    }

    /**
     * Insert or update a transactions main record
     * @param $status
     * @param $orderid
     * @return bool|string
     */
    private function insertTransactionRecord($status, $orderid)
    {
        if ($orderid==0){
            if (($gid = $this->salesMdl->create($this->ref, json_encode($this->jsonobj), $status, $this->jsonobj->userid, $this->jsonobj->devid, $this->jsonobj->locid, $this->jsonobj->custid, $this->jsonobj->discount, $this->jsonobj->rounding, $this->jsonobj->cost, $this->jsonobj->total, $this->jsonobj->processdt))) {
                return $gid;
            } else {
                return false;
            }
        } else {
            if ($this->salesMdl->edit($orderid, null, json_encode($this->jsonobj), $status, $this->jsonobj->userid, $this->jsonobj->devid, $this->jsonobj->locid, $this->jsonobj->custid, $this->jsonobj->discount, $this->jsonobj->rounding, $this->jsonobj->cost, $this->jsonobj->total, $this->jsonobj->processdt)!==false){
                return $orderid;
            }
        }
        return false;
    }

    /**
     * Add any new void records for the transaction
     * @param $hasrefund
     * @param $hasvoid
     * @param $result
     * @return mixed
     */
    private function insertVoidRecords($hasrefund, $hasvoid, $result)
    {
        $voidMdl = new SaleVoidsModel();
        // update new refund records
        if ($hasrefund){
            $saleItemsMdl = new SaleItemsModel();
            foreach ($this->refunddata as $refund){
                // Check if record has already been processed
                if (!$voidMdl->recordExists($this->id, $refund->processdt)){
                    $this->deviceid = $refund->deviceid; // set device id for the broadcast function
                    $voidMdl->create($this->id, $refund->userid, $refund->deviceid, $refund->locationid, $refund->reason, $refund->method, $refund->amount, json_encode($refund->items), 0, $refund->processdt);
                    // Increment refunded quantities in the sale_items table
                    foreach ($refund->items as $item){
                        $saleItemsMdl->incrementQtyRefunded($this->id, $item->ref, $item->numreturned);
                    }
                    // Create transaction history record
                    WposTransactions::addTransactionHistory($this->id, isset($_SESSION['userId'])?$_SESSION['userId']:0, "Refunded", "Sale refunded");
                    // log data
                    Logger::write("Refund processed with ref: ".$this->ref." (ID:".$this->id.")", "REFUND", json_encode($refund));
                }

            }
        }

        if ($hasvoid){
            // Check if record has already been processed
            if (!$voidMdl->recordExists($this->id, $this->voiddata->processdt)){
                $this->deviceid = $this->voiddata->deviceid; // set device id for the broadcast function
                $id = $voidMdl->create($this->id, $this->voiddata->userid, $this->voiddata->deviceid, $this->voiddata->locationid, $this->voiddata->reason, "", 0, 0,  1, $this->voiddata->processdt);
                if (!$id>0){
                    $result["error"].= $voidMdl->errorInfo;
                } else {
                    // return stock to original sale location
                    if (sizeof($this->jsonobj->items)>0){
                        $wposStock = new WposAdminStock();
                        foreach($this->jsonobj->items as $item){
                            if ($item->sitemid>0){
                                $wposStock->incrementStockLevel($item->sitemid, $this->jsonobj->locid, $item->qty, false);
                            }
                        }
                    }
                    // Create transaction history record
                    WposTransactions::addTransactionHistory($this->id, isset($_SESSION['userId'])?$_SESSION['userId']:0, "Voided", "Sale voided");
                    // log data
                    Logger::write("Sale voided with ref: ".$this->ref." (ID:".$this->id.")", "VOID", json_encode($this->voiddata));
                }
            }
        }
        return $result;
    }

    private $itemErr = "";
    /**
     * Insert transaction item records
     * @return bool
     */
    private function insertTransactionItems()
    {
        $itemsMdl = new SaleItemsModel();
        //$stockMdl = new StockModel();
        $wposStock = new WposAdminStock();
        foreach ($this->jsonobj->items as $key=>$item) {
            // fix for offline sales not containing cost field and getting stuck
            if (!isset($item->cost)) $item->cost = 0.00;
            $unit_original = (isset($item->unit_original) ? $item->unit_original : $item->unit);
            if (!$res=$itemsMdl->create($this->id, $item->sitemid, $item->ref, $item->qty, $item->name, $item->desc, $item->taxid, $item->tax, $item->cost, $item->unit, $item->price, $unit_original)) {
                $this->itemErr = $itemsMdl->errorInfo;
                return false;
            }
            // decrement stock level
            if ($item->sitemid>0){
                /*$stockMdl->incrementStockLevel($item->sitemid, $this->jsonobj->locid, $item->qty, true);*/
                $wposStock->incrementStockLevel($item->sitemid, $this->jsonobj->locid, $item->qty, true);
            }
            $this->jsonobj->items[$key]->id = $res;
        }
        return true;
    }

    private $paymentErr = "";
    /**
     * Insert transaction payment records
     * @return bool
     */
    private function insertTransactionPayments()
    {
        $payMdl = new SalePaymentsModel();
        foreach ($this->jsonobj->payments as $key=>$payment) {
            if (!$id=$payMdl->create($this->id, $payment->method, $payment->amount, $this->jsonobj->processdt)) {
                $this->paymentErr = $payMdl->errorInfo;
                return false;
            }
            $this->jsonobj->payments[$key]->id = $id;
            $this->jsonobj->payments[$key]->processdt = $this->jsonobj->processdt;
        }
        return true;
    }


    /**
     * Remove all transaction records associated with a sale
     * @return bool
     */
    private function removeTransactionRecords()
    {
        $itemsMdl = new SaleItemsModel();
        $payMdl = new SalePaymentsModel();
        if ($this->salesMdl->remove($this->id) !== false) {
            if ($payMdl->removeBySale($this->id) !== false) {
                if ($itemsMdl->removeBySale($this->id) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Process customer data if set
     * @return bool
     */
    private function processCustomer()
    {
        // is updated data available
        if ($this->custdata != null) {
            // if customer record (id) exists
            if ($this->jsonobj->custid > 0) {

                if (WposAdminCustomers::updateCustomerData($this->custdata)===true){
                    unset($this->jsonobj->custdata); // unset customer data; we don't need to send this back
                    return true;
                } else {
                    return false;
                }

            } else {
                $id = WposAdminCustomers::addCustomerData($this->custdata);
                if (is_numeric($id)) {
                    $this->jsonobj->custid = $id;
                    unset($this->jsonobj->custdata); // unset customer data; we don't need to send this back
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            // email only customers are not added for now (email only used for e-receipt); maybe an option.
            return true;
        }
    }

    /**
     * Broadcast the transaction to admin dash & devices included via config
     * @param $curdeviceid
     * @param bool $updatedsaleflag
     * @param bool $delete
     * @return bool
     */
    private function broadcastSale($curdeviceid, $updatedsaleflag = false, $delete = false){
        $socket = new WposSocketIO();
        $wposConfig = new WposAdminSettings();
        $config = $wposConfig->getSettingsObject("pos");
        $devices = [];

        switch ($config->saledevice){
            case "device": return true; // no need to do anything, the device will already get the updated record
            case "all":
                // get all device id's
                $devMdl = new DevicesModel();
                $devices = $devMdl->getDeviceIds();
                break;
            case "location":
                // get location device id array
                $devMdl = new DevicesModel();
                $devices = $devMdl->getLocationDeviceIds($curdeviceid);
                break;
        }

        // put devices into object, node.js array functions suck
        $dobject = new stdClass();
        foreach ($devices as $value){
           if ($curdeviceid!=$value){ // remove the current device from broadcast
                $dobject->$value = $value;
           }
        }

        if ($updatedsaleflag==true){
            $this->jsonobj->isupdate = 1;
        }

        $socket->sendSaleUpdate($dobject, ($delete?$this->jsonobj->ref:$this->jsonobj));

        return true;
    }

    /**
     * Email a receipt if the flag is set and customer email is not blank
     * @param $result
     * @return mixed
     */
    private function processReceipt($result){
        // does customer want a receipt?
        if ($this->emailrec==true && $this->jsonobj->custemail!=""){
            $emailresult = $this->emailReceipt($this->jsonobj->custemail);
            if ($emailresult!==true){
                $result['warning'] = $emailresult;
            }
        }
        return $result;
    }

    private function emailReceipt($email){
        $tempMdl = new WposTemplates();
        $config = new WposAdminSettings();
        $recval = $config->getSettingsObject("pos");
        $genval = $config->getSettingsObject("general");
        // create the data class
        $data = new WposTemplateData($this->jsonobj, ['general'=>$genval, 'pos'=>$recval]);
        $html = $tempMdl->renderTemplate($recval->rectemplate, $data);

        $wposMail = new WposMail($genval);

        if(($mresult=$wposMail->sendHtmlEmail($email, 'Your '. $genval->bizname .' receipt', $html))!==true) {
            return 'Failed to email receipt: ' . $mresult;
        } else {
            return true;
        }
    }

}