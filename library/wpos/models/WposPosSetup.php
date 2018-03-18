<?php
/**
 * WposSetup is part of Wallace Point of Sale system (WPOS) API
 *
 * WposSetup handles setup of pos devices, including first time setup and other device specific configuration.
 * It also provides device/user specific config records
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
class WposPosSetup
{
    /**
     * @var mixed
     */
    private $data;

    private $devMdl;

    private $locMdl;

    /**
     * Decode provided JSON and extract commonly used variables
     * @param $data
     */
    public function __construct($data = null){
        $this->devMdl = new DevicesModel();
        $this->locMdl = new LocationsModel();
        $this->data = $data;
    }

    /**
     * Get POS device specific configuration and aux values
     * @param array $result current result array
     * @return array API result array
     */
    public function getDeviceRecord($result){
        // Get device info using the uuid
        $deviceInfo = $this->devMdl->getUuidInfo($this->data->uuid);
        if ($deviceInfo === false) {
            $result['error'] = "DB Error fetching your device settings";
            return $result;
        } else if ($deviceInfo === null) {
            // device removed or not found
            $result['data'] = "removed";
            $result['warning'] = "This device has been removed from the system, please contact your Administrator";
            return $result;
        }
        if ($deviceInfo['disabled'] == 1){ // check if device is disabled
            $result['data'] = "disabled";
            $result['warning'] = "This device has been disabled, please contact your Administrator";
            return $result;
        }

        // add device specific info
        $result['data']               = new stdClass();
        $result['data']->deviceid     = $deviceInfo["deviceid"];
        $result['data']->devicename   = $deviceInfo["devicename"];
        $result['data']->locationid   = $deviceInfo["locationid"];
        $result['data']->locationname = $deviceInfo["locationname"];
        $result['data']->deviceconfig = $deviceInfo["deviceconfig"];
        $reg = new stdClass();
        $reg->uuid = $deviceInfo["uuid"];
        $reg->id = $deviceInfo["regid"];
        $reg->dt = $deviceInfo["regdt"];
        $result['data']->registration = $reg;

        // Get general & global pos configuration
        $WposConfig = new WposAdminSettings();
        $settings = $WposConfig->getAllSettings();
        $result['data']->general = $settings['general'];
        $result['data']->pos = $settings['pos'];
        $result['data']->invoice = $settings['invoice'];

        // get devices and locations
        if (($result['data']->devices=$this->getDevices())===false || ($result['data']->locations=$this->getLocations())===false){
            $result['error'] = "Device or Location info could not be retrieved!";
        }

        if (($result['data']->users=$this->getUsers())===false){
            $result['error'] = "User info could not be retrieved!";
        }

        $dataMdl = new WposPosData();
        $categories = $dataMdl->getCategories([]);
        if ($result['data']===false){
            $result['error'] = "Categories could not be retrieved: ".$categories['error'];
        } else {
            $result['data']->item_categories = $categories['data'];
        }

        // get tax
        $tax = WposPosData::getTaxes();
        if (is_null($tax['error'])){
            $result['data']->tax = $tax['data'];
        } else {
            $result['error'] = $tax['error'];
        }

        // get templates
        $templates = WposTemplates::getTemplates();
        if ($templates['error']=="OK"){
            $result['data']->templates = $templates['data'];
        } else {
            $result['error'] = $templates['error'];
        }

        return $result;
    }

    /**
     * Get admin dash specific aux values
     * @param array $result current result array
     * @return array API result array
     */
    public function getAdminConfig($result){
        $result['data'] = new stdClass();
        $WposConfig = new WposAdminSettings();
        // Get general & global pos configuration
        $settings = $WposConfig->getAllSettings();

        if ($settings === false){
            $result['error'] = "Global config could not be retrieved!";
        }
        $result['data']->general = $settings['general'];
        $result['data']->pos = $settings['pos'];
        $result['data']->invoice = $settings['invoice'];

        // get devices and locations
        if (($result['data']->devices=$this->getDevices())===false || ($result['data']->locations=$this->getLocations())===false){
            $result['error'] = "Device or Location info could not be retrieved!";
        }
        // get users
        if (($result['data']->users=$this->getUsers())===false){
            $result['error'] = "User info could not be retrieved!";
        }

        // get tax
        $tax = WposPosData::getTaxes();
        if (!isset($tax['error'])){
            $result['data']->tax = $tax['data'];
        } else {
            $result['error'] = $tax['error'];
        }

        // get templates
        $templates = WposTemplates::getTemplates();
        if ($templates['error']=="OK"){
            $result['data']->templates = $templates['data'];
        } else {
            $result['error'] = $templates['error'];
        }

        return $result;
    }

    /**
     * Retrieve devices
     * @return array|bool
     */
    private function getDevices(){
        $devices = $this->devMdl->get();
        if ($devices === false){
            return false;
        }
        $result = [];
        foreach ($devices as $device) {
            $result[$device['id']] =  $device;
        }
        // add admin dashboard value
        $result[0] = ["id"=>0, "name"=>"Admin Dash", "locationname"=>"Admin Dash", "disabled"=>0];
        return $result;
    }

    /**
     * Retrieve locations
     * @return array|bool
     */
    private function getLocations(){
        $locations  = $this->locMdl->get();
        if ($locations === false){
            return false;
        }
        $result = [];
        foreach ($locations as $location) {
            $result[$location['id']] = $location;
        }
        // add admin dashboard value
        $result[0] = ["id"=>0, "name"=>"Admin Dash", "disabled"=>0];
        return $result;
    }

    /**
     * Retrieve users
     * @return array|bool
     */
    private function getUsers(){
        $authMdl     = new AuthModel();
        $users  = $authMdl->get();
        if ($users === false){
            return false;
        }
        $result = [];
        foreach ($users as $user) {
            unset($user['password']);
            unset($user['permissions']);
            $result[$user['id']] = $user;
        }
        return $result;
    }

    /**
     * Setup device using the provided registration info,
     * This inserts/updates the device/location records before inserting a new uuid to bind to the device.
     * @param $result array current API result array
     * @return array API result array
     */
    public function setupDevice($result)
    {
        // create new location record
        if ($this->data->locationid == null) {
            // check if a name has been provided
            if ($this->data->locationname == null) {
                if ($this->data->deviceid == null){ // if we are adding a new device, location id or name must be provided.
                    $result['error'] = "No location id or name was provided";
                    return $result;
                }
            } else {
                // create a new location using the provided name
                if (($newid = $this->addNewLocation($this->data->locationname))!==false) {
                    $this->data->locationid = $newid;
                } else {
                    $result['error'] = "Insertion of new location record failed: ".$this->locMdl->errorInfo;
                    return $result;
                }
            }
        } else {
            if (($this->data->locationname=$this->doesLocationExist($this->data->locationid))===false){
                $result['error'] = "Could not find a location with the specified ID";
                return $result;
            }
        }
        // insert new device
        if ($this->data->deviceid == null) {
            if ($this->data->devicename == null) {
                $result['error'] = "The no device id or name was provided";

                return $result;
            } else {
                // create a new device using the provided name; default to general register for now
                $deviceData = new stdClass();
                $deviceData->name = $this->data->devicename;
                $deviceData->locationid = $this->data->locationid;
                $deviceData->type = "general_register";
                $deviceData->ordertype = "printer";
                $deviceData->orderdisplay = 1;
                $deviceData->kitchenid = 0;
                $deviceData->barid = 0;
                if (($newid = $this->addNewDevice($deviceData))!==false) {
                    $this->data->deviceid = $newid;
                } else {
                    $result['error'] = "Insertion of new device record failed: ".$this->devMdl->errorInfo;
                    return $result;
                }
            }
        } else {
            // check if device exists and is enabled
            $dev = $this->devMdl->get($this->data->deviceid, null, null, false);
            if ($dev===false || sizeof($dev)==0){
                $result['error'] = "The deviceid specified does not exist";
                return $result;
            }
            $dev = $dev[0];
            if ($dev['disabled']==1){
                $result['error'] = "The deviceid specified is disabled";
                return $result;
            }
            $deviceData = json_decode($dev['data']);
            $this->data->deviceid = $dev['id'];
            if ($this->data->locationid != null){ // if location id is left out, we can leave device at it's current location
                // check if location exists (and enabled) before updating device location
                /*if (!$this->doesLocationExist($this->data->locationid)){
                    $result['error'] = "The locationid specified does not exist or is disabled";
                    return $result;
                }*/
                // update location
                $deviceData->locationid = $this->data->locationid;
                if ($this->devMdl->edit($this->data->deviceid, $deviceData)===false){
                    $result['error'] = "Failed to update the devices location: ".$this->devMdl->errorInfo;
                    return $result;
                }
            }
        }

        $socketIO = new WposSocketIO();
        $deviceData->id = $this->data->deviceid;
        $deviceData->locationname = $this->data->locationname;
        $socketIO->sendDeviceConfigUpdate($deviceData);

        // insert the md5 signature in the device_map table
        if ($this->addNewUuid($this->data->uuid, $this->data->deviceid)) {
            // create json record to return to the client
            $result['data']               = new stdClass();
            $result['data']->deviceid     = $this->data->deviceid;
            $result['data']->devicename   = $this->data->devicename;
            $result['data']->locationid   = $this->data->locationid;
            $result['data']->locationname = $this->data->locationname;

            // log data
            Logger::write("New device registered with uuid: ".$this->data->uuid, "CONFIG", json_encode($this->data));

            return $result;
        } else {
            $result['error'] = "Error adding the device fingerprint into the database";

            return $result;
        }
    }

    /**
     * Add a new device record into the system
     * @param $data
     * @return bool|string
     */
    private function addNewDevice($data)
    {
        return $this->devMdl->create($data);
    }

    /**
     * Public Method for adding new device, returns result object.
     * @param $result array current result
     * @return mixed API result
     */
    public function addDevice($result){
         // validate input
         $jsonval = new JsonValidate($this->data, '{"name":"", "locationid":1, "type":"", "ordertype":"", "orderdisplay":""}');
         if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
         }
         if (($locname=$this->doesLocationExist($this->data->locationid))===false){
            $result['error'] = "The location id specified does not exist";
            return $result;
         }
         if (($newid = $this->addNewDevice($this->data))===false){
            $result['error'] = "Could not add the device: ".$this->devMdl->errorInfo;
         } else {
            $this->data->id = $newid;
            $this->data->locationname = $locname;
            $socketIO = new WposSocketIO();
            $socketIO->sendDeviceConfigUpdate($this->data);
            // log data
            Logger::write("New device added", "CONFIG", json_encode($this->data));
            $result['data'] = $this->data;
         }
        return $result;
    }

    /**
     * Update a device name and location
     * @param $result
     * @return mixed
     */
    public function updateDevice($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"id":1, "name":"", "locationid":1, "type":"", "ordertype":"", "orderdisplay":true}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if (($locname=$this->doesLocationExist($this->data->locationid))===false){
            $result['error'] = "The location id specified does not exist";
            return $result;
        }
        if ($this->devMdl->edit($this->data->id, $this->data)!==false) {
            $this->data->locationname = $locname;
            $result['data'] = $this->data;
            $socketIO = new WposSocketIO();
            $socketIO->sendDeviceConfigUpdate($this->data);
            // log data
            Logger::write("Device updated", "CONFIG", json_encode($this->data));
        } else {
            $result['error'] = "Could not update the device: ".$this->devMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Delete a device record
     * @param $result
     * @return mixed
     */
    public function deleteDevice($result){
        // validation
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        if ($this->devMdl->remove($this->data->id)!==false) {
            $result['data'] = true;
            $socketIO = new WposSocketIO();
            $socketIO->sendDeviceConfigUpdate(['id'=>$this->data->id, 'a'=>"removed"]);
            // log data
            Logger::write("Device deleted with id:".$this->data->id, "CONFIG");
        } else {
            $result['error'] = "Could not remove the device: ".$this->devMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Set a device disabled
     * @param $result
     * @return mixed
     */
    public function setDeviceDisabled($result){
        // validate input
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // get location id
        if ($this->data->disable==false){ // we don't want to enable a device with a disabled location
            $dev = $this->devMdl->get($this->data->id)[0];
            if ($this->doesLocationExist($dev['locationid'])===false){
                $result['error'] = "The devices location is disabled, pick a new location or enable it.";
                return $result;
            }
        }
        if ($this->devMdl->setDisabled($this->data->id, boolval($this->data->disable))===false) {
            $result['error'] = "Could not enable/disable the device: ".$this->devMdl->errorInfo;
        }
        if ($this->data->disable){
            $socketIO = new WposSocketIO();
            $socketIO->sendDeviceConfigUpdate(['id'=>$this->data->id, 'a'=>"disabled"]);
        }
        // log data
        Logger::write("Device ".($this->data->disable==true?"disabled":"enabled")." with id:".$this->data->id, "CONFIG");

        return $result;
    }

    /**
     * Set a device disabled
     * @param $result
     * @return mixed
     */
    public function getDeviceRegistrations($result){
        // validate input
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // get location id
        if (($result['data']=$this->devMdl->getUuids($this->data->id))===false){
            $result['error'] = "Could not retrieve device registrations: ".$this->devMdl->errorInfo;
        }

        return $result;
    }

    /**
     * Set a device disabled
     * @param $result
     * @return mixed
     */
    public function deleteDeviceRegistration($result){
        // validate input
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // get location id
        if ($this->devMdl->removeUuid($this->data->id)!==false) {
            $result['data'] = true;
            // log data
            Logger::write("Device registration deleted with id:".$this->data->id, "CONFIG");
        } else {
            $result['error'] = "Could not remove the device registration: ".$this->devMdl->errorInfo;
        }

        return $result;
    }

    /**
     * Add a new location record
     * @param $name
     * @return integer|string
     */
    public function addNewLocation($name)
    {
        return $this->locMdl->create($name);
    }

    /**
     * public method for adding a location. returns result object
     * @param $result
     * @return mixed
     */
    public function addLocation($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"name":""}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if (!$this->data->id = $this->addNewLocation($this->data->name)){
            $result['error'] = "Could not add the location: ".$this->locMdl->errorInfo;
        } else {
            $result['data'] = $this->data;
        }
        return $result;
    }

    /**
     * Update a locations name
     * @param $result
     * @return mixed
     */
    public function updateLocationName($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"id":1, "name":""}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if ($this->devMdl->edit($this->data->id, $this->data->name)!==false) {
            $result['data'] = $this->data;
            // log data
            Logger::write("Location updated", "CONFIG", json_encode($this->data));
        } else {
            $result['error'] = "Could not update the location: ".$this->devMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Delete a location using it's ID
     * @param $result
     * @return mixed
     */
    public function deleteLocation($result){
        // validation
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // Check if the location is in use
        if ($this->isLocationUsed($this->data->id)){
            $result['error'] = "The location is currently active, disable locations devices or update to a new location before deleting.";
            return $result;
        }
        if ($this->devMdl->remove($this->data->id)!==false) {
            $result['data'] = true;
            // log data
            Logger::write("Location deleted with id:".$this->data->id, "CONFIG");
        } else {
            $result['error'] = "Could not delete the location: ".$this->locMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Set a location disabled using it's ID.
     * @param $result
     * @return mixed
     */
    public function setLocationDisabled($result){
        // validate input
        if (!is_numeric($this->data->id)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        if ($this->data->disable==true){
            // Check if the location is in use
            if ($this->isLocationUsed($this->data->id)==true){
                $result['error'] = "The location is currently active, change devices to a new location before disabling.";
                return $result;
            }
        }
        if ($this->devMdl->setDisabled($this->data->id, boolval($this->data->disable))===false) {
            $result['error'] = "Could not enable/disable the location: ".$this->locMdl->errorInfo;
        }

        // log data
        Logger::write("Device ".($this->data->disable==true?"disabled":"enabled")." with id:".$this->data->id, "CONFIG");

        return $result;
    }

    /**
     * Returns true if a location exists and is not disabled
     * @param $id
     * @return bool
     */
    private function doesLocationExist($id){
        $loc = $this->locMdl->get($id);
        if (sizeof($loc)>0){
            $loc = $loc[0];
            if ($loc['disabled']==0){
                // for this function we don't want disabled devices
                // (ie, it would allow adding disabled location to a device or enabling device with disabled location)
                return $loc['name'];
            }
        }
        return false;
    }

    /**
     * Returns true if a device is associated with the provided locationid
     * @param $id
     * @return bool
     */
    private function isLocationUsed($id){
        $devs = $this->devMdl->getLocationDeviceIds(null, $id);
        if (sizeof($devs)>0){
            return true;
        }
        return false;
    }

    /**
     * Add a new UUID to bind to the device
     * @param $deviceId
     * @param $uuid
     *
     * @return bool true if uuid insert successful
     */
    private function addNewUuid($deviceId, $uuid)
    {
        if ($this->devMdl->addUuid($deviceId, $uuid) !== false) {
            return true;
        } else {
            return false;
        }
    }

}