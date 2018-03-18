<?php
/**
 * WposTransactions is part of Wallace Point of Sale system (WPOS) API
 *
 * WposTransactions is used to administer transactions including deleting, voiding and retracting voids & refunds
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

class WposTransactions {
    // incoming data
    private $data;
    // the current transaction data
    private $trans;

    /**
     * Init object and decode any provided data
     * @param $data
     * @param $id
     * @param $loadTransaction
     */
    function __construct($data=null, $id=null, $loadTransaction=false){
        // parse the data and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
            if ($id!=null){
                $this->data->id = $id;
            }
        }
        if ((isset($this->data->id) || isset($this->data->ref)) && $loadTransaction)
            $this->loadTransaction();
    }

    /**
     * Attempts to load the transaction specified by given data
     * @return bool
     */
    private function loadTransaction(){
        $transMdl = new TransactionsModel();
        if ($this->data->id!=null){
            $result = $transMdl->getById($this->data->id);
        } else {
            if ($this->data->ref==null) return false;
            $result = $transMdl->getByRef($this->data->ref);
        }
        if ($result!==false){
            if (isset($result[0]))
                if (($this->trans = json_decode($result[0]['data']))){
                    return true;
                }
            return false;
        }
        return false;
    }

    public function getCurrentTransaction(){
        return $this->trans;
    }

    /**
     * Add a transaction history record.
     * @param $saleid
     * @param $userid
     * @param $type
     * @param $desc
     * @return bool|string
     */
    public static function addTransactionHistory($saleid, $userid, $type, $desc){
        $histMdl = new TransHistModel();
        return $histMdl->create($saleid, $userid, $type, $desc);
    }

    /**
     * Get transaction history for the specified sale
     * @param $result
     * @return mixed
     */
    public function getTransactionHistory($result){
        $histMdl = new TransHistModel();
        $history = $histMdl->get($this->data->id);
        if ($history===false){
            $result['error'] = "Could not get transaction history: ".$histMdl->errorInfo;
        } else {
            $result['data'] = $history;
        }
        return $result;
    }

    /**
     * Fetches a single transaction record
     * @param $result
     * @return mixed
     */
    public function getTransaction($result){
        if (!isset($this->data->id) && (!isset($this->data->ref) && !isset($this->data->refs))){
            $result['error'] = "Transaction id or refs must be provided";
            return $result;
        }
        $transMdl = new TransactionsModel();
        if (isset($this->data->id) && !isset($this->data->refs)){
            $qres = $transMdl->getById($this->data->id);
        } else {
            $qres = $transMdl->getByRef((isset($this->data->refs) ? $this->data->refs : $this->data->ref));
        }
        if ($qres===false){
            $result['error'] = $transMdl->errorInfo;
        } else {
            if (count($qres) > 1){
                $sales = [];
                foreach ($qres as $sale) {
                    $jsonObj = json_decode($sale['data'], true);
                    $sales[$sale['ref']] = $jsonObj;
                }
                $result['data'] = $sales;
            } else {
                $result['data'][$qres[0]['ref']] = json_decode($qres[0]['data']);
            }
        }
        return $result;
    }

    /**
     * Delete sale specified by ID, same as delete transaction but sale is broadcast to pos devices
     * @param $result
     * @return mixed
     */
    public function deleteSale($result){
        $saleMdl = new SalesModel();

        if ($saleMdl->remove($this->data->id)===false){
            $result["error"] = "Error:".$saleMdl->errorInfo;
        } else {
            $result['data']= true;
            // broadcast the sale; supplying the id only indicates deletion
            $socket = new WposSocketIO();
            $socket->sendSaleUpdate(null, $this->data->id);

            // log data
            Logger::write("Sale deleted with id:".$this->data->id, "SALE");
        }
        return $result;
    }

    /**
     * Delete transaction specified by ID
     * @param $result
     * @return mixed
     */
    public function deleteTransaction($result){
        if (!isset($this->data->id)){
            $result['error'] = "Transaction id must be provided";
            return $result;
        }
        if ($this->deleteTransactionRecords()===false){
            $result['error'] = "Could not delete the transaction";
            return $result;
        }
        // log data
        Logger::write("Transaction deleted with id: ".$this->data->id, "TRANS");

        return $result;
    }

    /**
     * Remove all records associated with a transaction
     * @return bool
     */
    private function deleteTransactionRecords(){
        $transMdl = new TransactionsModel();
        if ($transMdl->remove($this->data->id)===false){
            return false;
        }
        $itemMdl = new SaleItemsModel();
        if ($itemMdl->removeBySale($this->data->id)===false){
            return false;
        }
        $payMdl = new SalePaymentsModel();
        if ($payMdl->removeBySale($this->data->id)===false){
            return false;
        }
        $voidMdl = new SaleVoidsModel();
        if ($voidMdl->removeBySale($this->data->id)===false){
            return false;
        }
        $histMdl = new TransHistModel();
        if ($histMdl->removeBySale($this->data->id)===false){
            return false;
        }
        return true;
    }

    /**
     * Void the sales specified by ID
     * @param $result
     * @return mixed
     */
    public function voidSale($result){
        // validate input
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // insert entry
        $voidMdl = new SaleVoidsModel();
        $saleMdl = new SalesModel();
        // contruct voiddata object
        $voiddata = new stdClass();
        $voiddata->processdt = time()*1000; // convert to milliseconds
        $voiddata->userid = $_SESSION['userId'];
        $voiddata->deviceid = 0; // id of zero corresponds to "admin dashboard", which is added into the JSON array sent to the client with WposSetup/getConfig
        $voiddata->locationid = 0;
        $voiddata->reason = $this->data->reason;
        // insert void record
        if ($voidMdl->create($this->data->id, 0, 0, 0, $this->data->reason, "", 0, 0,  1, $voiddata->processdt)){
            // get existing sales data
            if (($sale = $saleMdl->getById($this->data->id))!==false){
                // add voiddata to json object
                $data = json_decode($sale[0]['data']);
                $data->voiddata = $voiddata;
                if (!$saleMdl->edit($this->data->id, null, json_encode($data), 3)){
                    $result['error'] = "Could not update the sales record: ".$saleMdl->errorInfo;
                } else {
                    $result['data'] = $data;
                    // return stock to original sale location
                    if (sizeof($data->items)>0){
                        $wposStock = new WposAdminStock();
                        foreach($data->items as $item){
                            if ($item->sitemid>0){
                                $wposStock->incrementStockLevel($item->sitemid, $data->locid, $item->qty, false);
                            }
                        }
                    }
                    // Create transaction history record
                    WposTransactions::addTransactionHistory($this->data->id, $_SESSION['userId'], "Voided", "Transaction Voided");
                    // Success; log data
                    Logger::write("Sale voided with ref:".$data->ref, "VOID");
                }
            } else {
                $result['error'] = "Could not retrieve the sales record: ".$saleMdl->errorInfo;
            }
        } else {
            $result['error'] = "Could not insert void record: ".$voidMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Retract a void or refund using the sale id and void/refund processdt
     * @param $result
     * @return mixed
     */
    public function removeVoidRecord($result){
        $jsonval = new JsonValidate($this->data, '{"id":1, "processdt":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // find entry and delete
        $salesMdl = new SalesModel();
        $voidMdl = new SaleVoidsModel();
        $refitems = [];
        // retrive the sales record
        if (($sale = $salesMdl->getById($this->data->id))!==false){
            // Decode JSON and remove the refund/void
            $jsondata = json_decode($sale[0]['data']);
            $recfound = false;
            $foundrecord = null;
            $foundtype = null;
            // check if the void record is a match
            if ($jsondata->voiddata->processdt == $this->data->processdt){
                $foundrecord = $jsondata->voiddata;
                unset($jsondata->voiddata);
                $recfound = true; $foundtype = 'void';
            } else {
                // no void record found with that timestamp, try refunds
                if ($jsondata->refunddata!=null){
                    foreach ($jsondata->refunddata as $key => $refund){
                        if ($refund->processdt == $this->data->processdt){
                            // add the items to the array so we can remove them from qty refunded
                            $refitems = $jsondata->refunddata[$key]->items;
                            // unset the array value, this outputs objects so we need to reformat as array
                            $foundrecord = $jsondata->refunddata[$key];
                            unset($jsondata->refunddata[$key]);
                            $jsondata->refunddata = array_values($jsondata->refunddata);
                            if (sizeof($jsondata->refunddata)==0){
                                unset($jsondata->refunddata);
                            }
                            $recfound = true; $foundtype = 'refund';
                            break;
                        }
                    }
                }
            }
            // calculate updated status
            $status = (isset($jsondata->voiddata)?3:(isset($jsondata->refunddata)?2:1));

            if ($recfound){
                // remove the void db record
                if ($voidMdl->removeBySale($this->data->id, $this->data->processdt)){
                    if (sizeof($refitems)>0){ // if its a refund, remove qty refunded
                        $saleItemsMdl = new SaleItemsModel();
                        // Decrement refunded quantities in the sale_items table
                        foreach ($refitems as $item){
                            $saleItemsMdl->incrementQtyRefunded($this->data->id, $item->id, $item->numreturned, false);
                        }
                    }
                    if (!$salesMdl->edit($this->data->id, null, json_encode($jsondata), $status)){
                        $result["error"] = "Could not update sales table. Error:".$salesMdl->errorInfo;
                    } else {
                        $result['data'] = $jsondata;
                        // if sale has been unvoided, remove item stock from the location where created
                        if (($foundtype=='void' && $status!=3) && sizeof($jsondata->items)>0){
                            $wposStock = new WposAdminStock();
                            foreach($jsondata->items as $item){
                                if ($item->sitemid>0){
                                    $wposStock->incrementStockLevel($item->sitemid, $jsondata->locid, $item->qty, true);
                                }
                            }
                        }
                        // Create transaction history record
                        WposTransactions::addTransactionHistory($this->data->id, $_SESSION['userId'], "Retract", "Transaction Void/Refund Retracted");
                        // Success; log data
                        Logger::write("Retracted void/refund from:".$jsondata->ref, "RETRACT", json_encode($foundrecord));
                    }
                } else {
                    $result["error"] = "Could not remove void record. Error:".$voidMdl->errorInfo;
                }
            } else {
                $result["error"] = "Could not find the record in the JSON data: ".print_r($jsondata);
            }
        } else {
            $result["error"] = "Could not fetch the sales record. Error:".$salesMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Generate invoice for the specified transaction
     */
    public function generateInvoice(){
        // make sure sale is loaded
        if (!$this->trans){
            if ($this->loadTransaction()===false){
                die("Failed to load the transaction!");
            }
        }
        $html = $this->generateInvoiceHtml($_REQUEST['template']);
        if (isset($_REQUEST['type']) && $_REQUEST['type']=="html"){
            if (isset($_REQUEST['download']) && $_REQUEST['download']==1){
                header("Content-Type: application/stream");
                header('Content-Disposition: attachment; filename="Invoice #'.$this->trans->ref.'.html"');
            }
            echo($html);
        } else {
            $output = (isset($_REQUEST['download']) && $_REQUEST['download']==1)?2:1;
            $this->convertToPdf($html, $output);
        }
        exit;
    }

    /**
     * Generate invoice for the specified transaction
     * @param $result
     * @return mixed
     */
    public function emailInvoice($result){
        // validate json
        $jsonval = new JsonValidate($this->data, '{"id":1, "to":""}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if (!$this->trans){
            if ($this->loadTransaction()===false){
                die("Failed to load the transaction!");
            }
        }
        // Generate Invoice PDF
        $html = $this->generateInvoiceHtml($this->data->template);
        $pdf = $this->convertToPdf($html, 0);
        $attachment = [$pdf, "Invoice #".$this->trans->ref.".pdf"];
        $subject = (isset($this->data->subject)?$this->data->subject:"Invoice #".$this->trans->ref." Attached");
        $message = (isset($this->data->message) && $this->data->message!==""?$this->data->message:"Please find the attached invoice");
        $cc = isset($this->data->cc)?$this->data->cc:null;
        $bcc = isset($this->data->bcc)?$this->data->bcc:null;
        // Constuct & send email
        $email = new WposMail();
        $emlresult = $email->sendHtmlEmail($this->data->to, $subject, $message, $cc, $bcc, $attachment);
        if ($emlresult!==true){
            $result['error']=$emlresult;
        } else {
            // Create transaction history record
            WposTransactions::addTransactionHistory($this->trans->id, $_SESSION['userId'], "Emailed", "Invoice emailed to: ".$this->data->to.($cc!=null?",".$cc:"").($bcc!=null?",".$bcc:""));
        }

        return $result;
    }

    /**
     * Generate invoice html
     * @param string $template
     * @return string
     */
    private function generateInvoiceHtml($template=""){
        $tempMdl = new WposTemplates();
        $config = new WposAdminSettings();
        $invval = $config->getSettingsObject("invoice");
        $genval = $config->getSettingsObject("general");
        // create the data class
        $data = new WposTemplateData($this->trans, ['general'=>$genval, 'invoice'=>$invval], true);
        return $tempMdl->renderTemplate($template!=""?$template:$invval->defaulttemplate, $data);
    }

    /**
     * Convert HTML to PDF
     * @param $html
     * @param int $output
     * @return bool|string
     */
    private function convertToPdf($html, $output=0){
        require_once($_SERVER['DOCUMENT_ROOT']."/library/dompdf/dompdf_config.inc.php");
        $dompdf = new DOMPDF();
        $dompdf->load_html($html);
        $dompdf->render();
        //output PDF document according to type
        if ($output==0){
            $pdfdoc=$dompdf->output();
            return $pdfdoc;
        } else {
            if ($output==1){
                // Display Inline
                $dompdf->stream("Invoice #".$this->trans->ref.".pdf", ["Attachment"=>0]);
            } else {
                // Download
                $dompdf->stream("Invoice #".$this->trans->ref.".pdf");
            }
        }
        return true;
    }

    /**
     * Save template (not currently used)
     * @param $type
     * @param $template
     */
    public function savetemplate($type, $template){
        // open file
        $file = fopen($_SERVER['DOCUMENT_ROOT']."/docs/templates/".$type.".php", "w");
        // write data
        fwrite($file, $template);
    }
    /**
     * Reset template to default (not currently used)
     * @param $type
     */
    public function resettemplate($type){
        // get original file in string
        $template = file_get_contents($_SERVER['DOCUMENT_ROOT']."/docs-template/templates/".$type.".php");
        // open file
        $file = fopen($_SERVER['DOCUMENT_ROOT']."/docs/templates/".$type.".php", "w");
        // write data
        fwrite($file, $template);
    }
}