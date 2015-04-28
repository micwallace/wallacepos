<?php
/**
 * SalePaymentsModel is part of Wallace Point of Sale system (WPOS) API
 *
 * SalePaymentsModel extends the DbConfig PDO class to interact with the config DB table
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

class SalePaymentsModel extends DbConfig
{

    /**
     * @var array of available table columns
     */
    protected $_columns = ['id', 'saleid', 'method', 'amount', 'processdt'];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $saleid
     * @param $method
     * @param $amount
     * @param $processdt
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($saleid, $method, $amount, $processdt)
    {
        $sql = "INSERT INTO sale_payments (saleid, method, amount, processdt) VALUES (:saleid, :method, :amount, :processdt)";
        $placeholders = [
            ':saleid'        => $saleid,
            ':method'       => $method,
            ':amount'     => $amount,
            ':processdt'     => $processdt
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $saleid
     * @param $method
     * @param $amount
     * @param $processdt
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function import($id, $saleid, $method, $amount, $processdt)
    {
        $sql = "INSERT INTO sale_payments (id, saleid, method, amount, processdt) VALUES (:id, :saleid, :method, :amount, :processdt)";
        $placeholders = [
            ':id'        => $id,
            ':saleid'        => $saleid,
            ':method'       => $method,
            ':amount'     => $amount,
            ':processdt'     => $processdt
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param null $saleid
     *
     *
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function get($limit = 0, $offset = 0, $saleid=null)
    {
        $sql = 'SELECT * FROM sale_payments';
        $placeholders = [];

        if ($saleid !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= 'saleid = :saleid';
            $placeholders[':saleid'] = $saleid;
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
     * @param int $itemid
     * @param String $method
     * @param String $amount
     * @param String $processdt
     *
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function edit($itemid, $method, $amount, $processdt){
        $sql = 'UPDATE sale_payments SET method=:method, amount=:amount, processdt=:processdt WHERE id= :id';
        $placeholders = [":id"=>$itemid, ":method"=>$method, ":amount"=>$amount, ":processdt"=>$processdt];

        return $this->update($sql, $placeholders);
    }

    /**
     * Returns a range of sales, optionally providing the total and grouping payment methods.
     * @param $stime
     * @param $etime
     * @param null $deviceid
     * @param null $status
     * @param bool $statparity
     * @return array|bool A range of sales on success, false on failure
     */
    public function getRange($stime, $etime, $deviceid=null, $status=null, $statparity= true){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = 'SELECT s.*, p.method as method FROM sale_payments as p LEFT JOIN sales as s ON p.saleid=s.id WHERE (s.processdt>= :stime AND s.processdt<= :etime)';

        if ($deviceid !== null) {
            $sql .= ' AND s.deviceid= :deviceid';
            $placeholders[':deviceid'] = $deviceid;
        }

        if ($status !== null) {
            $sql .= ' AND s.status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        // do not return orders
        $sql .= ' AND s.status!=0';

        return $this->select($sql, $placeholders);
    }

    /**
     * Returns a range of sales, optionally providing the total and grouping payment methods.
     * @param $stime
     * @param $etime
     * @param null $status
     * @param bool $statparity
     * @param bool $groupmethod
     * @param null $ttype
     * @return array|bool A range of sales on success, false on failure
     */
    public function getTotals($stime, $etime, $status=null, $statparity= true, $groupmethod=false, $ttype=null){

        $placeholders = [":stime"=>$stime, ":etime"=>$etime];
        $sql = "SELECT s.*, p.method as method, COALESCE(SUM(p.amount), 0) as stotal, COUNT(p.id) as snum, COALESCE(GROUP_CONCAT(s.ref SEPARATOR ','),'') as refs FROM sale_payments as p LEFT JOIN sales as s ON p.saleid=s.id WHERE (s.processdt>= :stime AND s.processdt<= :etime)";

        if ($status !== null) {
            $sql .= ' AND s.status'.($statparity?'=':'!=').' :status';
            $placeholders[':status'] = $status;
        }

        // do not return orders
        $sql .= ' AND s.status!=0';

        if ($ttype!=null){
            $sql .= ' AND s.type=:type';
            $placeholders[':type'] = $ttype;
        }

        if ($groupmethod){
            $sql .= ' GROUP BY method';
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param null $saleid
     * @return bool|int Returns false on failure, the number of rows affected on successs
     */
    public function removeBySale($saleid=null){
        if ($saleid===null){
            return false;
        }

        $sql = "DELETE FROM `sale_payments` WHERE `saleid` = :saleid";
        $placeholders = [":saleid" => $saleid];

        return $this->delete($sql, $placeholders);
    }

    /**
     * @param null $id
     * @return bool|int Returns false on failure, the number of rows affected on successs
     */
    public function removeById($id=null){
        if ($id===null){
            return false;
        }

        $sql = "DELETE FROM `sale_payments` WHERE `id` = :id";
        $placeholders = [":id" => $id];

        return $this->delete($sql, $placeholders);
    }

}