<?php
/**
 * SaleItemsModel is part of Wallace Point of Sale system (WPOS) API
 *
 * SaleItemsModel extends the DbConfig PDO class to interact with the config DB table
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


class SaleItemsModel extends DbConfig
{

    /**
     * @var array of available columns
     */
    protected $_columns = ['id', 'saleid', 'storeditemid', 'saleitemid', 'qty', 'name', 'description', 'taxid', 'unit', 'price', 'refundqty'];

    /**
     * Init the PDO object
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param $saleid
     * @param $sitemid
     * @param $saleitemid
     * @param $qty
     * @param $name
     * @param $desc
     * @param $taxid
     * @param $tax
     * @param $unit
     * @param $price
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($saleid, $sitemid, $saleitemid, $qty, $name, $desc, $taxid, $tax, $cost, $unit, $price, $unit_original=0)
    {
        $sql = "INSERT INTO sale_items (saleid, storeditemid, saleitemid, qty, name, description, taxid, tax, tax_incl, tax_total, cost, unit_original, unit, price, refundqty) VALUES (:saleid, :sitemid, :saleitemid, :qty, :name, :description, :taxid, :tax, :tax_incl, :tax_total, :cost, :unit_original, :unit, :price, 0)";
        $placeholders = [
            ':saleid'       => $saleid,
            ':sitemid'      => $sitemid,
            ':saleitemid'   => $saleitemid,
            ':qty'          => $qty,
            ':name'         => $name,
            ':description'  => $desc,
            ':taxid'        => $taxid,
            ':tax'          => json_encode($tax),
            ':tax_incl'     => $tax->inclusive ? 1 : 0,
            ':tax_total'    => $tax->total,
            ':cost'         => $cost,
            ':unit_original'=> $unit_original,
            ':unit'         => $unit,
            ':price'        => $price
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $saleid
     * @param $sitemid
     * @param $saleitemid
     * @param $qty
     * @param $name
     * @param $desc
     * @param $taxid
     * @param $tax
     * @param $unit
     * @param $price
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function import($id, $saleid, $sitemid, $saleitemid, $qty, $name, $desc, $taxid, $tax, $cost, $unit, $price)
    {
        $sql = "INSERT INTO sale_items (id, saleid, storeditemid, saleitemid, qty, name, description, taxid, tax, cost, unit, price, refundqty) VALUES (:id, :saleid, :sitemid, :saleitemid, :qty, :name, :description, :taxid, :tax, :cost, :unit, :price, 0)";
        $placeholders = [
            ':id'        => $id,
            ':saleid'        => $saleid,
            ':sitemid'       => $sitemid,
            ':saleitemid'   => $saleitemid,
            ':qty'     => $qty,
            ':name'     => $name,
            ':description'   => $desc,
            ':taxid' => $taxid,
            ':cost' => $cost,
            ':tax' => $tax,
            ':unit'     => $unit,
            ':price'   => $price
        ];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param null $saleid
     * @param null $sitemid
     *
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function get($limit = 0, $offset = 0, $saleid=null, $sitemid=null)
    {
        $sql = 'SELECT * FROM sale_items';
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
        if ($sitemid !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= 'storeditemid = :sitemid';
            $placeholders['sitemid'] = $sitemid;
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
     * @param null $sitemid
     * @param int|null $saleitemid
     * @param null $qty
     * @param null $name
     * @param null $desc
     * @param $taxid
     * @param $tax
     * @param $unit
     * @param $price
     * @return array|bool Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function edit($itemid, $sitemid, $saleitemid=0, $qty, $name, $desc, $taxid, $tax, $cost, $unit, $price){
        $sql = 'UPDATE sale_items SET storeditemid=:sitemid, saleitemid=:saleitemid, qty=:qty, name=:name, description=:desc, taxid=:taxid, tax=:tax, cost=:cost, unit=:unit, price=:price WHERE id= :id';
        $placeholders = [":id"=>$itemid, ":sitemid"=>$sitemid, ":saleitemid"=>$saleitemid, ":qty"=>$qty, ":name"=>$name, ":desc"=>$desc, ":taxid"=>$taxid, ":tax"=>json_encode($tax), ":cost"=>$cost, ":unit"=>$unit, ":price"=>$price];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param $stime
     * @param $etime
     * @param bool $group (1 to group by category, 2 to group by supplier)
     * @param bool $novoids
     * @param null $ttype
     * @return array|bool Returns an array of stored items and their totals for a corresponding period, items that are not stored are added into the Misc group (ie id=0). Returns false on failure
     */
    public function getStoredItemTotals($stime, $etime, $group = 0, $novoids = true, $ttype=null){

        if ($group==2){
            $groupcol = "supplierid";
            $grouptable = "stored_suppliers";
        } else {
            $groupcol = "categoryid";
            $grouptable = "stored_categories";
        }

        $sql = "SELECT ".($group>0?'si.'.$groupcol.' AS groupid, p.name AS name':'i.storeditemid AS groupid, i.name AS name').", COALESCE(SUM(i.qty), 0) AS itemnum, COALESCE(SUM(i.price-(i.price*(s.discount/100))), 0) AS itemtotal, COALESCE(SUM((i.price*(s.discount/100))), 0) AS discounttotal, COALESCE(SUM(i.tax_total-(i.tax_total*(s.discount/100))), 0) AS taxtotal, COALESCE(SUM(i.refundqty), 0) AS refnum, COALESCE(SUM(i.unit*i.refundqty), 0) AS reftotal, COALESCE(GROUP_CONCAT(DISTINCT s.ref SEPARATOR ','),'') as refs";
        $sql.= ' FROM sale_items AS i LEFT JOIN sales AS s ON i.saleid=s.id'.($group>0 ? ' LEFT JOIN stored_items AS si ON i.storeditemid=si.id LEFT JOIN '.$grouptable.' AS p ON si.'.$groupcol.'=p.id' : '').' WHERE (s.processdt>= :stime AND s.processdt<= :etime) '.($novoids?'AND s.status!=3':'');
        $placeholders = [":stime"=>$stime, ":etime"=>$etime];

        if ($ttype!=null){
            $sql .= ' AND s.type=:type';
            $placeholders[':type'] = $ttype;
        }

        $sql.= ' GROUP BY groupid, name';

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $stime
     * @param $etime
     * @param bool $novoids
     * @param null $ttype
     * @return array|bool Returns a range of sale items with their totals. returns false on failure
     */
    public function getTotalsRange($stime, $etime, $novoids = true, $ttype=null){

        $sql = "SELECT i.*, COALESCE(i.price-(i.price*(s.discount/100)), 0) AS itemtotal, COALESCE((i.price*(s.discount/100)/i.qty)*i.refundqty, 0) AS refundtotal, s.ref as ref, s.discount as discount FROM sale_items AS i LEFT JOIN sales AS s ON i.saleid=s.id WHERE (s.processdt>= :stime AND s.processdt<= :etime) ".($novoids?'AND s.status!=3':'');
        $placeholders = [":stime"=>$stime, ":etime"=>$etime];

        if ($ttype!=null){
            $sql .= ' AND s.type=:type';
            $placeholders[':type'] = $ttype;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $stime
     * @param $etime
     * @return array|bool Returns a single row array with to total amount of cash rounding and the qty of sales rounding was applied to
     */
    public function getRoundingTotal($stime, $etime){
        $sql = "SELECT COALESCE(SUM(rounding), 0) as total, COALESCE(COUNT(id), 0) as num, COALESCE(GROUP_CONCAT(ref SEPARATOR ','),'') as refs FROM sales WHERE (processdt>= :stime AND processdt<= :etime) AND rounding!=0";
        $placeholders = [":stime"=>$stime, ":etime"=>$etime];

        return $this->select($sql, $placeholders);
    }

    /**
     * Increment the number of sale items returned, used by refunds and retraction actions.
     * @param $saleid
     * @param $saleitemid
     * @param $qtyrefunded
     * @param bool $add
     * @return bool|int Returns false on failure, number of rows affected on success.
     */
    public function incrementQtyRefunded($saleid, $saleitemid, $qtyrefunded, $add = true){
        $sql = 'UPDATE `sale_items` SET `refundqty`= `refundqty`'.($add==true?'+':'-').':qtyrefunded WHERE `saleid` = :saleid AND `saleitemid` = :saleitemid';
        $placeholders = [':saleid'=>$saleid, ':saleitemid'=>$saleitemid, ':qtyrefunded'=>$qtyrefunded];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param null $saleid
     * @return bool|int Returns false on failure, number of rows affected on success.
     */
    public function removeBySale($saleid=null){
        if ($saleid===null){
            return false;
        }
        $sql = "DELETE FROM `sale_items` WHERE `saleid` = :saleid";
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

        $sql = "DELETE FROM `sale_items` WHERE `id` = :id";
        $placeholders = [":id" => $id];

        return $this->delete($sql, $placeholders);
    }

}