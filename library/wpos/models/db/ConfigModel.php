<?php
/**
 * ConfigModel is part of Wallace Point of Sale system (WPOS) API
 *
 * ConfigModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      Class created 28/04/14 2:28 PM
 */
class ConfigModel extends DbConfig
{

    protected $_columns = ['id', 'name', 'data'];

    public function __construct(){
        parent::__construct();
    }

    /**
     * @param string $name
     * @param string $data
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($name, $data)
{
    $sql = "INSERT INTO `config` (`name`, `data`) VALUES (:name, :data)";
    $placeholders = [":name"=>$name, ":data"=>$data];

    return $this->insert($sql, $placeholders);
}

    /**
     * @param null $name config entry name
     *
     * @return array|bool Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function get($name = null)
{
    $sql = 'SELECT * FROM `config`';
    $placeholders = [];
    if ($name !== null) {
        if (empty($placeholders)) {
            $sql .= ' WHERE';
        }
        $sql .= ' name= :name';
        $placeholders[':name'] = $name;
    }

    return $this->select($sql, $placeholders);
}

    /**
     * @param $name
     * @param $data
     *
     * @return array|bool  Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function edit($name, $data){

        $sql = 'UPDATE `config` SET `data`= :data WHERE `name`= :name';
        $placeholders = [":name"=>$name, ":data"=>$data];

        return $this->update($sql, $placeholders);

    }
}