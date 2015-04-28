<?php
/**
 * WposAdminUtilities is part of Wallace Point of Sale system (WPOS) API
 *
 * WposAdminUtilities is used for misc tasks including data backup and archiving
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
class WposAdminUtilities {

    /**
     * Init, setting provided data
     * @param null $data
     * @return $this|bool
     */
    function WposAdminUtilities($data = null){
        // parse the data and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
        return $this;
    }
    // UTIL FUNCTIONS
    /**
     * Format the provided JS timestamp into the config specified format
     * @param $timestamp
     * @param $dateformat
     * @param bool $includetime
     * @return bool|string
     */
    public static function getDateFromTimeStamp($timestamp, $dateformat, $includetime = true){
        if ($dateformat==null){
            $confMdl = new ConfigModel();
            $conf = $confMdl->get('general');
            $dateformat = $conf['dateformat'];
        }
        // divide from javascript timestamp into unix epoch
        $timestamp = $timestamp / 1000;
        // get date format
        $timestr=date($dateformat, $timestamp);

        if ($includetime === true)
            $timestr.=' '.date('H:i:s', $timestamp);

        return $timestr;
    }

    /**
     * Formats currency for display, includes provided currency symbol or default
     * @param $value
     * @param $currency
     * @return string
     */
    public static function currencyFormat($currency, $value){
        if ($currency==null){
            $confMdl = new ConfigModel();
            $conf = $confMdl->get('general');
            $currency = $conf['curformat'];
        }
        return $currency.number_format($value, 2, ".", ",");
    }

    /**
     * Get a random token
     * @param $min
     * @param $max
     * @return mixed
     */
    private static function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    /**
     * Get a random token
     * @param int $length
     * @return string
     */
    public static function getToken($length=32){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for($i=0;$i<$length;$i++){
            $token .= $codeAlphabet[self::crypto_rand_secure(0,strlen($codeAlphabet))];
        }
        return $token;
    }

    /**
     *  Backup database and init download.
     */
    function backUpDatabase(){
        include $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/mysql.export.class.php";
        $conf = DbConfig::getConf();
        $e = new export_mysql($conf['host'], $conf['user'], $conf['pass'], $conf['db']);
        $fname = $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/backup/dbbackup-'.date("Y-m-d_H-i-s").'.sql';
        $e->exportValue($fname,false);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($fname).'"'); //<<< Note the " " surrounding the file name
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fname));
        echo(file_get_contents($fname));
        // unlink($fname); TODO: Option to keep on server
        // log data
        Logger::write("Database backed up", "UTIL");
    }

    /**
     * Archive a range of sales records
     */
    function archiveSalesRecords(){
        // TODO
    }

}