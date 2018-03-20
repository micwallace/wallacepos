<?php
/**
 * LocationsModel is part of Wallace Point of Sale system (WPOS) API
 *
 * LocationsModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      File available since 14/12/13 07:46 PM
 */


class LocationsModel extends DbConfig
{

    /**
     * @var array of available DB fields
     */
    protected $_columns = ['id', 'name'];

    /**
     *  Init the _db PDO object.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $name
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($name)
    {
        $sql = "INSERT INTO locations (name) VALUES (:name)";
        $placeholders = ['name'=>$name];

        return $this->insert($sql, $placeholders);
    }


    /**
     * @param null $locationId
     * @param null $disabled
     * @param int $limit
     * @param int $offset
     * @return array|bool Returns false on an unexpected failure, an array of locations on success.
     */
    public function get($locationId = null, $disabled = null, $limit = 0, $offset = 0)
    {
        $sql = 'SELECT * FROM locations';
        $placeholders = [];
        if ($locationId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = :locationid';
            $placeholders[':locationid'] = $locationId;
        }
        if ($disabled !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' disabled = :disabled';
            $placeholders[':disabled'] = $disabled?1:0;
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
     * @param $locationId
     * @param $name
     * @return bool|int Returns false on an unexpected failure, number of affected Ids on success.
     */
    public function edit($locationId, $name)
    {
        $sql = "UPDATE locations SET name= :name WHERE id= :id";
        $placeholders = ['id'=>$locationId,'name'=>$name];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param $locationId
     * @param bool $disabled
     * @return bool|int Returns false on an unexpected failure, number of affected Ids on success.
     */
    public function setDisabled($locationId, $disabled = true){
        $sql = "UPDATE locations SET disabled= :disabled WHERE id= :id";
        $placeholders = ['id'=>$locationId, ':disabled'=>($disabled===true?1:0)];

        return $this->update($sql, $placeholders);
    }


    /**
     * @param null $locationId
     * @return bool|int Returns false on an unexpected failure, number of affected Ids on success.
     */
    public function remove($locationId = null)
    {
        // function should not remove the location if devices are currently attached.
        if ($locationId === null) {
            return false;
        }
        $sql = "DELETE FROM locations WHERE id= :id";
        $placeholders = ['id'=>$locationId];
        return $this->delete($sql, $placeholders);
    }

}