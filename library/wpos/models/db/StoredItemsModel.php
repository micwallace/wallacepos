<?php
/**
 * StoredItemsModel is part of Wallace Point of Sale system (WPOS) API
 *
 * StoredItemsModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      File available since 11/23/13 10:36 PM
 */

class StoredItemsModel extends DbConfig
{

    /**
     * @var array available columns
     */
    protected $_columns = ['supplierid', 'code', 'qty', 'name', 'description', 'taxid', 'price'];

    /**
     * Init DB
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $supplierid
     * @param $code
     * @param $qty
     * @param $name
     * @param $desc
     * @param $taxid
     * @param $price
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($supplierid, $code, $qty, $name, $desc, $taxid, $price)
    {
        $sql          = "INSERT INTO stored_items (`supplierid`, `code`, `qty`, `name`, `description`, `taxid`, `price`) VALUES (:supplierid, :code, :qty, :name, :desc, :taxid, :price);";
        $placeholders = [":supplierid"=>$supplierid, ":code"=>$code, ":qty"=>$qty, ":name"=>$name, ":desc"=>$desc, ":taxid"=>$taxid, ":price"=>$price];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $Id
     * @param null $code
     * @return array|bool Returns false on an unexpected failure or an array of selected rows
     */
    public function get($Id = null, $code = null) {
        $sql = 'SELECT * FROM stored_items';
        $placeholders = [];
        if ($Id !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = :id';
            $placeholders[':id'] = $Id;
        }
        if ($code !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' code = :code';
            $placeholders[':code'] = $code;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $supplierid
     * @param $code
     * @param $qty
     * @param $name
     * @param $desc
     * @param $taxid
     * @param $price
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function edit($id, $supplierid, $code, $qty, $name, $desc, $taxid, $price)
    {

        $sql = "UPDATE stored_items SET supplierid= :supplierid, code= :code, qty= :qty, name= :name, description= :desc, taxid= :taxid, price= :price WHERE id= :id;";
        $placeholders = [":id"=>$id, ":supplierid"=>$supplierid, ":code"=>$code, ":qty"=>$qty, ":name"=>$name, ":desc"=>$desc, ":taxid"=>$taxid, ":price"=>$price];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param null $id
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the delete operation
     */
    public function remove($id = null)
    {
        if ($id === null) {
            return false;
        }
        $sql          = "DELETE FROM stored_items WHERE `id`=:id;";
        $placeholders = [":id"=>$id];

        return $this->delete($sql, $placeholders);

    }

}