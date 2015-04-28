<?php
/**
 * DevicesModel is part of Wallace Point of Sale system (WPOS) API
 *
 * DevicesModel extends the DbConfig PDO class to interact with the config DB table
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

class DevicesModel extends DbConfig
{

    /**
     * @var array
     */
    protected $_columns = ['id', 'name', 'locationid'];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $name
     * @param string $locationid
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($name, $locationid)
    {
        $sql = "INSERT INTO devices (name, locationid) VALUES (:name, :locationid)";
        $placeholders = [':name'=>$name, ":locationid"=>$locationid];

        return $this->insert($sql, $placeholders);
    }

    /**
     * Binds a new UUID to a device
     * @param $uuid
     * @param $deviceId
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function addUuid($uuid, $deviceId){

        $sql = "INSERT INTO device_map (deviceid, uuid) VALUES (:deviceid, :uuid)";
        $placeholders = ['uuid'=>$uuid, 'deviceid'=>$deviceId];

        return $this->insert($sql, $placeholders);
    }


    /**
     * @param null $deviceId
     * @param null $locationId
     * @param null $disabled
     * @param int $limit
     * @param int $offset
     * @return array|bool Returns false on an unexpected failure, returns an array of devices on success
     */
    public function get($deviceId = null, $locationId = null, $disabled = null, $limit = 0, $offset = 0)
    {
        $sql = 'SELECT d.*, l.name as locationname FROM devices as d LEFT JOIN locations as l on d.locationid=l.id';
        $placeholders = [];
        if ($deviceId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' d.id= :deviceid';
            $placeholders[':deviceid'] = $deviceId;
        }
        if ($locationId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' d.locationid= :locationid';
            $placeholders[':locationid'] = $locationId;
        }
        if ($disabled !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' d.disabled= :disabled';
            $placeholders[':disabled'] = $disabled;
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
     * @return array Returns all current device Ids as an array, returns an empty array on failure.
     */
    public function getDeviceIds(){
        $devarr = [];
        $devices = $this->get();
        foreach ($devices as $dev){
            array_push($devarr, $dev['id']);
        }
        return $devarr;
    }

    /**
     * Get a current locations devices using a locationid or a member device Id.
     * @param $deviceid
     * @param null $locationid
     * @return array|bool Returns all current device associated with a location id, returns an empty array on failure.
     */
    public function getLocationDeviceIds($deviceid, $locationid = null) {
        $devarr = [];
        if ($locationid === null){
            $device = $this->get($deviceid);
            if (!is_array($device)){
                return false;
            }
            $locationid = $device[0]['locationid'];
        }

        $locdev = $this->get(null, $locationid); // get location devices
        if (!is_array($locdev)){
            return false;
        }

        foreach ($locdev as $dev){
            if ($dev['disabled']==0){ // no disabled devices included
                array_push($devarr, $dev['id']);
            }
        }
        return $devarr;
    }

    /**
     * @param $uuid
     * @return array|bool Returns false on an unexpected failure, returns an array of uuid records on success
     */
    public function getUuidInfo($uuid){
        $sql = 'SELECT m.uuid as uuid, d.id as deviceid, d.name as devicename, l.id as locationid, l.name as locationname, d.disabled as disabled FROM device_map as m LEFT JOIN devices as d ON m.deviceid=d.id LEFT JOIN locations as l ON d.locationid=l.id WHERE m.uuid= :uuid';
        $placeholders = [];
        $placeholders[':uuid']=$uuid;

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $deviceId
     * @param bool $disabled
     * @return bool|int Returns false on an unexpected failure, number of rows affected on success
     */
    public function setDisabled($deviceId, $disabled = true){
        $sql = "UPDATE devices SET disabled= :disabled WHERE id= :id";
        $placeholders = ['id'=>$deviceId, ':disabled'=>($disabled===true?1:0)];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $name
     * @param $locationid
     * @return bool|int Returns false on an unexpected failure, number of rows affected on success
     */
    public function edit($id, $name, $locationid)
    {
        $sql = "UPDATE devices SET name= :name, locationid= :locationid WHERE id= :id";
        $placeholders = ['id'=>$id, ':name'=>$name, ':locationid'=>$locationid];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $locationid
     * @return bool|int Returns false on an unexpected failure, number of rows affected on success
     */
    public function updateLocation($id, $locationid)
    {
        $sql = "UPDATE devices SET locationid= :locationid WHERE id= :id";
        $placeholders = ['id'=>$id, ':locationid'=>$locationid];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param null $id
     * @return bool|int Returns false on an unexpected failure, number of rows affected on success
     */
    public function remove($id = null)
    {
        if ($id === null) {
            return false;
        }
        $sql = "DELETE FROM devices WHERE id= :id";
        $placeholders = ['id'=>$id];

        return $this->delete($sql, $placeholders);
    }

}
