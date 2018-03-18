<?php
require $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/autoload.php";
use Ifsnop\Mysqldump as IMysqldump;
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
     */
    function __construct($data = null){
        // parse the data and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
    }

    // UTIL FUNCTIONS
    /**
     * Format the provided JS timestamp into the config specified format
     * @param $timestamp
     * @param $dateformat
     * @param bool $includetime
     * @return bool|string
     */
    public static function getDateFromTimeStamp($timestamp, $dateformat= null, $includetime = true){
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
     * Sets the values used for currency formatting
     * @param $format
     */
    public function setCurrencyFormat($format){
        $this->currencyVals = explode('~', $format);
    }

    private $currencyVals = null;
    /**
     * Formats currency for display, includes provided currency symbol or default
     * @param $value
     * @return string
     */
    public function currencyFormat($value){
        if ($this->currencyVals==null){
            $confMdl = new ConfigModel();
            $conf = $confMdl->get('general');
            $this->currencyVals = explode('~', $conf['currencyformat']);
        }
        $formatted = number_format($value, $this->currencyVals[1], $this->currencyVals[2], $this->currencyVals[3]);
        if ($this->currencyVals[4]==0){
            return $this->currencyVals[0].$formatted;
        } else {
            return $formatted.$this->currencyVals[0];
        }
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

    private static $taxTable = null;
    /**
     * Gets tax information as a static variable
     * @return null
     */
    public static function getTaxTable(){
        if (self::$taxTable==null){
            self::$taxTable = WposPosData::getTaxes([])['data'];
        }
        return self::$taxTable;
    }

    /**
     * Calculates tax using the given ruleid, locationid & item total
     * This is used for test data generation only but reflects how tax is calculated in utilities.js
     *
     * @param $taxruleid
     * @param $locationid
     * @param $itemtotal
     * @return stdClass
     */
    public static function calculateTax($taxruleid, $locationid, $itemtotal){
        $tax = new stdClass();
        $tax->total = 0;
        $tax->values = new stdClass();
        $tax->inclusive = true;
        if (!array_key_exists($taxruleid, self::getTaxTable()['rules']))
            return $tax;

        // get the tax rule; taxable total is needed to calculate inclusive tax
        $rule = self::getTaxTable()['rules'][$taxruleid];
        $tax->inclusive = $rule->inclusive;
        $taxitems = self::getTaxTable()['items'];
        $taxablemulti = $rule->inclusive ? self::getTaxableTotal($rule, $locationid) : 0;
        // check in locations, if location rule present get tax totals
        if (isset($rule->locations->{$locationid})){
            foreach ($rule->locations->{$locationid} as $itemkey){
                if (array_key_exists($itemkey, $taxitems)){
                    $tempitem = $taxitems[$itemkey];
                    if (!isset($tax->values->{$tempitem['id']})) $tax->values->{$tempitem['id']} = 0;
                    $tempval = $rule->inclusive ? self::getIncludedTax($tempitem['multiplier'], $taxablemulti, $itemtotal) : round($tempitem['multiplier']*$itemtotal, 2);
                    $tax->values->{$tempitem['id']} += $tempval;
                    $tax->total += $tempval;
                    if ($rule->mode=="single")
                        return $tax;
                }
            }
        }
        // get base tax totals
        foreach ($rule->base as $itemkey){
            if (array_key_exists($itemkey ,$taxitems)){
                $tempitem = $taxitems[$itemkey];
                if (!isset($tax->values->{$tempitem['id']})) $tax->values->{$tempitem['id']} = 0;
                $tempval = $rule->inclusive ? self::getIncludedTax($tempitem['multiplier'], $taxablemulti, $itemtotal) : round($tempitem['multiplier']*$itemtotal, 2);
                $tax->values->{$tempitem['id']} += $tempval;
                $tax->total += $tempval;
                if ($rule->mode=="single")
                    return $tax;
            }
        }
        return $tax;
    }

    /**
     * Gets the total taxable percentage for an item using the given tax rule & location
     * @param $rule
     * @param $locationid
     * @return float
     */
    private static function getTaxableTotal($rule, $locationid){
        $taxitems = self::getTaxTable()['items'];
        $taxable = 0;
        if (isset($rule->locations->{$locationid})){
            foreach ($rule->locations->{$locationid} as $itemkey){
                if (array_key_exists($itemkey, $taxitems)) {
                    $taxable += floatval($taxitems[$itemkey]['multiplier']);
                    if ($rule->mode=="single")
                        round($taxable, 2);
                }
            }
        }
        foreach ($rule->base as $itemkey){
            if (array_key_exists($itemkey , $taxitems)) {
                $taxable += floatval($taxitems[$itemkey]['multiplier']);
                if ($rule->mode=="single")
                    round($taxable, 2);
            }
        }
        return round($taxable, 2);
    }

    /**
     * gets the tax inclusive of the item total
     * @param $multiplier
     * @param $taxablemulti
     * @param $value
     * @return float
     */
    private static function getIncludedTax($multiplier, $taxablemulti, $value){
        $value = floatval($value);
        $taxable = $value-($value/($taxablemulti+1));
        return round(($taxable/$taxablemulti)*$multiplier, 2);
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
     * Get remote address using x-forwarded for if available
     * @return string
     */
    public static function getRemoteAddress(){
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
            return  $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER["REMOTE_ADDR"];
        }else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
    }

    /**
     *  Backup database and init download.
     * @param bool $download
     * @throws Exception
     */
    public static function backUpDatabase($download=true){
        $conf = DbConfig::getConf();
        $dump = new IMysqldump\Mysqldump('mysql:host='.$conf['host'].';dbname='.$conf['db'], $conf['user'], $conf['pass']);
        $fname = $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/backup/dbbackup-'.date("Y-m-d_H-i-s").'.sql';
        $dump->start($fname);
        if ($download) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fname) . '"'); //<<< Note the " " surrounding the file name
            header('Content-Transfer-Encoding: binary');
            header('Connection: Keep-Alive');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fname));
            readfile($fname);
        }
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