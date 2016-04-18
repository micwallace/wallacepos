<?php
/**
 * WposSocketControl is part of Wallace Point of Sale system (WPOS) API
 *
 * WposSocketControl is used to control the node.js websocket server
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
class WposSocketControl {

    private $isWindows = false;

    function WposSocketControl(){
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Start the socket server
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function startSocketServer($result){
		if ($this->isWindows) {
			pclose(popen('START "WPOS" node '.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'api/server.js','r'));
		} else {
			exec("nodejs ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."api/server.js > /dev/null & echo $!", $output);
        }
		if ($this->getServerStat()===false){
            $result['error'] = "Failed to start the feed server!";
        }
        return $result;
    }

    /**
     * Stop the socket server
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function stopSocketServer($result){
		if ($this->isWindows) {
			exec('TASKKILL /F /FI "WindowTitle eq WPOS"', $output);
		} else {
			exec("kill `pidof nodejs`", $output);
		}
        if ($this->getServerStat()===true){
            $result['error'] = "Failed to stop the feed server!";
        }
        return $result;
    }

    /**
     * Checks if the server is currently running
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function isServerRunning($result){
        if ($this->getServerStat()===true){
            $result['data'] = true;
        } else {
            $result['data'] = false;
        }
        return $result;
    }

    /**
     * Restart the server
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function restartSocketServer($result){
        $result['data'] = true; // server currently running
        $result = $this->stopSocketServer($result);
        if ($result['error']=="OK"){ // successfully stopped server
            $result = $this->startSocketServer($result);
            if ($result['error']!=="OK"){
                $result['data'] = false;
            }
        }
        return $result;
    }

    /**
     * Checks if the server is running
     * @return bool
     */
    private function getServerStat(){
		if ($this->isWindows) {
			exec('TASKLIST /NH /V /FI "WindowTitle eq WPOS"', $output );
			if (strpos($output[0], 'INFO')!==false){
				$output[0] = 'Offline';
				return false;
			} else {
				$output[0] = 'Online';
				return true;
			}
		} else {
			exec('ps cax | grep nodejs > /dev/null
					if [ $? -eq 0 ]; then
						echo "Online"
							else
						echo "Offline"
					fi', $output);
			if (strpos($output[0], 'Online')!==false){
				return true;
			} else {
				return false;
			}
		}
    }

}




