<?php
/**
 * TaxItemsModel is part of Wallace Point of Sale system (WPOS) API
 *
 * TaxItemsModel extends the DbConfig PDO class to interact with the config DB table.
 * This model is not actively used in the system. Tax rates are "hardcoded into the database".
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

class TaxItemsModel extends DbConfig
{

    /**
     * @var array
     */
    protected $_columns = ['id', 'name', 'value'];

    /**
     * Init DB
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $name
     * @param $value
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($name, $value)
    {
        $sql          = "INSERT INTO tax_items (name, value) VALUES (:name, :value);";
        $placeholders = [":name"=>$name, ":value"=>$value];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $taxId
     * @param null $name
     * @return array|bool Returns false on an unexpected failure or an array with the selected rows
     */
    public function get($taxId = null, $name = null)
    {
        $sql          = 'SELECT * FROM tax_items';
        $placeholders = [];
        if ($taxId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = '.$taxId;
            $placeholders[] = $taxId;
        }
        if ($name !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' name = '.$name;
            $placeholders[] = $name;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $name
     * @param $value
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the operation
     */
    public function edit($id, $name, $value)
    {

        $sql          = "UPDATE tax_items SET name= :name, value= :value WHERE id= :id;";
        $placeholders = [":id"=>$id, ":name"=>$name, ":value"=>$value];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param $id
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the operation
     */
    public function remove($id)
    {
        if ($id === null) {
            return false;
        }
        $sql          = "DELETE FROM tax_items WHERE id= :id;";
        $placeholders = [":id"=>$id];

        return $this->delete($sql, $placeholders);
    }

}

?>