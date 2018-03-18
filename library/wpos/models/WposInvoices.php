<?php
/**
 * WposInvoices is part of Wallace Point of Sale system (WPOS) API
 *
 * WposInvoices is used to create, edit & manage invoice transactions
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
class WposInvoices {
    /**
     * @var stdClass provided params
     */
    private $data;
    /**
     * @var stdClass provided params
     */
    private $invoice;
    /**
     * @var int invoice id
     */
    private $id = null;
    /**
     * @var string invoice ref
     */
    private $ref = null;
    /**
     * @var InvoicesModel
     */
    private $invMdl;

    private $import = false;

    /**
     * Decode provided input, if reference or id is
     * @param $data
     * @param null $id
     * @param bool $import
     */
    function __construct($data=null, $id=null, $import=false){
        // parse the data and put it into an object
        $this->invMdl = new InvoicesModel();
        $this->import = $import;
        if ($data!==null){
            $this->data = $data;
            if (isset($this->data->id)){
                $this->id = $this->data->id;
                if ($import==false)
                    if (!$this->loadData())
                        return false;
            }
        } else {
            $this->data = new stdClass();
            if ($id!==null){
                $this->id = $id;
                if (!$this->loadData())
                    return false;
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    private function loadData(){
        if ($this->id!==null){
            $result = $this->invMdl->getById($this->id);
        } else {
            $result = $this->invMdl->getByRef($this->data->ref);
        }
        if ($result!==false){
            if (isset($result[0]))
                if (($this->invoice = json_decode($result[0]['data']))){
                    $this->id = $this->invoice->id;
                    $this->ref = $this->invoice->ref;
                    return true;
                }
            return false;
        }
        return false;
    }

    public function getInvoices($result){
        // if range is not set, use the default
        if (!isset($this->data->stime) && !isset($this->data->etime)){
            $this->data->etime = time()*1000;
            $this->data->stime = strtotime("-1 month")*1000;
        }
        // get matching range of records & put into json object
        $invoicedata = [];
        $invoices = $this->invMdl->getRange($this->data->stime, $this->data->etime);
        // Get unbalanced and overdue records
        $oinvoices = $this->invMdl->getOpenInvoices();
        if ($invoices===false || $oinvoices===false){
            $result['error'] = "Could not retrieve invoice records: ".$this->invMdl->errorInfo;
        } else {
            $invoices = array_merge($invoices, $oinvoices);
            foreach ($invoices as $invoice){
                $jsondata = json_decode($invoice['data']);
                $jsondata->type = $invoice['type'];
                $invoicedata[$invoice['ref']] = $jsondata;
            }
            $result['data'] = $invoicedata;
        }
        return $result;
    }

    /**
     * Searches sales for the given reference.
     * @param $searchdata
     * @param $result
     * @return mixed Returns sales that match the specified ref.
     */
    public function searchInvoices($searchdata, $result)
    {
        $salesMdl = new InvoicesModel();
        $dbSales  = $salesMdl->get(null, $searchdata->ref, true);
        if (is_array($dbSales)) {
            $sales = [];
            foreach ($dbSales as $sale) {
                $jsonObj             = json_decode($sale['data'], true);
                $sales[$sale['ref']] = $jsonObj;
            }
            $result['data'] = $sales;
        } else if ($dbSales === false) {
            $result['error'] = $salesMdl->errorInfo;
        }

        return $result;
    }

    public function createInvoice($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"ref":"", "channel":"", "custid":1, "discount":1, "processdt":1, "duedt":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        $this->invoice = $this->data;
        if (!isset($this->invoice->devid)){
            $this->invoice->devid = 0;
        }
        if (!isset($this->invoice->locid)){
            $this->invoice->locid = 0;
        }
        // Get invoice totals
        $this->calculateInvoice();
        $this->invoice->userid = (isset($_SESSION['userId'])?$_SESSION['userId']:$this->invoice->userid);
        // insert main transaction record
        if (($gid=$this->invMdl->create($this->invoice->ref, $this->invoice->channel, json_encode($this->invoice), 0, $this->invoice->userid, $this->invoice->custid, $this->invoice->discount, $this->invoice->total, $this->invoice->balance, $this->invoice->processdt, $this->invoice->duedt))===false){
            $result['error'] = "Could not insert invoice record: ".$this->invMdl->errorInfo;
            return $result;
        }
        // add sale id and processdt to json
        $this->invoice->id = $gid;
        $this->id = $gid;
        $this->invoice->dt = date("Y-m-d H:i:s");
        // insert items
        if (isset($this->invoice->items) && sizeof($this->invoice->items)>0){
            $itemMdl = new SaleItemsModel();
            foreach ($this->invoice->items as $key => $item){
                $unit_original = (isset($item->unit_original) ? $item->unit_original : $item->unit);
                $itemid = $itemMdl->create($this->id, $item->sitemid, 0, $item->qty, $item->name, $item->desc, $item->taxid, $item->tax, $item->cost, $item->unit, $item->price, $unit_original);
                if ($itemid===false){
                    // Roll back transaction
                    $this->deleteInvoice();
                }
                // update json with id
                $this->invoice->items[$key]->id = $itemid;
            }
        } else {
            $this->invoice->items = [];
        }
        // insert payments
        if (isset($this->invoice->payments) && sizeof($this->invoice->payments)>0){
            $payMdl = new SalePaymentsModel();
            foreach ($this->invoice->payments as $key=>$payment){
                $payid = $payMdl->create($this->id, $payment->method, $payment->amount, $this->invoice->processdt);
                if ($payid===false){
                    // Roll back transaction
                    $this->deleteInvoice();
                } else {
                    // update json with id
                    $this->invoice->payments[$key]->id = $payid;
                }
            }
        } else {
            $this->invoice->payments = [];
        }
        // update invoice data
        if ($this->saveInvoiceData()===false){
            // Roll back transaction
            $this->deleteInvoice();
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // decrement stock
            if (!$this->import){
                if (sizeof($this->invoice->items)>0){
                    $wposStock = new WposAdminStock();
                    foreach($this->invoice->items as $item){
                        if ($item->sitemid>0){
                            $wposStock->incrementStockLevel($item->sitemid, 0, $item->qty, true);
                        }
                    }
                }

                // Create transaction history record
                WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Created", "Invoice created");
                // log data
                Logger::write("Invoice created with id: ".$this->id, "INVOICE", json_encode($this->invoice));
            }
        }

        $result['data']= $this->invoice;
        return $result;
    }

    public function importInvoice($result){
        $this->invoice = $this->data;
        if (!isset($this->invoice->devid)){
            $this->invoice->devid = 0;
        }
        if (!isset($this->invoice->locid)){
            $this->invoice->locid = 0;
        }
        // Get invoice totals
        $this->calculateInvoice();
        $this->invoice->userid = (isset($this->invoice->userid)?$this->invoice->userid:$_SESSION['userId']);
        // insert main transaction record
        if (($this->invMdl->import($this->invoice->id, $this->invoice->ref, "manual", json_encode($this->invoice), 0, $this->invoice->userid, $this->invoice->custid, $this->invoice->discount, $this->invoice->total, $this->invoice->balance, $this->invoice->processdt, $this->invoice->duedt))===false){
            $result['error'] = "Could not insert invoice record: ".$this->invMdl->errorInfo;
            return $result;
        }
        // add sale id and processdt to json
        $this->id = $this->invoice->id;
        $this->invoice->dt = date("Y-m-d H:i:s");
        $this->invoice->userid = $_SESSION['userId'];
        // insert items
        if (isset($this->invoice->items) && sizeof($this->invoice->items)>0){
            $itemMdl = new SaleItemsModel();
            foreach ($this->invoice->items as $item){
                $itemid = $itemMdl->import($item->id, $this->id, $item->sitemid, 0, $item->qty, $item->name, $item->desc, $item->taxid, $item->tax, $item->unit, $item->price);
                if ($itemid===false){
                    // Roll back transaction
                    $this->deleteInvoice();
                    $result['error'] = "Could not insert invoice item, transaction rolled back : ".$itemMdl->errorInfo;
                    return $result;
                }
            }
        } else {
            $this->invoice->items = [];
        }
        // insert payments
        if (isset($this->invoice->payments) && sizeof($this->invoice->payments)>0){
            $payMdl = new SalePaymentsModel();
            foreach ($this->invoice->payments as $payment){
                $payid = $payMdl->import($payment->id, $this->id, $payment->method, $payment->amount, $this->invoice->processdt);
                if ($payid===false){
                    // Roll back transaction
                    $this->deleteInvoice();
                    $result['error'] = "Could not insert invoice payment, transaction rolled back : ".$payMdl->errorInfo;
                    return $result;
                }
            }
        } else {
            $this->invoice->payments = [];
        }
        // update invoice data
        if ($this->saveInvoiceData()===false){
            // Roll back transaction
            $this->deleteInvoice();
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        }
        $result['data']= $this->invoice;
        return $result;
    }

    public function updateInvoice($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"id":1, "processdt":1, "duedt":1, "discount":1, "closedt":"~"}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        $updateinv =  ($this->invoice->discount!=$this->data->discount)?true:false;
        // Update json values
        $this->invoice->notes = $this->data->notes;
        $this->invoice->processdt = $this->data->processdt;
        $this->invoice->duedt = $this->data->duedt;
        $this->invoice->discount = $this->data->discount;
        $this->invoice->closedt = $this->data->closedt;
        // update invoice data
        if ($updateinv) $this->calculateInvoice();
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Invoice Modified");
            // log data
            Logger::write("Invoice updated with id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data'] = $this->invoice;
        return $result;
    }

    public function removeInvoice($result){
        if (!isset($this->data->id)){
            $result['error'] = "Sale id must be provided";
            return $result;
        }
        if ($this->deleteInvoice()===false){
            $result['error'] = "Could not delete the invoice";
            return $result;
        }
        // log data
        Logger::write("Invoice deleted with id: ".$this->id, "INVOICE");

        return $result;
    }

    public function addItem($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"id":1, "sitemid":1, "qty":1, "name":"", "desc":"~", "taxid":1, "tax":"{", "cost":1, "unit":1, "price":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // insert item record
        $itemMdl = new SaleItemsModel();
        $unit_original = (isset($this->data->unit_original) ? $this->data->unit_original : $this->data->unit);
        if (($itemid = $itemMdl->create($this->data->id, $this->data->sitemid, 0, $this->data->qty, $this->data->name, $this->data->desc, $this->data->taxid, $this->data->tax, $this->data->cost, $this->data->unit, $this->data->price, $unit_original))===false){
            $result['error'] = "Could not insert item record: ".$itemMdl->errorInfo;
            return $result;
        }
        // set item in json
        $this->data->id = $itemid;
        $this->invoice->items[] = $this->data;
        // Update invoice totals
        $this->calculateInvoice();
        // update invoice data
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // decrement stock
            if ($this->data->sitemid>0){
                $wposStock = new WposAdminStock();
                $wposStock->incrementStockLevel($this->data->sitemid, 0, $this->data->qty, true);
            }
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Item Added");
            // log data
            Logger::write("Invoice item added for invoice id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data']= $this->invoice;
        return $result;
    }

    public function updateItem($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"id":1, "itemid":1, "sitemid":1, "qty":1, "name":"", "desc":"~", "taxid":1, "tax":"{", "cost":1, "unit":1, "price":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // update item record
        $itemMdl = new SaleItemsModel();
        if ($itemMdl->edit($this->data->itemid, $this->data->sitemid, 0, $this->data->qty, $this->data->name, $this->data->desc, $this->data->taxid, $this->data->tax, $this->data->cost, $this->data->unit, $this->data->price)===false){
            $result['error'] = "Could not update item record: ".$itemMdl->errorInfo;
            return $result;
        }
        $qtydifval = 0; $qtydifdec = false;
        // update item in json
        foreach ($this->invoice->items as $key=>$item){
            if ($this->data->itemid==$item->id){
                // get item difference for stock
                $qtydif = $this->data->qty-$item->qty;
                $qtydifval = abs($qtydif);
                $qtydifdec = $qtydif>0?true:false; // decrement if plus diff
                // update record
                $this->data->id = $this->data->itemid;
                unset($this->data->itemid);
                $this->invoice->items[$key] = $this->data;
                break;
            }
        }
        // Update invoice totals
        $this->calculateInvoice();
        // update invoice data
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // update stock
            if ($this->data->sitemid>0){
                // skip if no change in qty
                if ($qtydifval>0){
                    $wposStock = new WposAdminStock();
                    // increment/decrement stock depending on difference calced above
                    $wposStock->incrementStockLevel($this->data->sitemid, 0, $qtydifval, $qtydifdec);
                }
            }
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Item Modified");
            // log data
            Logger::write("Invoice item modified for invoice id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data']= $this->invoice;
        return $result;
    }

    public function removeItem($result){
        // validate json
        if (!isset($this->data->id) || !isset($this->data->itemid)){
            $result['error'] = "Sale & item id must be provided";
            return $result;
        }
        // update item record
        $itemMdl = new SaleItemsModel();
        if ($itemMdl->removeById($this->data->itemid)===false){
            $result['error'] = "Could not remove item record: ".$itemMdl->errorInfo;
            return $result;
        }
        // delete item in json
        foreach ($this->invoice->items as $key=>$item){
            if ($this->data->itemid==$item->id){
                $this->data->sitemid = $item->sitemid;
                $this->data->qty = $item->qty;
                unset($this->invoice->items[$key]);
                $this->invoice->items = array_values($this->invoice->items);
                break;
            }
        }
        // Update invoice totals
        $this->calculateInvoice();
        // update invoice data
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // increment stock
            if ($this->data->sitemid>0){
                $wposStock = new WposAdminStock();
                $wposStock->incrementStockLevel($this->data->sitemid, 0, $this->data->qty, false);
            }
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Item Removed");
            // log data
            Logger::write("Invoice item removed for invoice id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data']= $this->invoice;
        return $result;
    }

    public function addPayment($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"id":1, "method":"", "amount":1, "processdt":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // insert payment record
        $payMdl = new SalePaymentsModel();
        if (($payid = $payMdl->create($this->data->id, $this->data->method, $this->data->amount, $this->data->processdt))===false){
            $result['error'] = "Could not insert payment record: ".$payMdl->errorInfo;
            return $result;
        }
        // set payment in json, add payid to data object
        $this->data->id = $payid;
        $this->invoice->payments[] = $this->data;
        // Update invoice totals
        $this->calculateInvoice();
        // update invoice data
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Payment Added");
            // log data
            Logger::write("Invoice payment added for invoice id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data']= $this->invoice;
        return $result;
    }

    public function updatePayment($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"id":1, "paymentid":1, "method":"", "amount":1, "processdt":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // update payment record
        $payMdl = new SalePaymentsModel();
        if (($payid = $payMdl->edit($this->data->paymentid, $this->data->method, $this->data->amount, $this->data->processdt))===false){
            $result['error'] = "Could not insert item record: ".$payMdl->errorInfo;
            return $result;
        }
        foreach ($this->invoice->payments as $key=>$item){
            if ($this->data->paymentid==$item->id){
                $this->data->id = $this->data->paymentid;
                unset($this->data->paymentid);
                $this->invoice->payments[$key] = $this->data;
                break;
            }
        }
        // Update invoice totals
        $this->calculateInvoice();
        // update invoice data
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Payment Modified");
            // log data
            Logger::write("Invoice payment modified for invoice id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data']= $this->invoice;
        return $result;
    }

    public function removePayment($result){
        // validate json
        if (!isset($this->data->id) || !isset($this->data->paymentid)){
            $result['error'] = "Sale & item id must be provided";
            return $result;
        }
        // delete payment record
        $payMdl = new SalePaymentsModel();
        if ($payMdl->removeById($this->data->paymentid)===false){
            $result['error'] = "Could not remove payment record: ".$payMdl->errorInfo;
            return $result;
        }
        // delete item in json
        foreach ($this->invoice->payments as $key=>$item){
            if ($this->data->paymentid==$item->id){
                unset($this->invoice->payments[$key]);
                $this->invoice->payments = array_values($this->invoice->payments);
                break;
            }
        }
        // Update invoice totals
        $this->calculateInvoice();
        // update invoice data
        if ($this->saveInvoiceData()===false){
            $result['error'] = "Could not commit invoice data: ".$this->invMdl->errorInfo;
            return $result;
        } else {
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->id, $_SESSION['userId'], "Modified", "Payment Removed");
            // log data
            Logger::write("Invoice payment removed for invoice id: ".$this->id, "INVOICE", json_encode($this->data));
        }

        $result['data']= $this->invoice;
        return $result;
    }

    private function calculateInvoice(){
        $total = 0;
        $cost = 0;
        $payments = 0;
        $this->invoice->taxdata = [];
        $this->invoice->tax = 0;
        $this->invoice->numitems = 0;
        // add items
        if (isset($this->invoice->items)){
            foreach ($this->invoice->items as $key=>$item){
                $item->price = round(floatval($item->qty)*floatval($item->unit), 2);
                $this->invoice->numitems += $item->qty;
                // add tax data to totals
                foreach ($this->invoice->items[$key]->tax->values as $taxid=>$taxval){
                    if (isset($this->invoice->taxdata[$taxid])){
                        $this->invoice->taxdata[$taxid]+= $taxval;
                    } else {
                        $this->invoice->taxdata[$taxid] = $taxval;
                    }
                }
                // Add to total sale tax
                $this->invoice->tax+= $this->invoice->items[$key]->tax->total;
                // if item tax exclusive, add to item total
                if (!$this->invoice->items[$key]->tax->inclusive){
                    $item->price+= $this->invoice->items[$key]->tax->total;
                }
                $total += $item->price;
                $cost += round($item->cost * $item->qty, 2);
                $this->invoice->items[$key]->unit = number_format($item->unit, 2, ".", "");
                $this->invoice->items[$key]->price = number_format($item->price, 2, ".", "");
            }
        }
        // add payments
        if (isset($this->invoice->payments)){
            foreach ($this->invoice->payments as $payment){
                $payments += floatval($payment->amount);
            }
        }
        // set total, payments & balance
        $this->invoice->cost = number_format($cost, 2, ".", "");
        $this->invoice->total = number_format($total, 2, ".", "");
        $this->invoice->subtotal = number_format($total - $this->invoice->tax, 2, ".", "");
        // Get discount amount & apply to total & tax values
        if ($this->invoice->discount>0){
            $this->invoice->discountval = number_format(($this->invoice->total*($this->invoice->discount/100)), 2, ".", "");;
            $this->invoice->total = number_format(($this->invoice->total - $this->invoice->discountval), 2, ".", "");
        } else {
            $this->invoice->discountval = 0;
        }
        // calc balance
        $this->invoice->balance = number_format($this->invoice->total-$payments, 2, ".", "");
        if ($this->invoice->total>$payments){
            // invoice open
            $this->invoice->status = -1;
            unset($this->invoice->closedt);
        } else {
            // invoice closed
            $this->invoice->status = 1;
            if ($this->import == false)
                $this->invoice->closedt = time()*1000;
        }
    }

    private function saveInvoiceData(){
        if ($this->invMdl->edit($this->id, null, json_encode($this->invoice), $this->invoice->status, $this->invoice->discount, $this->invoice->cost, $this->invoice->total, $this->invoice->balance)===false){
            return false;
        }
        return true;
    }

    private function deleteInvoice(){
        if ($this->invMdl->remove($this->id)===false){
            return false;
        }
        $itemMdl = new SaleItemsModel();
        if ($itemMdl->removeBySale($this->id)===false){
            return false;
        }
        $payMdl = new SalePaymentsModel();
        if ($payMdl->removeBySale($this->id)===false){
            return false;
        }
        $voidMdl = new SaleVoidsModel();
        if ($voidMdl->removeBySale($this->id)===false){
            return false;
        }
        $histMdl = new TransHistModel();
        if ($histMdl->removeBySale($this->id)===false){
            return false;
        }
        return true;
    }
}