<?php
/**
 * SalesModel is part of Wallace Point of Sale system (WPOS) API
 *
 * SalesModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      Class created 11/23/13 10:35 PM
 */


class SalesModel extends TransactionsModel
{

    protected $_columns = ['id', 'ref', 'type', 'channel', 'data', 'userid', 'deviceid', 'locationid', 'custid', 'discount', 'total', 'status', 'processdt', 'dt'];

    /**
     * Init DB
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param $ref
     * @param $data
     * @param $status
     * @param $userId
     * @param $deviceId
     * @param $locationId
     * @param $custId
     * @param $discount
     * @param $rounding
     * @param $total
     * @param $processdt
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($ref, $data, $status, $userId, $deviceId, $locationId, $custId, $discount, $rounding, $cost, $total, $processdt)
    {
        $sql = "INSERT INTO sales (ref, type, channel, data, userid, deviceid, locationid, custid, discount, rounding, cost, total, status, processdt, dt) VALUES (:ref, 'sale', 'pos', :data, :userid, :deviceid, :locationid, :custid, :discount, :rounding, :cost, :total, :status, :processdt, '".date("Y-m-d H:i:s")."')";
        $placeholders = [
            ':ref'        => $ref,
            ':data'       => $data,
            ':userid'     => $userId,
            ':deviceid'   => $deviceId,
            ':locationid' => $locationId,
            ':custid'     => $custId,
            ':discount'   => $discount,
            ':rounding'   => $rounding,
            ':cost'   => $cost,
            ':total'      => $total,
            ':status'      => $status,
            ':processdt'      => $processdt
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param null $ref
     * @param null $userId
     * @param null $deviceId
     * @param null $locationId
     * @param null $custId
     * @param bool $searchref
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function get($limit = 0, $offset = 0, $ref = null, $userId = null, $deviceId = null, $locationId = null, $custId = null, $searchref = false)
    {
        $sql = 'SELECT * FROM sales';
        $placeholders = [];
        if ($ref !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            if ($searchref){
                $sql .= ' ref LIKE :ref';
                $placeholders[':ref'] = '%'.$ref.'%';
            } else {
                $sql .= ' ref= :ref';
                $placeholders[':ref'] = $ref;
            }

        }
        if ($userId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' userid= :userid';
            $placeholders[':userid'] = $userId;
        }
        if ($deviceId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' deviceid= :deviceid';
            $placeholders[':deviceid'] = $deviceId;
        }
        if ($locationId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' locationid = :locationid';
            $placeholders[':locationid'] = $locationId;
        }
        if ($custId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' custid = :custid';
            $placeholders[':custid'] = $custId;
        }
        // just get sale transactions
        if (empty($placeholders)) {
            $sql .= ' WHERE';
        } else {
            $sql .= ' AND';
        }
        $sql .= " type= 'sale'";

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
     * Returns an array of sales or totals from the specified range
     * @param $stime
     * @param $etime
     * @param null $deviceids
     * @param null $status
     * @param bool $statparity
     * @param bool $includeorders
     * @return array|bool Returns false on failure or an array with sales or totals on success
     */
    public function getRange($stime, $etime, $deviceids=null, $status=null, $statparity= true, $includeorders=true){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = 'SELECT * FROM sales WHERE (processdt>= :stime AND processdt<= :etime)';

        if ($deviceids !== null) {
            if (is_array($deviceids)){
                $deviceids = implode(",", $deviceids);
            }
            $sql .= " AND (INSTR(:deviceid, s.deviceid) OR INSTR(:deviceid, v.deviceid))";
            $placeholders[':deviceid'] = "%".$deviceids."%";
        }

        if ($status !== null) {
            $sql .= ' AND status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        // just get sale transactions
        $sql .= " AND type='sale'";

        // do not total orders & invoices for reporting functions
        if ($includeorders==false){
            $sql .= ' AND status!=0';
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * like the getrange function above but it takes into account the date of voids/refunds
     * @param $stime
     * @param $etime
     * @param null $deviceids
     * @param null $status
     * @param bool $statparity
     * @param bool $includeorders
     * @return array|bool Returns false on failure or an array with sales on success
     */
    public function getRangeWithRefunds($stime, $etime=null, $deviceids=null, $status=null, $statparity=true, $includeorders=true){

        $placeholders = [":stime"=>$stime];
        if ($etime!=null)
            $placeholders[":etime"] = $etime;
        $sql = 'SELECT s.* FROM sales as s LEFT JOIN sale_voids as v ON s.id=v.saleid WHERE ((s.processdt>= :stime'.($etime!=null?' AND s.processdt<= :etime':'').') OR (v.processdt>= :stime'.($etime!==null?' AND v.processdt<= :etime':'').'))';

        if ($deviceids !== null) {
            if (is_array($deviceids)){
                $deviceids = implode(",", $deviceids);
            }
            $sql .= " AND (INSTR(:deviceid, s.deviceid) OR INSTR(:deviceid, v.deviceid))";
            $placeholders[':deviceid'] = "%".$deviceids."%";
        }

        if ($status !== null) {
            $sql .= ' AND status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        // just get sale transactions
        $sql .= " AND type= 'sale'";

        // do not total orders for reporting functions
        if ($includeorders==false){
            $sql .= ' AND status!=0';
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Get an array of totals, grouped by user, device or location
     * @param $stime
     * @param $etime
     * @param null $status
     * @param bool $statparity
     * @param string $grouptype
     * @return array|bool Returns false on failure or an array with totals on success
     */
    public function getGroupedTotals($stime, $etime, $status=null, $statparity= true, $grouptype = "device"){

        $joinsql = "devices as d ON s.deviceid=d.id LEFT JOIN locations as l ON d.locationid=l.id";
        $groupsql = " GROUP BY s.deviceid";

        switch($grouptype){
            case "device":
                break;
            case "location":
                $joinsql = "locations as d ON s.locationid=d.id";
                $groupsql = " GROUP BY s.locationid";
                break;
            case "user":
                $joinsql = "auth as d ON s.userid=d.id";
                $groupsql = " GROUP BY s.userid";
                break;
        }

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = 'SELECT *, d.id as groupid, '.($grouptype=='device'?"CONCAT(d.name, ' (', l.name, ')')":'d.name').' as name, SUM(s.total) as stotal, COUNT(s.id) as snum FROM sales as s LEFT JOIN '.$joinsql.' WHERE (processdt>= :stime AND processdt<= :etime)';

        if ($status !== null) {
            $sql .= ' AND status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        // do not total orders
        $sql .= " AND status!=0";

        $sql.= $groupsql;

        return $this->select($sql, $placeholders);
    }

    /**
     * @param null $saleid
     * @param null $saleref
     * @param $data
     * @param null $status
     * @param null $userid
     * @param null $devid
     * @param null $locid
     * @param null $custid
     * @param null $discount
     * @param null $total
     * @param null $processdt
     * @return bool|int Returns false on failure or number of rows affected on success
     */
    public function edit($saleid=null, $saleref=null, $data, $status = null, $userid=null, $devid=null, $locid=null, $custid=null, $discount=null, $rounding=null, $cost=null, $total=null, $processdt=null){
        if (!is_numeric($saleid) && ($saleref==null || $saleref=="")){ return false; }
        $sql = "UPDATE sales SET data= :data";
        $sqlcond = ""; // conditions to preprend
        $placeholders = [];
        if ($saleid==null && $saleref==null){
            return false; // we would not want that!
        }
        if ($saleref !== null) {
            $sqlcond .= ' WHERE';
            $sqlcond .= ' ref= :ref';
            $placeholders[':ref'] = $saleref;
        } else {
            if (empty($placeholders)){
               $sqlcond .= ' WHERE';
            } else {
               $sqlcond .= ' AND';
            }
            $sqlcond .= ' id= :saleid';
            $placeholders[':saleid'] = $saleid;
        }
        if ($status !== null) {
            $sql .= ', status= :status';
            $placeholders[':status'] = $status;
        }
        if ($userid !== null) {
            $sql .= ', userid= :userid';
            $placeholders[':userid'] = $userid;
        }
        if ($devid !== null) {
            $sql .= ', deviceid= :deviceid';
            $placeholders[':deviceid'] = $devid;
        }
        if ($locid !== null) {
            $sql .= ', locationid= :locationid';
            $placeholders[':locationid'] = $locid;
        }
        if ($custid !== null) {
            $sql .= ', custid= :custid';
            $placeholders[':custid'] = $custid;
        }
        if ($discount !== null) {
            $sql .= ', discount= :discount';
            $placeholders[':discount'] = $discount;
        }
        if ($rounding !== null) {
            $sql .= ', rounding= :rounding';
            $placeholders[':rounding'] = $rounding;
        }
        if ($cost !== null) {
            $sql .= ', cost= :cost';
            $placeholders[':cost'] = $cost;
        }
        if ($total !== null) {
            $sql .= ', total= :total';
            $placeholders[':total'] = $total;
        }
        if ($processdt !== null) {
            $sql .= ', processdt= :processdt';
            $placeholders[':processdt'] = $processdt;
        }

        $placeholders[':data'] = $data;

        return $this->update($sql.$sqlcond, $placeholders);
    }

    /**
     * Removes an order record using it's reference, it will not be removed if it's not an order (status of 0)
     * @param $ref
     * @return bool|int Returns false on failure or number of rows affected on success
     */
    public function removeOrder($ref){
        $sql = "DELETE FROM `sales` WHERE `ref` = :ref AND `status`=0";
        $placeholders = [":ref" => $ref];

        return $this->delete($sql, $placeholders);
    }

}