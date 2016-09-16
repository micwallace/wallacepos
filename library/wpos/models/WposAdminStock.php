<?php
/**
 * WposAdminStock is part of Wallace Point of Sale system (WPOS) API
 *
 * WposAdminStock is used to manage stock
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
class WposAdminStock {
    /**
     * @var stdClass provided params
     */
    private $data;
    /**
     * @var StockHistoryModel
     */
    private $histMdl;
    /**
     * @var StockModel
     */
    private $stockMdl;

    /**
     * Decode provided input
     * @param $data
     */
    function __construct($data=null){
        // parse the data and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
        // setup objects
        $this->histMdl = new StockHistoryModel();
        $this->stockMdl = new StockModel();
    }

    /**
     * This function is used by WposPosSale and WposInvoices to decrement/increment sold/voided transaction stock; it does not create a history record
     * @param $storeditemid
     * @param $locationid
     * @param $amount
     * @param bool $decrement
     * @return bool
     */
    public function incrementStockLevel($storeditemid, $locationid, $amount, $decrement = false){
        if ($this->stockMdl->incrementStockLevel($storeditemid, $locationid, $amount, $decrement)!==false){
            return true;
        }
        return false;
    }

    /**
     * Transfer stock to another location
     * @param $result
     * @return mixed
     */
    public function transferStock($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"storeditemid":1, "locationid":1, "newlocationid":1, "amount":">=1"}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if ($this->data->locationid == $this->data->newlocationid){
            $result['error'] = "Cannot transfer stock to the same location, pick a different one.";
            return $result;
        }
        // check if theres enough stock at source location
        if (($stock=$this->stockMdl->get($this->data->storeditemid, $this->data->locationid))===false){
            $result['error'] = "Could not fetch current stock level: ".$this->stockMdl->errorInfo;
            return $result;
        }
        if ($stock[0]['stocklevel']<$this->data->amount){
            $result['error'] = "Not enough stock at the source location, add some first";
            return $result;
        }
        // create history record for removed stock
        if ($this->createStockHistory($this->data->storeditemid, $this->data->locationid, 'Stock Transfer', -$this->data->amount, $this->data->newlocationid, 0)===false){ // stock history created with minus
            $result['error'] = "Could not create stock history record";
            return $result;
        }
        // remove stock amount from current location
        if ($this->incrementStockLevel($this->data->storeditemid, $this->data->locationid, $this->data->amount, true)===false){
            $result['error'] = "Could not decrement stock from current location";
            return $result;
        }
        // create history record for added stockd
        if ($this->createStockHistory($this->data->storeditemid, $this->data->newlocationid, 'Stock Transfer', $this->data->amount, $this->data->locationid, 1)===false){
            $result['error'] = "Could not create stock history record";
            return $result;
        }
        // add stock amount to new location
        if ($this->incrementStockLevel($this->data->storeditemid, $this->data->newlocationid, $this->data->amount, false)===false){
            $result['error'] = "Could not add stock to the new location";
            return $result;
        }

        // Success; log data
        Logger::write("Stock Transfer", "STOCK", json_encode($this->data));

        return $result;
    }

    /**
     * Set the level of stock at a location (stocktake)
     * @param $result
     * @return mixed
     */
    public function setStockLevel($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"storeditemid":1, "locationid":1, "amount":">=1"}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // create history record for added stock
        if ($this->createStockHistory($this->data->storeditemid, $this->data->locationid, 'Stock Added', $this->data->amount)===false){
            $result['error'] = "Could not create stock history record";
            return $result;
        }
        if ($this->stockMdl->setStockLevel($this->data->storeditemid, $this->data->locationid, $this->data->amount)===false){
            $result['error'] = "Could not add stock to the location";
        }

        // Success; log data
        Logger::write("Stock Level Set", "STOCK", json_encode($this->data));

        return $result;
    }

    /**
     * Add stock to a location
     * @param $result
     * @return mixed
     */
    public function addStock($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"storeditemid":1, "locationid":1, "amount":">=1"}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // create history record for added stock
        if ($this->createStockHistory($this->data->storeditemid, $this->data->locationid, 'Stock Added', $this->data->amount)===false){
            $result['error'] = "Could not create stock history record";
            return $result;
        }
        // add stock amount to new location
        if ($this->incrementStockLevel($this->data->storeditemid, $this->data->locationid, $this->data->amount, false)===false){
            $result['error'] = "Could not add stock to the new location";
            return $result;
        }
        // Success; log data
        Logger::write("Stock Added", "STOCK", json_encode($this->data));
        return $result;
    }

    /**
     * Get stock history records for a specified item & location
     * @param $result
     * @return mixed
     */
    public function getStockHistory($result){
        if (($stockHist = $this->histMdl->get($this->data->storeditemid, $this->data->locationid))===false){
            $result['error']="Could not retrieve stock history";
        } else {
            $result['data']= $stockHist;
        }
        return $result;
    }

    /**
     * Create a stock history record for a item & location
     * @param $storeditemid
     * @param $locationid
     * @param $type
     * @param $amount
     * @param $sourceid
     * @param int $direction
     * @return bool
     */
    private function createStockHistory($storeditemid, $locationid, $type, $amount, $sourceid=-1, $direction=0){
        if ($this->histMdl->create($storeditemid, $locationid, $type, $amount, $sourceid, $direction)!==false){
            return true;
        }
        return false;
    }
} 