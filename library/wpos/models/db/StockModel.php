<?php
/**
 * StockModel is part of Wallace Point of Sale system (WPOS) API
 *
 * StockModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      File available since 24/05/14 4:13 PM
 */
class StockModel extends DbConfig
{

    /**
     * @var array
     */
    protected $_columns = ['id', 'storeditemid', 'locationid', 'stocklevel', 'dt'];

    /**
     * Init the DB
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param $storeditemid
     * @param $locationid
     * @param $stocklevel
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($storeditemid, $locationid, $stocklevel)
    {
        $sql          = "INSERT INTO stock_levels (`storeditemid`, `locationid`, `stocklevel`, `dt`) VALUES (:storeditemid, :locationid, :stocklevel, now());";
        $placeholders = [":storeditemid"=>$storeditemid, ":locationid"=>$locationid, ":stocklevel"=>$stocklevel];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param $storeditemid
     * @param $locationid
     * @param $stocklevel
     * @return bool|int|string Returns false on failure, number of rows affected or a newly inserted id.
     */
    public function setStockLevel($storeditemid, $locationid, $stocklevel){

        $sql = "UPDATE stock_levels SET `stocklevel`=:stocklevel WHERE `storeditemid`=:storeditemid AND `locationid`=:locationid";
        $placeholders = [":storeditemid"=>$storeditemid, ":locationid"=>$locationid, ":stocklevel"=>$stocklevel];
        $result=$this->update($sql, $placeholders);
        if ($result>0) // if row has been updated, return
            return $result;

        if ($result===false) // if error occured return
            return false;

        // Otherwise add a new stock record, none exists
        return $this->create($storeditemid, $locationid, $stocklevel);
    }

    /**
     * @param $storeditemid
     * @param $locationid
     * @param $amount
     * @param bool $decrement
     * @return bool|int|string Returns false on failure, number of rows affected or a newly inserted id.
     */
    public function incrementStockLevel($storeditemid, $locationid, $amount, $decrement = false){
        $sql = "UPDATE stock_levels SET `stocklevel`= (`stocklevel` ".($decrement==true?'-':'+')." :stocklevel) WHERE `storeditemid`=:storeditemid AND `locationid`=:locationid";
        $placeholders = [":storeditemid"=>$storeditemid, ":locationid"=>$locationid, ":stocklevel"=>$amount];

        $result = $this->update($sql, $placeholders);
        if ($result>0) return $result;

        if ($result===false) return false;

        if ($decrement===false){ // if adding stock and no record exists, create it
            return $this->create($storeditemid, $locationid, $amount);
        }

        return true;
    }

    /**
     * Returns an array of stock records, optionally including special reporting values
     * @param null $storeditemid
     * @param null $locationid
     * @param bool $report
     * @return array|bool Returns false on failure, or an array of stock records
     */
    public function get($storeditemid= null, $locationid= null, $report=false){

        $sql = 'SELECT s.*, i.name AS name, COALESCE(p.name, "Misc") AS supplier'.($report?', l.name AS location, i.price*s.stocklevel as stockvalue':'').' FROM stock_levels as s LEFT JOIN stored_items as i ON s.storeditemid=i.id LEFT JOIN stored_suppliers as p ON i.supplierid=p.id'.($report?' LEFT JOIN locations as l ON s.locationid=l.id':'');
        $placeholders = [];
        if ($storeditemid !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' s.storeditemid = :storeditemid';
            $placeholders[':storeditemid'] = $storeditemid;
        }
        if ($locationid !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' s.locationid = :locationid';
            $placeholders[':locationid'] = $locationid;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Remove stock record by item id.
     * @param $itemid
     * @return bool|int Returns false on failure, or number of records deleted
     */
    public function removeByItemId($itemid){
        if ($itemid === null) {
            return false;
        }
        $sql          = "DELETE FROM stock_levels WHERE itemid=:itemid;";
        $placeholders = [":itemid"=>$itemid];

        return $this->delete($sql, $placeholders);
    }

    /**
     * Remove stock record by location id.
     * @param $locationid
     * @return bool|int Returns false on failure, or number of records deleted
     */
    public function removeByLocationId($locationid){
        if ($locationid === null) {
            return false;
        }
        $sql          = "DELETE FROM stock_levels WHERE locationid=:locationid;";
        $placeholders = [":locationid"=>$locationid];

        return $this->delete($sql, $placeholders);
    }

}