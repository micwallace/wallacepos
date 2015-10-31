<?php
/**
 * WposAdminSettings is part of Wallace Point of Sale system (WPOS) API
 *
 * WposAdminSettings is used to retrieve and update the system configuration sets
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
 * @since      File available since 12/04/14 3:44 PM
 */
class WposAdminSettings {
    /**
     * @var stdClass provided data (updated config)
     */
    private $data;
    /**
     * @var String the name of the current configuration
     */
    private $name;
    /**
     * @var ConfigModel the config DB object
     */
    private $configMdl;
    /**
     * @var stdClass the current configuration
     */
    private $curconfig;

    /**
     * Init object with any provided data
     * @param null $data
     * @return $this|bool
     */
    function WposAdminSettings($data = null){
        if ($data !== null){
            // parse the data and put it into an object
            $this->data = $data;
            $this->name = (isset($this->data->name)?$this->data->name:null);
            unset($this->data->name);
        } else {
            $this->data = new stdClass();
        }

        return $this;
    }

    /**
     * Set the name of the configuration set to update.
     * @param $name
     */
    public function setName($name){
        $this->name = $name;
    }

    /**
     * Get a config set by name
     * @param $name
     * @return bool|mixed
     */
    public static function getSettingsObject($name){
        $configMdl = new ConfigModel();
        $data = $configMdl->get($name);
        if ($data===false){
            return false;
        }

        if (!$result = json_decode($data[0]['data'])){
            return false;
        }

        return $result;
    }

    /**
     * Put a setting value, using section name, key, value
     * @param $name
     * @param $key
     * @param $value
     * @return bool|mixed
     */
    public static function putValue($name, $key, $value){
        $configMdl = new ConfigModel();
        $data = $configMdl->get($name);
        if ($data===false){
            return false;
        }
        if (!$result = json_decode($data[0]['data'])){
            return false;
        }

        $result->{$key} = $value;

        if ($configMdl->edit($name, json_encode($result))===false){
           return false;
        }

        return true;
    }

    /**
     * Get all config values as an array
     * @internal param $name
     * @return bool|mixed
     */
    public function getAllSettings(){
        $this->configMdl = new ConfigModel();
        $data = $this->configMdl->get();
        if ($data===false){
            return false;
        }
        $settings = [];
        foreach ($data as $setting){
            $settings[$setting['name']] = json_decode($setting['data']);
            if ($setting['name']=="general"){
                $settings[$setting['name']]->gcontactaval = $settings[$setting['name']]->gcontacttoken!='';
                unset($settings[$setting['name']]->gcontacttoken);
            } elseif ($setting['name']=="accounting"){
                $settings[$setting['name']]->xeroaval = $settings[$setting['name']]->xerotoken!='';
                unset($settings[$setting['name']]->xerotoken);
            }
        }

        return $settings;
    }

    /**
     * API method to retrieve a config.
     * @param $result
     * @return mixed
     */
    public function getSettings($result){
        if (!isset($this->name)){
            $result['error'] = "A config-set name must be supplied";
            return $result;
        }
        $this->configMdl = new ConfigModel();
        $data = $this->configMdl->get($this->name);
        if ($data!==false){
            $data = json_decode($data[0]['data']);
            if ($this->name=="general"){
                $data->gcontactaval = $data->gcontacttoken!='';
                unset($data->gcontacttoken);
                $data->timezone = getenv("WPOS_TIMEZONE");
            } else if ($this->name=="accounting"){
                // check xero token expiry TODO: Remove when we become xero partner
                if ($data->xerotoken!='' && $data->xerotoken->expiredt<time()){
                    $data->xerotoken='';
                }
                $data->xeroaval = $data->xerotoken!='';
                unset($data->xerotoken);
            }
            $result['data'] = $data;
        } else {
            $result['error'] = "Could not retrive the selected config record: ".$this->configMdl->errorInfo;
        }

        return $result;
    }

    /**
     * Update and save the current configuration
     * @param $result
     * @return mixed
     */
    public function saveSettings($result){
        if (!isset($this->name)){
            $result['error'] = "A config-set name must be supplied";
            return $result;
        }
        $this->configMdl = new ConfigModel();
        $config = $this->configMdl->get($this->name);
        if ($config!==false){
            if (sizeof($config)>0){

                $this->curconfig = json_decode($config[0]['data']); // get the json object
                $configbk = $this->curconfig;

                if (isset($this->data->gcontactcode) && $this->data->gcontactcode!=''){
                    // Get google access token
                    $tokens = GoogleIntegration::processGoogleAuthCode($this->data->gcontactcode);
                    if ($tokens){
                        $tokens = json_decode($tokens);
                        $this->data->gcontacttoken = $tokens;
                        $this->data->gcontact = 1;
                    }
                    unset($this->data->gcontactcode);
                }

                foreach ($this->curconfig as $key=>$value){
                    if (isset($this->data->{$key})){ // update the config value if specified in the data
                        $this->curconfig->{$key} = $this->data->{$key};
                    }
                }

                if ($this->configMdl->edit($this->name, json_encode($this->curconfig))===false){
                    $result['error'] = "Could not update config record: ".$this->configMdl->errorInfo;
                } else {
                    // generate new qr code
                    if ($this->name == "pos"){
                        if ($this->data->recqrcode !== $configbk->recqrcode && $this->data->recqrcode!=""){
                            $this->generateQRCode();
                        }
                    }
                    $conf = $this->curconfig;
                    if ($this->name=="general"){
                        unset($conf->gcontacttoken);
                        $conf->timezone = $this->data->timezone;
                        if ($conf->timezone!=getenv("WPOS_TIMEZONE")) {
                            $this->updateConfigFileValue('timezone', $conf->timezone);
                        }
                    } else if ($this->name=="accounting"){
                        unset($conf->xerotoken);
                    }

                    // send config update to POS terminals
                    $socket = new WposSocketIO();
                    $socket->sendConfigUpdate($conf, $this->name);

                    // Success; log data
                    Logger::write("System configuration updated:".$this->name, "CONFIG", json_encode($conf));
                }
            } else {
                // if current settings are null, create a new record with the specified name
                if ($this->configMdl->create($this->name, json_encode($this->data))===false){
                    $result['error'] = "Could not insert new config record: ".$this->configMdl->errorInfo;
                }
            }
        } else {
            $result['error'] = "Could not retrieve the selected config record: ".$this->configMdl->errorInfo;
        }

        return $result;
    }

    /**
     * Updates config.json value
     * @param $key
     * @param $value
     */
    private function updateConfigFileValue($key, $value){
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/wpos/config.json")){
            $config = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/wpos/config.json"));
            $config->{$key} = $value;
            file_put_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/wpos/config.json", json_encode($config));
        }
    }

    /**
     * Generate a QR code, executed if qrcode text has changed
     */
    public function generateQRCode(){
        include($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/phpqrcode.php');
        //echo("Creating QR code");
        $path = "/docs/qrcode.png";
        QRcode::png($this->data->recqrcode, $_SERVER['DOCUMENT_ROOT'].$path, QR_ECLEVEL_L, 6, 1);
    }

}


?>