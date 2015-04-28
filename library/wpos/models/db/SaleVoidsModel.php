<?php
/**
 * SaleVoidsModel is part of Wallace Point of Sale system (WPOS) API
 *
 * SaleVoidsModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      File available since 26/12/13 16:15 PM
 */

class SaleVoidsModel extends DbConfig
{

    /**
     * @var array of available columns
     */
    protected $_columns = ['id', 'saleid', 'userid', 'deviceid', 'locationid', 'reason', 'method', 'amount', 'items', 'void', 'processdt', 'dt'];

    /**
     * Ini the DB
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param $saleid
     * @param $userid
     * @param $deviceid
     * @param $locationid
     * @param $reason
     * @param null $method
     * @param null $amount
     * @param null $items
     * @param $void
     * @param $processdt
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($saleid, $userid, $deviceid, $locationid, $reason, $method=null, $amount=null, $items=null, $void, $processdt)
    {
        $sql = "INSERT INTO `sale_voids` (`saleid`, `userid`, `deviceid`, `locationid`, `reason`, `method`, `amount`, `items`, `void`, `processdt`, `dt`) VALUES (:saleid, :userid, :deviceid, :locationid, :reason, :method, :amount, :items, :isvoid, :processdt, now())";
        $placeholders = [
            ':saleid'=>$saleid,
            ':userid'=>$userid,
            ':deviceid'=>$deviceid,
            ':locationid'=>$locationid,
            ':reason'=>$reason,
            ':method'=>$method,
            ':amount'=>$amount,
            ':items'=>$items,
            ':isvoid'=>$void,
            ':processdt'=>$processdt
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param null $saleid
     * @param null $processdt
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function get($limit = 0, $offset = 0, $saleid=null, $processdt=null)
    {
        $sql = 'SELECT * FROM sale_voids';
        $placeholders = [];

        if ($saleid !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' saleid = :saleid';
            $placeholders[':saleid'] = $saleid;
        }
        if ($processdt !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' processdt = :processdt';
            $placeholders[':processdt'] = $processdt;
        }
        if ($limit !== 0 && is_int($limit)) {
            $sql .= ' LIMIT :limit';
            $placeholders[':limit'] = $limit;
        }
        if ($offset !== 0 && is_int($offset)) {
            $sql .= ' OFFSET :offset';
            $placeholders[':offset'] = $offset;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Get a range of refund records, optionally returning refunded totals or 'method grouped' refund totals
     * @param $stime
     * @param $etime
     * @param null $deviceid
     * @param null $isvoid
     * @param bool $gettotal
     * @param bool $groupmethod
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function getRange($stime, $etime, $deviceid=null, $isvoid=null, $gettotal = false, $groupmethod = false){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = 'SELECT *'.($gettotal?', COALESCE(SUM(amount), 0) as stotal, COUNT(id) as snum':'').' FROM sale_voids WHERE (processdt>= :stime AND processdt<= :etime)';

        if ($deviceid !== null) {
            $sql .= ' AND deviceid= :deviceid';
            $placeholders[':deviceid'] = $deviceid;
        }

        if ($isvoid !== null) {
            $sql .= ' AND void= :isvoid';
            $placeholders[':isvoid'] = ($isvoid?1:0);
        }

        if ($gettotal && $groupmethod){
            $sql .= ' GROUP BY method';
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Get a range of refund records, optionally returning refunded totals or 'method grouped' refund totals
     * @param $stime
     * @param $etime
     * @param null $isvoid
     * @param bool $groupmethod
     * @param null $ttype
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function getTotals($stime, $etime, $isvoid=null, $groupmethod = false, $ttype=null){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = "SELECT *, COALESCE(SUM(v.amount), 0) as stotal, COUNT(v.id) as snum, COALESCE(GROUP_CONCAT(s.ref SEPARATOR ','),'') as refs FROM sale_voids as v LEFT JOIN sales as s ON v.saleid=s.id WHERE (v.processdt>= :stime AND v.processdt<= :etime)";

        if ($isvoid !== null) {
            $sql .= ' AND v.void= :isvoid';
            $placeholders[':isvoid'] = ($isvoid?1:0);
        }

        if ($ttype!=null){
            $sql .= ' AND s.type=:type';
            $placeholders[':type'] = $ttype;
        }

        if ($groupmethod){
            $sql .= ' GROUP BY v.method';
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Get a range of refund totals, grouped by user, device or location
     * @param $stime
     * @param $etime
     * @param null $isvoid
     * @param string $grouptype
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function getGroupedTotals($stime, $etime, $isvoid = null, $grouptype='device'){

        $joinsql = "devices as d ON v.deviceid=d.id LEFT JOIN locations as l ON d.locationid=l.id";
        $groupsql = " GROUP BY v.deviceid";

        switch($grouptype){
            case "device":
                break;
            case "location":
                $joinsql = "locations as d ON v.locationid=d.id";
                $groupsql = " GROUP BY v.locationid";
                break;
            case "user":
                $joinsql = "auth as d ON v.userid=d.id";
                $groupsql = " GROUP BY v.userid";
                break;
        }

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = "SELECT *, d.id as groupid, ".($grouptype=='device'?"CONCAT(d.name, ' (', l.name, ')')":'d.name')." as name, SUM(v.amount) as stotal, COUNT(v.id) as snum, GROUP_CONCAT(s.ref SEPARATOR ',') as refs FROM sale_voids as v LEFT JOIN sales as s ON v.saleid=s.id LEFT JOIN ".$joinsql." WHERE (v.processdt>= :stime AND v.processdt<= :etime)";

        if ($isvoid !== null) {
            $sql .= ' AND v.void= :isvoid';
            $placeholders[':isvoid'] = ($isvoid?1:0);
        }

        $sql.= $groupsql;

        return $this->select($sql, $placeholders);
    }

    /**
     * Returns true if a record exists with the specified sale id and processdt
     * @param null $saleid
     * @param null $processdt
     * @return bool
     */
    public function recordExists($saleid=null, $processdt=null){
        $records = $this->get(0,0,$saleid,$processdt);
        if (sizeof($records)>0){
            return true;
        }
        return false;
    }

    /**
     * Removes all void records from a corresponding sale, optionally providing processdt to remove a single record.
     * @param null $saleid
     * @param null $processdt
     * @return bool|int
     */
    public function removeBySale($saleid=null, $processdt=null){
        if ($saleid===null){
            return false;
        }
        $sql = "DELETE FROM `sale_voids` WHERE `saleid` = :saleid";
        $placeholders = [":saleid" => $saleid];

        // since we don't have a unique id in the json data we can use a combination of timestamp and saleid to delete a single record
        if ($processdt !== null) {
            $sql .= ' AND processdt = :processdt';
            $placeholders[':processdt'] = $processdt;
        }

        return $this->delete($sql, $placeholders);
    }

}