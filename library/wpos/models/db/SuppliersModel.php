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
 * @since      File available since 24/05/14 4:24 PM
 */
class SuppliersModel extends DbConfig
{

    /**
     * @var array
     */
    protected $_columns = ['id', 'name', 'dt'];

    /**
     * Init the DB
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $name
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($name)
    {
        $sql          = "INSERT INTO stored_suppliers (`name`, `dt`) VALUES (:name, now());";
        $placeholders = [":name"=>$name];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $Id
     * @return array|bool Returns false on an unexpected failure or an array of selected rows
     */
    public function get($Id = null) {
        $sql = 'SELECT s.*, COUNT(i.id) as numitems FROM stored_suppliers as s LEFT OUTER JOIN stored_items as i ON s.id=i.supplierid';
        $placeholders = [];
        if ($Id !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' s.id =:id';
            $placeholders[':id'] = $Id;
        }
        $sql.=" GROUP BY s.id";

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $name
     * @return bool|int Returns false on an unexpected failure or number of affected rows
     */
    public function edit($id, $name)
    {

        $sql = "UPDATE stored_suppliers SET name= :name WHERE id= :id;";
        $placeholders = [":id"=>$id, ":name"=>$name];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param null $id
     * @return bool|int Returns false on an unexpected failure or number of affected rows
     */
    public function remove($id = null)
    {
        $placeholders = [];
        $sql = "DELETE FROM stored_suppliers WHERE";
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