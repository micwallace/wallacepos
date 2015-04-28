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
     * @var string
     */
    private $deviceId;
    /**
     * @var string
     */
    private $deviceName;
    /**
     * @var string
     */
    private $locationId;
    /**
     * @var string
     */
    private $locationName;
    /**
     * @var
     */
    private $uuid;

    /**
     * @var mixed
     */
    private $data;

    /**
     * Decode provided JSON and extract commonly used variables
     * @param $data
     */
    public function WposPosSetup($data = null){
        $this->data = $data;
        $this->uuid         = (isset($this->data->uuid) ? $this->data->uuid : "");
        $this->deviceId     = (isset($this->data->devid) ? $this->data->devid : "");
        $this->deviceName   = (isset($this->data->devname) ? $this->data->devname : "");
        $this->locationId   = (isset($this->data->locid) ? $this->data->locid : "");
        $this->locationName = (isset($this->data->locname) ? $this->data->locname : "");
    }

    /**
     * Get POS device specific configuration and aux values
     * @param array $result current result array
     * @return array API result array
     */
    public function getDeviceRecord($result){
        // Get device info using the uuid
        $devMdl     = new DevicesModel();
        $deviceInfo = $devMdl->getUuidInfo($this->uuid);
        if ($deviceInfo === false) {
            $result['error'] = "DB Error fetching your device settings";
            return $result;
        } else if (sizeof($deviceInfo) < 1) {
            // device removed or not found
            $result['data'] = ["remdev"=>true];
            $result['warning'] = "This device has been removed from the system, please contact your Administrator";
            return $result;
        }
        if ($deviceInfo[0]['disabled'] == 1){ // check if device is disabled
            $result['data'] = ["remdev"=>true];
            $result['warning'] = "This device has been disabled, please contact your Administrator";
            return $result;
        }

        // add device specific info
        $result['data']               = new stdClass();
        $result['data']->deviceid     = $deviceInfo[0]["deviceid"];
        $result['data']->devicename   = $deviceInfo[0]["devicename"];
        $result['data']->locationid   = $deviceInfo[0]["locationid"];
        $result['data']->locationname = $deviceInfo[0]["locationname"];

        // Get general & global pos configuration
        $WposConfig = new WposAdminSettings();
        $general = $WposConfig->getSettingsObject("general");
        $pos = $WposConfig->getSettingsObject("pos");
        if ($general === false || $pos === false){
            $result['error'] = "Global config could not be retrieved!";
        }
        $result['data']->general = $general;
        $result['data']->pos = $pos;

        // get devices and locations
        if (($result['data']->devices=$this->getDevices())===false || ($result['data']->locations=$this->getLocations())===false){
            $result['error'] = "Device or Location info could not be retrieved!";
        }

        if (($result['data']->users=$this->getUsers())===false){
            $result['error'] = "User info could not be retrieved!";
        }

        // get tax
        if (($result['data']->tax=$this->getTaxRecords())===false){
            $result['error'] = "Tax config could not be retrieved!";
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
        if (($result['data']->tax=$this->getTaxRecords())===false){
            $result['error'] = "Tax config could not be retrieved!";
        }

        return $result;
    }

    /**
     * Retrieve tax record
     * @return array|bool
     */
    private function getTaxRecords(){
        $taxMdl = new TaxItemsModel();
        if (($taxes = $taxMdl->get())===false){
            return false;
        }
        $result = [];
        foreach($taxes as $tax){
            $result[$tax['id']] = $tax;
        }
        return $result;
    }

    /**
     * Retrieve devices
     * @return array|bool
     */
    private function getDevices(){
        $devMdl     = new DevicesModel();
        $devices = $devMdl->get();
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
        $locMdl     = new LocationsModel();
        $locations  = $locMdl->get();
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
        if ($this->locationId == null) {
            // check if a name has been provided
            if ($this->locationName == null) {
                if ($this->deviceId == null){ // if we are adding a new device, location id or name must be provided.
                    $result['error'] = "No location id or name was provided";
                    return $result;
                }
            } else {
                // create a new location using the provided name
                if (($newid = $this->addNewLocation($this->locationName))!==false) {
                    $this->locationId = $newid;
                } else {
                    $result['error'] = "Insertion of new location record failed";

                    return $result;
                }
            }
        }
        // insert new device
        if ($this->deviceId == null) {
            if ($this->deviceName == null) {
                $result['error'] = "The no device id or name was provided";

                return $result;
            } else {
                // create a new location using the provided name
                if (($newid = $this->addNewDevice($this->deviceName, $this->locationId))!==false) {
                    $this->deviceId = $newid;
                } else {
                    $result['error'] = "Insertion of new device record failed";
                    return $result;
                }
            }
        } else {
            // check if device exists and is enabled
            $devMdl = new DevicesModel();
            $dev = $devMdl->get($this->deviceId);
            if ($dev===false || sizeof($dev)==0){
                $result['error'] = "The deviceid specified does not exist";
                return $result;
            }
            $dev = $dev[0];
            if ($dev['disabled']==1){
                $result['error'] = "The deviceid specified is disabled";
                return $result;
            }
            if ($this->locationId != null){ // if location id is left out, we can leave device at it's current location
                // check if location exists (and enabled) before updating device location
                if (!$this->doesLocationExist($this->locationId)){
                    $result['error'] = "The locationid specified does not exist or is disabled";
                    return $result;
                }
                // update location
                if ($devMdl->updateLocation($this->deviceId, $this->locationId)===false){
                    $result['error'] = "Failed to update the devices location";
                    return $result;
                }
            }
        }
        // insert the md5 signature in the device_map table
        if ($this->addNewUuid($this->uuid, $this->deviceId)) {
            // create json record to return to the client
            $result['data']               = new stdClass();
            $result['data']->deviceid     = $this->deviceId;
            $result['data']->devicename   = $this->deviceName;
            $result['data']->locationid   = $this->locationId;
            $result['data']->locationname = $this->locationName;

            // log data
            Logger::write("New device registered with uuid:".$this->uuid, "CONFIG", json_encode($this->data));

            return $result;
        } else {
            $result['error'] = "Error adding the device fingerprint into the database";

            return $result;
        }
    }

    /**
     * Add a new device record into the system
     * @return bool|string
     */
    private function addNewDevice()
    {
        $devMdl = new DevicesModel();
        $newid  = $devMdl->create($this->deviceName, $this->locationId);
        if ($newid !== false) {
            return $newid;
        } else {
            return false;
        }
    }

    /**
     * Public Method for adding new device, returns result object.
     * @param $result array current result
     * @return mixed API result
     */
    public function addDevice($result){
         // validate input
         $jsonval = new JsonValidate($this->data, '{"devname":"", "locid":1}');
         if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
         }
         if (!$this->doesLocationExist($this->locationId)){
            $result['error'] = "The location id specified does not exist";
            return $result;
         }
         if (!$this->addNewDevice()){
            $result['error'] = "Could not add the device";
         } else {
            // log data
            Logger::write("New device added", "CONFIG", json_encode($this->data));

            $result['data'] = true;
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
        $jsonval = new JsonValidate($this->data, '{"devid":1, "devname":"", "locid":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if (!$this->doesLocationExist($this->locationId)){
            $result['error'] = "The location id specified does not exist";
            return $result;
        }
        $devMdl = new DevicesModel();
        if ($devMdl->edit($this->deviceId, $this->deviceName, $this->locationId)!==false) {
            $result['data'] = true;

            // log data
            Logger::write("Device updated", "CONFIG", json_encode($this->data));
        } else {
            $result['error'] = "Could not update the device";
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
        if (!is_numeric($this->deviceId)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $devMdl = new DevicesModel();
        if ($devMdl->remove($this->deviceId)!==false) {
            $result['data'] = true;

            // log data
            Logger::write("Device deleted with id:".$this->deviceId, "CONFIG");
        } else {
            $result['error'] = "Could not remove the device";
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
        $devMdl = new DevicesModel();
        if ($this->data->disable==false){ // we don't want to enable a device with a disabled location
            $dev = $devMdl->get($this->data->id)[0];
            if (!$this->doesLocationExist($dev['locationid'])){
                $result['error'] = "The devices location is disabled, pick a new location or enable it.";
                return $result;
            }
        }
        if ($devMdl->setDisabled($this->data->id, boolval($this->data->disable))===false) {
            $result['error'] = "Could not enable/disable the device";
        }

        // log data
        Logger::write("Device ".($this->data->disable==true?"disabled":"enabled")." with id:".$this->data->id, "CONFIG");

        return $result;
    }

    /**
     * Add a new location record
     * @return bool|string
     */
    public function addNewLocation()
    {
        $locMdl = new LocationsModel();
        if (($newid = $locMdl->create($this->locationName))!==false) {
            return $newid;
        } else {
            return false;
        }
    }

    /**
     * public method for adding a location. returns result object
     * @param $result
     * @return mixed
     */
    public function addLocation($result){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"locname":""}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        if (!$this->addNewLocation()){
            $result['error'] = "Could not add the location";
        } else {
            $result['data'] = true;
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
        $jsonval = new JsonValidate($this->data, '{"locid":1, "locname":""}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        $locMdl = new LocationsModel();
        if ($locMdl->edit($this->locationId, $this->locationName)!==false) {
            $result['data'] = true;

            // log data
            Logger::write("Location updated", "CONFIG", json_encode($this->data));
        } else {
            $result['error'] = "Could not update the location";
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
        if (!is_numeric($this->locationId)){
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // Check if the location is in use
        if ($this->isLocationUsed($this->locationId)){
            $result['error'] = "The location is currently active, disable locations devices or update to a new location before deleting.";
            return $result;
        }
        $locMdl = new LocationsModel();
        if ($locMdl->remove($this->locationId)!==false) {
            $result['data'] = true;

            // log data
            Logger::write("Location deleted with id:".$this->locationId, "CONFIG");
        } else {
            $result['error'] = "Could not delete the location";
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
        $locMdl = new LocationsModel();
        if ($locMdl->setDisabled($this->data->id, boolval($this->data->disable))===false) {
            $result['error'] = "Could not enable/disable the location";
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
        $locMdl = new LocationsModel();
        $loc = $locMdl->get($id);
        if (sizeof($loc)>0){
            $loc = $loc[0];
            if ($loc['disabled']==0){
                // for this function we don't want disabled devices
                // (ie, it would allow adding disabled location to a device or enabling device with disabled location)
                return true;
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
        $devMdl = new DevicesModel();
        $devs = $devMdl->getLocationDeviceIds(null, $id);
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

        $devMdl = new DevicesModel();
        if ($devMdl->addUuid($deviceId, $uuid) !== false) {
            return true;
        } else {
            return false;
        }
    }

}