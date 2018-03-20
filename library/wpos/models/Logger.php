<?php
/**
 * Logger is part of Wallace Point of Sale system (WPOS) API
 *
 * Logger is a simple, static logging class. That is all.
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
 * @since      File available since 18/07/14 5:20 PM
 */

class Logger {

    /**
     * @var string the directory to store logs relative to project root (doc+app root).
     */
    private static $directory = "docs/logs";

    /**
     * Log an event into the log file
     * @param $msg
     * @param string $type
     * @param null $data
     */
    public static function write($msg, $type="Misc", $data=null, $showUser=true){

        if ($showUser) {
            if (php_sapi_name() === 'cli'){
                $user = "system:cli";
            } else {
                $auth = new Auth();
                $user = $auth->isLoggedIn() ? $auth->getUserId() . ":" . $auth->getUsername() : ($auth->isCustomerLoggedIn() ? $auth->getCustomerId() . ":" . $auth->getCustomerUsername() : 'system');
            }
        }
        // open file
        $fd = fopen($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].self::$directory.DIRECTORY_SEPARATOR."wpos_log_".date("y-m-d").".txt", "a");
        // write string
        fwrite($fd, "[".date("y-m-d H:i:s")."] (".$type.(isset($user)?' - '.$user.') ':') ').$msg.($data!=null?"\nData: ".$data:"")."\n");
        // close file
        fclose($fd);
    }

    /**
     * Read a log file using it's filename
     * @param $filename
     * @return string
     */
    public static function read($filename){
        return file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].self::$directory.DIRECTORY_SEPARATOR.$filename);
    }

    /**
     * List the contents of the log dir
     * @return array
     */
    public static function ls(){
        $dir = scandir($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].self::$directory);
        unset($dir[0]);
        unset($dir[1]);
        return $dir;
    }
}