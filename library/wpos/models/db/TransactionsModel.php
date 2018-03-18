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


class TransactionsModel extends DbConfig
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
     * Get all transaction records belonging to a specific customer
     * @param $id
     * @return array|bool
     */
    public function getByCustomer($id){
        $sql = 'SELECT * FROM sales WHERE custid= :id;';
        $placeholders = [":id"=>$id];
        return $this->select($sql, $placeholders);
    }

    /**
     * Get a single sale object using it's id.
     * @param $id
     * @return array|bool Returns false on failure or an array with a single record on success
     */
    public function getById($id){
        $sql = 'SELECT * FROM sales WHERE id= :id;';
        $placeholders = [":id"=>$id];
        return $this->select($sql, $placeholders);
    }

    /**
     * Get a single sale object using its reference.
     * @param $ref
     * @return array|bool Returns false on failure or an array with a single record on success
     */
    public function getByRef($ref){
        $sql = 'SELECT * FROM sales WHERE';
        $placeholders = [];
        if (is_array($ref)) {
            $ref = array_map([$this->_db, 'quote'], $ref);
            $sql .= " `ref` IN (" . implode(', ', $ref) . ");";
        } else if (is_numeric(str_replace("-", "", $ref))){
            $sql .= " `ref`=:ref;";
            $placeholders[":ref"] = $ref;
        } else {
            return false;
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
    public function getRangeWithRefunds($stime, $etime, $deviceids=null, $status=null, $statparity=true, $includeorders=true){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = 'SELECT s.* FROM sales as s LEFT JOIN sale_voids as v ON s.id=v.saleid WHERE ((s.processdt>= :stime AND s.processdt<= :etime) OR (v.processdt>= :stime AND v.processdt<= :etime))';

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

        // do not total orders for reporting functions
        if ($includeorders==false){
            $sql .= ' AND status!=0';
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Returns sale totals for a given time span
     * @param $stime
     * @param $etime
     * @param null $status
     * @param bool $statparity
     * @param bool $includeorders
     * @param null $ttype
     * @return array|bool Returns false on failure or an array with sales or totals on success
     */
    public function getTotals($stime, $etime, $status=null, $statparity=true, $includeorders=false, $ttype=null){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = "SELECT *, COALESCE(SUM(total), 0) as stotal, COALESCE(SUM(cost), 0) as ctotal, COUNT(id) as snum, COALESCE(GROUP_CONCAT(ref SEPARATOR ','),'') as refs FROM sales WHERE (processdt>= :stime AND processdt<= :etime)";

        if ($status !== null) {
            $sql .= ' AND status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        // do not total orders & invoices for reporting functions
        if ($includeorders==false){
            $sql .= ' AND status!=0';
        }

        if ($ttype!=null){
            $sql .= ' AND type=:type';
            $placeholders[':type'] = $ttype;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * Returns unpaid totals (for accural accounting purposes) for a given time span
     * @param $stime
     * @param $etime
     * @param bool $includeorders
     * @param null $ttype
     * @return array|bool Returns false on failure or an array with sales or totals on success
     */
    public function getUnaccountedTotals($stime, $etime, $includeorders=false, $ttype=null){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = "SELECT *, COALESCE(SUM(s.balance), 0) as stotal, COUNT(s.id) as snum, COALESCE(GROUP_CONCAT(s.ref SEPARATOR ','),'') as refs FROM sales AS s WHERE (s.processdt>= :stime AND s.processdt<= :etime) AND (s.status!=3 AND s.balance!=0)";

        // do not total orders & invoices for reporting functions
        if ($includeorders==false){
            $sql .= ' AND s.status!=0';
        }

        if ($ttype!=null){
            $sql .= ' AND s.type=:type';
            $placeholders[':type'] = $ttype;
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
        $sql = "SELECT *, d.id as groupid, ".($grouptype=='device'?"CONCAT(d.name, ' (', l.name, ')')":'d.name')." as name, SUM(s.total) as stotal, COUNT(s.id) as snum, COALESCE(GROUP_CONCAT(s.ref SEPARATOR ','),'') as refs FROM sales as s LEFT JOIN ".$joinsql." WHERE (s.processdt>= :stime AND s.processdt<= :etime)";

        if ($status !== null) {
            $sql .= ' AND status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        $sql.= $groupsql;

        return $this->select($sql, $placeholders);
    }

    /**
     * Updates the sale time of the associated transation record
     * @param $id
     * @param $saletime
     * @return bool|int Returns false on failure or number of rows affected on success
     */
    public function setSaleTime($id, $saletime){
        $placeholders = [":id"=>$id, ":saletime"=>$saletime];
        $sql = "UPDATE sales SET processdt= :saletime WHERE id= :id";
        return $this->update($sql, $placeholders);
    }

    /**
     * Updates the status for the associated transaction
     * @param $id
     * @param $status
     * @return bool|int Returns false on failure or number of rows affected on success
     */
    public function setSaleStatus($id, $status){
        if (!is_numeric($id)){ return false; }
        $placeholders = [":id"=>$id, ":status"=>$status];
        $sql = "UPDATE sales SET status= :status WHERE id= :id";
        return $this->update($sql, $placeholders);
    }

    /**
     * Removes the transaction record and all associated records
     * @param $saleid
     * @return bool|int Returns false on failure or number of rows affected on success
     */
    public function remove($saleid){
        $sql = "DELETE FROM `sales` WHERE `id` = :saleid";
        $placeholders = [
            ":saleid" => $saleid
        ];
        // Remove associated records
        $saleItemsMdl = new SaleItemsModel();
        $salePaymentsMdl = new SalePaymentsModel();
        $saleVoidMdl = new SaleVoidsModel();
        if (($result = $saleVoidMdl->removeBySale($saleid))!==false)
            if (($result = $salePaymentsMdl->removeBySale($saleid))!==false)
                $result = $saleItemsMdl->removeBySale($saleid);

        if($result!==false){
            return $this->delete($sql, $placeholders);
        } else {
            return $result;
        }
    }

}