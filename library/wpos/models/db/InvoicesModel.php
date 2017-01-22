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


class InvoicesModel extends TransactionsModel
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
     * @param $channel
     * @param $data
     * @param $status
     * @param $userId
     * @param $custId
     * @param $discount
     * @param $total
     * @param $balance
     * @param $processdt
     * @param $duedt
     * @param int $deviceId
     * @param int $locationId
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($ref, $channel, $data, $status, $userId, $custId, $discount, $total, $balance, $processdt, $duedt, $deviceId=0, $locationId=0)
    {
        $sql = "INSERT INTO sales (ref, type, channel, data, userid, deviceid, locationid, custid, discount, total, balance, status, processdt, duedt, dt) VALUES (:ref, 'invoice', :channel, :data, :userid, :deviceid, :locationid, :custid, :discount, :total, :balance, :status, :processdt, :duedt, '".date("Y-m-d H:i:s")."')";
        $placeholders = [
            ':ref'        => $ref,
            ':channel'    => $channel,
            ':data'       => $data,
            ':userid'     => $userId,
            ':deviceid'   => $deviceId,
            ':locationid' => $locationId,
            ':custid'     => $custId,
            ':discount'   => $discount,
            ':total'      => $total,
            ':balance'      => $balance,
            ':status'      => $status,
            ':processdt'      => $processdt,
            ':duedt'      => $duedt
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $ref
     * @param $channel
     * @param $data
     * @param $status
     * @param $userId
     * @param $custId
     * @param $discount
     * @param $total
     * @param $balance
     * @param $processdt
     * @param $duedt
     * @param int $deviceId
     * @param int $locationId
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function import($id, $ref, $channel, $data, $status, $userId, $custId, $discount, $total, $balance, $processdt, $duedt, $deviceId=0, $locationId=0)
    {
        $sql = "INSERT INTO sales (id, ref, type, channel, data, userid, deviceid, locationid, custid, discount, total, balance, status, processdt, duedt, dt) VALUES (:id, :ref, 'invoice', :channel, :data, :userid, :deviceid, :locationid, :custid, :discount, :total, :balance, :status, :processdt, :duedt, '".date("Y-m-d H:i:s", $processdt/1000)."')";
        $placeholders = [
            ':id'        => $id,
            ':ref'        => $ref,
            ':channel'    => $channel,
            ':data'       => $data,
            ':userid'     => $userId,
            ':deviceid'   => $deviceId,
            ':locationid' => $locationId,
            ':custid'     => $custId,
            ':discount'   => $discount,
            ':total'      => $total,
            ':balance'      => $balance,
            ':status'      => $status,
            ':processdt'      => $processdt,
            ':duedt'      => $duedt
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $custId
     * @param int $limit
     * @param int $offset
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function get($custId = null, $ref = null, $searchref = false, $limit = 0, $offset = 0)
    {
        $sql = 'SELECT * FROM sales';
        $placeholders = [];
        if ($custId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' custid = :custid';
            $placeholders[':custid'] = $custId;
        }
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
        // just get sale transactions
        if (empty($placeholders)) {
            $sql .= ' WHERE';
        } else {
            $sql .= ' AND';
        }
        $sql .= " type= 'invoice'";

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

    public function getOpenInvoices() {
        $sql = "SELECT * FROM sales WHERE balance!=0 AND type='invoice'";

        return $this->select($sql, []);
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
    public function getRange($stime, $etime=null, $deviceids=null, $status=null, $statparity=true, $includeorders=true){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
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

        // do not total orders & invoices for reporting functions
        if ($includeorders==false){
            $sql .= ' AND status!=0';
        }

        // just get invoice transactions
        $sql .= " AND type= 'invoice'";

        return $this->select($sql, $placeholders);
    }

    /**
     * @param null $saleid
     * @param null $saleref
     * @param $data
     * @param null $status
     * @param null $discount
     * @param null $total
     * @param null $balance
     * @param null $processdt
     * @param null $duedt
     * @param null $userid
     * @param null $devid
     * @param null $locid
     * @param null $custid
     * @return bool|int Returns false on failure or number of rows affected on success
     */
    public function edit($saleid=null, $saleref=null, $data, $status = null, $discount=null, $cost=null, $total=null, $balance=null, $processdt=null, $duedt=null, $userid=null, $devid=null, $locid=null, $custid=null){
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
        if ($cost !== null) {
            $sql .= ', cost= :cost';
            $placeholders[':cost'] = $cost;
        }
        if ($total !== null) {
            $sql .= ', total= :total';
            $placeholders[':total'] = $total;
        }
        if ($balance !== null) {
            $sql .= ', balance= :balance';
            $placeholders[':balance'] = $balance;
        }
        if ($processdt !== null) {
            $sql .= ', processdt= :processdt';
            $placeholders[':processdt'] = $processdt;
        }
        if ($duedt !== null) {
            $sql .= ', duedt= :duedt';
            $placeholders[':duedt'] = $duedt;
        }

        $placeholders[':data'] = $data;

        return $this->update($sql.$sqlcond, $placeholders);
    }

}