<?php
require $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/elephantio/Client.php";
use ElephantIO\Client as Elephant;
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
    private $hashkey = "0798f20c2c513da7cad1af28ffa3012cdafd0e799e41912f006e6d46c8e99327";

    /**
     * Initialise the elephantIO object and set the hashkey
     */
    function WposSocketIO(){

        $this->elephant = new Elephant('http://127.0.0.1:8080', 'socket.io', 1, false, true, true);
        $this->elephant->setHandshakeQuery([
            'hashkey' => $this->hashkey
        ]);

    }

    /**
     * Sends session updates to the node.js feed server, optionally removing the corresponding session
     * @param $data
     * @param bool $remove
     */
    public function sendSessionData($data, $remove = false){
        $this->elephant->init();
        $this->elephant->send(
            ElephantIO\Client::TYPE_EVENT,
            null,
            null,
            json_encode(['name' => 'session', 'args' => ['hashkey'=>$this->hashkey, 'data'=>$data, 'remove'=>$remove]])
        );
        $this->elephant->close();
    }

    /**
     * Broadcast a message to all authenticated devices
     * REDUNDANT Is it used anywhere?
     * @param $data
     */
    private function sendBroadcastData($data){
        // sends message to all connected devices, even if not authenticate
        // this is redundant because of new session sharing
        $this->elephant->init();
        $this->elephant->send(
            ElephantIO\Client::TYPE_EVENT,
            null,
            null,
            json_encode(['name' => 'broadcast', 'args' => $data])
        );
        $this->elephant->close();
    }

    /**
     * Broadcast a message to all connected/authenticated devices except the admin dash
     * @param $message
     * @return bool
     */
    public function sendBroadcastMessage($message){
        $this->sendBroadcastData(['a' => 'msg', 'data' => $message]);
        return true;
    }

    /**
     * Send a reset request to all pos devices or the device specified
     * @param null $devices
     * @return bool
     */
    public function sendResetCommand($devices=null){
        if ($devices==null){
            $this->sendBroadcastData(['a'=>'reset']);
        } else {
            $this->sendDataToDevices(['a'=>'reset'], $devices);
        }
        return true;
    }

    /**
     * Send data to the specified devices, if no devices specified then all receive it.
     * @param $data
     * @param null $devices
     */
    private function sendDataToDevices($data, $devices=null){
        // sends message to all authenticated devices
        $this->elephant->init();
        $this->elephant->send(
            ElephantIO\Client::TYPE_EVENT,
            null,
            null,
            json_encode(['name' => 'send', 'args' => ['hashkey'=>$this->hashkey, 'include'=>$devices, 'data'=>$data]])
        );
        $this->elephant->close();
    }

    /**
     * Send a message to the specified devices, if no devices specified then all receive it. Admin dash excluded
     * @param $devices
     * @param $message
     * @return bool
     */
    public function sendMessageToDevices($devices, $message){
        // send message to specified devices
        $this->sendDataToDevices(['a' => 'msg', 'data' => $message], $devices);
        return true;
    }

    /**
     * Broadcast a stored item addition/update/delete to all connected devices.
     * @param $item
     * @return bool
     */
    public function sendItemUpdate($item){
        // item updates get sent to all authenticated clients
        $this->sendDataToDevices(['a' => 'item', 'data' => $item], null);
        return true;
    }

    /**
     * Broadcast a customer addition/update/delete to all connected devices.
     * @param $customer
     * @param int $senddev
     */
    public function sendCustomerUpdate($customer, $senddev = 0){

    }

    /**
     * Send a sale update to the specified devices, if no devices specified, all receive.
     * @param null $devices
     * @param $sale
     * @return bool
     */
    public function sendSaleUpdate($devices=null, $sale){ // device that the record was updated on

        $this->sendDataToDevices(['a' => 'sale', 'data' => $sale], $devices);
        return true;
    }

    /**
     * Broadcast a configuration update to all connected devices.
     * @param $newconfig
     * @param $type
     */
    public function sendConfigUpdate($newconfig, $type){
        switch($type){
            case "general":
                $this->sendDataToDevices(['a' => 'config', 'type' => "general", 'data' => $newconfig], null);
                break;
            case "pos":
                $this->sendDataToDevices(['a' => 'config', 'type' => "pos", 'data' => $newconfig], null);
                break;
        }
    }

}