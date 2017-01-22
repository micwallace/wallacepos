<?php
/**
 * JsonData is part of Wallace Point of Sale system (WPOS) API
 *
 * JsonData is used for retrieving database tables into JSON for use by the pos client.
 * The device,location and tax functions are no longer used much as WposSetup now provides these values alongside the config.
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
 * @since      File available since 17/11/13 2:16 PM
 */
class WposPosData
{
    // these variables will determine which records to provide when requesting sales
    /**
     * @var int deviceId
     */
    var $devid;
    /**
     * @var int locationId
     */
    var $locid;

    /**
     * @var mixed JSON object
     */
    var $data;

    /**
     * Decodes any provided JSON string
     */
    public function __construct($jsondata=null)
    {
        if ($jsondata!==null){
            if (is_string($jsondata)){
                $this->data = json_decode($jsondata);
            } else {
                $this->data = $jsondata;
            }
        }
    }

    /**
     * @param array $result
     *
     * @return array of customer records
     */
    public function getCustomers($result)
    {
        $customersMdl = new CustomerModel();
        $customers    = $customersMdl->get();
        $contacts = $customersMdl->getContacts();
        if (is_array($customers)) {
            $cdata = [];
            foreach ($customers as $customer) {
                $customer['contacts'] = [];
                $cdata[$customer['id']] = $customer;
            }
            // add custoner contacts
            foreach ($contacts as $contact){
                if (isset($cdata[$contact['customerid']])){
                    $cdata[$contact['customerid']]['contacts'][$contact['id']] = $contact;
                }
            }
            $result['data'] = $cdata;
        } else {
            $result['error'] = $customersMdl->errorInfo;
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return array of stored item records
     */
    public function getItems($result)
    {
        $storedItemsMdl = new StoredItemsModel();
        $storedItems    = $storedItemsMdl->get();
        if (is_array($storedItems)) {
            $items = [];
            foreach ($storedItems as $storedItem) {

                $items[$storedItem['id']] = $storedItem;
            }
            $result['data'] = $items;
        } else {
            $result['error'] = $storedItemsMdl->errorInfo;
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return array of POS device records
     */
    public function getPosDevices($result)
    {
        $devMdl  = new DevicesModel();
        $devices = $devMdl->get();
        if (is_array($devices)) {
            $data = [];
            foreach ($devices as $device) {
                $data[$device['id']] =  $device;
            }
            $data[0] = ["id"=> 0, "name"=>"Admin dash", "locationname"=>"Admin dash", "locationid"=>0];
            $result['data'] = $data;
        } else {
            $result['error'] = $devMdl->errorInfo;
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return array of POS location records
     */
    public function getPosLocations($result)
    {
        $locMdl    = new LocationsModel();
        $locations = $locMdl->get();
        if (is_array($locations)) {
            $data = [];
            foreach ($locations as $location) {
                $data[$location['id']] = $location;
            }
            $data[0] = ["id"=> 0, "name"=>"Admin dash"];
            $result['data'] = $data;
        } else {
            $result['error'] = $locMdl->errorInfo;
        }

        return $result;
    }

    /**
     * @param $result
     * @return mixed an array of users without their password hash
     */
    public function getUsers($result){
        $authMdl = new AuthModel();
        $users = $authMdl->get();
        $data = [];
        foreach ($users as $user){
            unset($user['password']);
            $user['permissions']=json_decode($user['permissions']);
            $data[$user['id']] = $user;
        }
        $result['data'] = $data;
        return $result;
    }

    /**
     * If stime & etime are not set, This function returns sales using the provided devices ID, using POS configuration values.
     *
     * @param $result
     * @return mixed
     */
    public function getSales($result){
        if (!isset($this->data->stime) && !isset($this->data->etime)){
            // time not set, retrieving POS records, get config.
            $WposConfig = new WposAdminSettings();
            $config = $WposConfig->getSettingsObject("pos");

            // set the sale range based on the config setting
            $etime = time()*1000;
            $stime = strtotime("-1 ".(isset($config->salerange)?$config->salerange:"week"))*1000;

            // determine which devices transactions to include based on config
            if (isset($this->data->deviceid)){
                switch ($config->saledevice){
                    case "device": break; // no need to do anything, id already set
                    case "all":
                        unset($this->data->deviceid); // unset the device id to get all sales
                        break;
                    case "location":
                        // get location device id array
                        $devMdl = new DevicesModel();
                        $this->data->deviceid = $devMdl->getLocationDeviceIds($this->data->deviceid);
                }
            }
        } else {
            $stime = $this->data->stime;
            $etime = $this->data->etime;
        }

        // Get all transactions within the specified timeframe/devices
        $salesMdl = new SalesModel();
        $dbSales  = $salesMdl->getRangeWithRefunds($stime, $etime, (isset($this->data->deviceid)?$this->data->deviceid:null));

        if (is_array($dbSales)) {
            $sales = [];
            foreach ($dbSales as $sale) {
                $salejson = json_decode($sale['data']);
                $salejson->type = $sale['type'];
                $sales[$sale['ref']] = $salejson;
            }
            $result['data'] = $sales;
        } else if ($dbSales === false) {
            $result['error'] = $salesMdl->errorInfo;
        }

        return $result;
    }


    /**
     * Searches sales for the given reference.
     * @param $searchdata
     * @param $result
     * @return mixed Returns sales that match the specified ref.
     */
    public function searchSales($searchdata, $result)
    {
        $salesMdl = new SalesModel();
        $dbSales  = $salesMdl->get(0, 0, $searchdata->ref, null, null, null, null, true);
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

    /**
     * @param array $result
     *
     * @return array Returns an array of tax objects
     */
    public static function getTaxes($result=[])
    {
        $taxItemsMdl = new TaxItemsModel();
        $taxItemsArr    = $taxItemsMdl->get();

        if (is_array($taxItemsArr)) {
            $taxItems = [];
            foreach ($taxItemsArr as $taxItem) {
                $taxItems[$taxItem['id']] = $taxItem;
            }
            $result['data'] = [];
            $result['data']['items'] = $taxItems;

            $taxRulesMdl = new TaxRulesModel();
            $taxRulesArr   = $taxRulesMdl->get();
            if (is_array($taxRulesArr)) {
                $taxRules = [];
                foreach ($taxRulesArr as $taxRule) {
                    $ruleData = json_decode($taxRule['data']);
                    $ruleData->id = $taxRule['id'];
                    $taxRules[$taxRule['id']] = $ruleData;
                }

                $result['data']['rules'] = $taxRules;
            } else {
                $result['error'] = "Tax data could not be retrieved: ".$taxRulesMdl->errorInfo;
            }
        } else {
            $result['error'] = "Tax data could not be retrieved: ".$taxItemsMdl->errorInfo;
        }

        return $result;
    }



    /**
     * @param $result
     * @return mixed Returns an array of suppliers
     */
    public function getSuppliers($result)
    {
        $suppliersMdl = new SuppliersModel();
        $suppliers    = $suppliersMdl->get();
        if (is_array($suppliers)) {
            $supplierdata = [];
            foreach ($suppliers as $supplier) {
                $supplierdata[$supplier['id']] = $supplier;
            }
            $result['data'] = $supplierdata;
        } else {
            $result['error'] = $suppliersMdl->errorInfo;
        }

        return $result;
    }

    /**
     * @param $result
     * @return mixed Returns an array of categories
     */
    public function getCategories($result)
    {
        $catMdl = new CategoriesModel();
        $categories = $catMdl->get();
        if (is_array($categories)) {
            $catdata = [];
            foreach ($categories as $category) {
                $catdata[$category['id']] = $category;
            }
            $result['data'] = $catdata;
        } else {
            $result['error'] = $catMdl->errorInfo;
        }

        return $result;
    }

    /**
     * @param $result
     * @return mixed Returns an array of stock. Each row is a certain item & location ID.
     */
    public function getStock($result)
    {
        $stockMdl = new StockModel();
        $stocks    = $stockMdl->get();
        if (is_array($stocks)) {
            $stockdata = [];
            foreach ($stocks as $stock) {
                $stockdata[$stock['id']] = $stock;
            }
            $result['data'] = $stockdata;
        } else {
            $result['error'] = $stockMdl->errorInfo;
        }

        return $result;
    }

}

?>