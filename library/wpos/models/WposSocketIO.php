<?php
/*require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Client.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/EngineInterface.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/AbstractPayload.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Exception/SocketException.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Exception/MalformedUrlException.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Exception/ServerConnectionFailureException.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Exception/UnsupportedActionException.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Exception/UnsupportedTransportException.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Engine/AbstractSocketIO.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Engine/SocketIO/Session.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Engine/SocketIO/Version0X.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Engine/SocketIO/Version1X.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Payload/Decoder.php");
require_once($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Payload/Encoder.php");*/

require $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/autoload.php";

use ElephantIO\Client as Client;
use ElephantIO\Engine\SocketIO\Version1X as Version1X;
/**
 * WposSocketIO is part of Wallace Point of Sale system (WPOS) API
 *
 * WposSocketIO is used to send data to the node.js socket.io (websocket) server
 * It uses ElephantIO library to send the data
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
 * @since      File available since 30/04/14 9:28 PM
 */
class WposSocketIO {

    /**
     * @var ElephantIO\Client|null The elephant IO client
     */
    private $elephant = null;
    /**
     * @var string This hashkey provides authentication for php operations
     */
    private $hashkey = "5d40b50e172646b845640f50f296ac3fcbc191a7469260c46903c43cc6310ace";

    /**
     * Initialise the elephantIO object and set the hashkey
     */
    function __construct(){
        $conf = WposAdminSettings::getConfigFileValues(true);
        if (isset($conf->feedserver_key))
            $this->hashkey = $conf->feedserver_key;

        $this->elephant = new Client(new Version1X('http://127.0.0.1:'.$conf->feedserver_port.'/?hashkey='.$this->hashkey));
    }

    /**
     * Sends session updates to the node.js feed server, optionally removing the corresponding session
     * @param $event
     * @param $data
     * @return bool
     */
    private function sendData($event, $data){
        set_error_handler(function() { /* ignore warnings */ }, E_WARNING);
        try {
            $this->elephant->initialize();
            $this->elephant->emit($event, $data);
            $this->elephant->close();
        } catch(Exception $e){
            restore_error_handler();
            return false;
        }
        restore_error_handler();
        return true;
    }

    /**
     * Sends session updates to the node.js feed server, optionally removing the corresponding session
     * @param $data
     * @param bool $remove
     * @return bool
     */
    public function sendSessionData($data, $remove = false){

        return $this->sendData('session', ['hashkey'=>$this->hashkey, 'data'=>$data, 'remove'=>$remove]);
    }

    /**
     * Generate a random hashkey for php -> node.js authentication
     * @return bool
     */
    public function generateHashKey(){
        $key = hash('sha256', WposAdminUtilities::getToken(256));
        WposAdminSettings::setConfigFileValue('feedserver_key', $key);

        $socket = new WposSocketControl();
        if ($socket->isServerRunning())
            $this->sendData('hashkey', ['hashkey'=>$this->hashkey, 'newhashkey'=>$key]);

        return;
    }

    /**
     * Send a reset request to all pos devices or the device specified
     * @param null $devices
     * @return bool
     */
    public function sendResetCommand($devices=null){

        return $this->sendDataToDevices(['a'=>'reset'], $devices);
    }

    /**
     * Send data to the specified devices, if no devices specified then all receive it.
     * @param $data
     * @param null $devices
     * @return bool
     */
    private function sendDataToDevices($data, $devices=null){
        // sends message to all authenticated devices
        return $this->sendData('send', ['hashkey'=>$this->hashkey, 'include'=>$devices, 'data'=>$data]);
    }

    /**
     * Send a message to the specified devices, if no devices specified then all receive it. Admin dash excluded
     * @param $devices
     * @param $message
     * @return bool
     */
    public function sendMessageToDevices($devices, $message){
        // send message to specified devices
        return $this->sendDataToDevices(['a' => 'msg', 'data' => $message], $devices);
    }

    /**
     * Broadcast a stored item addition/update/delete to all connected devices.
     * @param $item
     * @return bool
     */
    public function sendItemUpdate($item){
        // item updates get sent to all authenticated clients
        return $this->sendDataToDevices(['a' => 'item', 'data' => $item], null);
    }

    /**
     * Broadcast a customer addition/update/delete to all connected devices.
     * @param $customer
     * @return bool
     */
    public function sendCustomerUpdate($customer){

        return $this->sendDataToDevices(['a' => 'customer', 'data' => $customer], null);
    }

    /**
     * Send a sale update to the specified devices, if no devices specified, all receive.
     * @param null $devices
     * @param $sale
     * @return bool
     */
    public function sendSaleUpdate($devices=null, $sale){ // device that the record was updated on

        return $this->sendDataToDevices(['a' => 'sale', 'data' => $sale], $devices);
    }

    /**
     * Broadcast a configuration update to all connected devices.
     * @param $newconfig
     * @param $configset; the set name for the values
     * @return bool
     */
    public function sendConfigUpdate($newconfig, $configset){
        return $this->sendDataToDevices(['a' => 'config', 'type' => $configset, 'data' => $newconfig], null);
    }

    /**
     * Send updated device specific config
     * @param $newconfig
     * @return bool
     */
    public function sendDeviceConfigUpdate($newconfig){
        return $this->sendDataToDevices(['a' => 'config', 'type' => 'deviceconfig', 'data' => $newconfig], null);
    }
}