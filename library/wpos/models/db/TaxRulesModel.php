<?php
/**
 * TaxRulesModel is part of Wallace Point of Sale system (WPOS) API
 *
 * TaxItemsModel extends the DbConfig PDO class to interact with the config DB table.
 * Tax Rules consist of tax components, or "tax_items" which are applied to items.
 * This allows for applying multiple tax rates to an item & applying conditional taxes based on location
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

class TaxRulesModel extends DbConfig
{

    /**
     * @var array
     */
    protected $_columns = ['id', 'data'];

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
        $sql          = "INSERT INTO tax_rules (data) VALUES (:data);";
        $placeholders = [":data"=>json_encode($data)];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $taxId
     * @return array|bool Returns false on an unexpected failure or an array with the selected rows
     */
    public function get($taxId = null)
    {
        $sql          = 'SELECT * FROM tax_rules';
        $placeholders = [];
        if ($taxId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = '.$taxId;
            $placeholders[] = $taxId;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $data
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the operation
     */
    public function edit($id, $data)
    {

        $sql          = "UPDATE tax_rules SET data= :data WHERE id= :id;";
        $placeholders = [":id"=>$id, ":data"=>json_encode($data)];

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
        $sql          = "DELETE FROM tax_rules WHERE id= :id;";
        $placeholders = [":id"=>$id];

        return $this->delete($sql, $placeholders);
    }

}

?>