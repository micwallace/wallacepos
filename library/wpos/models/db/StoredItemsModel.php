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
    protected $_columns = ['id' ,'data', 'supplierid', 'categoryid', 'code', 'name', 'price'];

    /**
     * Init DB
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $data
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($data)
    {
        $sql          = "INSERT INTO stored_items (`data`, `supplierid`, `categoryid`, `code`, `name`, `price`) VALUES (:data, :supplierid, :categoryid, :code, :name, :price);";
        $placeholders = [":data"=>json_encode($data),":supplierid"=>$data->supplierid, ":categoryid"=>$data->categoryid, ":code"=>$data->code, ":name"=>$data->name, ":price"=>$data->price];

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

        $items = $this->select($sql, $placeholders);
        if ($items===false)
            return false;

        foreach($items as $key=>$item){
            $data = json_decode($item['data'], true);
            $data['id'] = $item['id'];
            $items[$key] = $data;
        }

        return $items;
    }

    /**
     * @param $id
     * @param $data
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function edit($id, $data){

        $sql = "UPDATE stored_items SET data= :data, supplierid= :supplierid, categoryid= :categoryid, code= :code, name= :name, price= :price WHERE id= :id;";
        $placeholders = [":id"=>$id, ":data"=>json_encode($data), ":supplierid"=>$data->supplierid, ":categoryid"=>$data->categoryid, ":code"=>$data->code, ":name"=>$data->name, ":price"=>$data->price];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param integer|array $id
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the delete operation
     */
    public function remove($id){

        $placeholders = [];
        $sql = "DELETE FROM stored_items WHERE";
        if (is_numeric($id)){
            $sql .= " `id`=:id;";
            $placeholders[":id"] = $id;
        } else if (is_array($id)) {
            $id = array_map([$this->_db, 'quote'], $id);
            $sql .= " `id` IN (" . implode(', ', $id) . ");";
        } else {
            return false;
        }

        return $this->delete($sql, $placeholders);
    }

}