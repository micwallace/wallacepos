<?php
/**
 * DbUpdater is part of Wallace Point of Sale system (WPOS) API
 *
 * DbUpdater handles incremental upgrades of the database
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
class DbUpdater {
    private $db;

    function __construct(){
        $this->db = new DbConfig();
        header("Content-Type: text/plain");
    }

    public function install(){
        // check install status
        $installed=false;
        try {
            $qres = $this->db->_db->query("SELECT 1 FROM `auth` LIMIT 1");
            if ($qres!==false){
                $installed = true;
            }
            $qres->closeCursor();
        } catch (Exception $ex){
        }
        // Check docs template
        $this->checkStorageTemplate();
        if ($installed){
            return "Database detected, skipping full installation.";
        }
        // Install database
        $schemapath = $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/installer/schemas/install.sql";
        if (!file_exists($schemapath)){
            return "Schema does not exist";
        }
        $sql = file_get_contents($schemapath);
        try {
            $result = $this->db->_db->exec($sql);
            if ($result!==false){
                // use setup var provided in request
                if (isset($_REQUEST['setupvars'])){
                    $setupvars = json_decode($_REQUEST['setupvars']);
                    // set admin hash and disable staff user
                    $authMdl = new AuthModel();
                    $authMdl->setDisabled(2, true);
                    $authMdl->edit(1, null, $setupvars->adminhash);
                    // Setup general info
                    echo("Setup variables processed.\n");
                }

                WposAdminSettings::putValue('general', 'version', $this->getLatestVersionName());

                $socket = new WposSocketControl();
                if (!$socket->isServerRunning())
                    $socket->startSocketServer();

            }
        } catch (Exception $e){
            return $e->getMessage();
        }
        return "Installation Completed!";
    }

    public function checkStorageTemplate(){
        // set permissions
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/logs')==false){
                exec('ROBOCOPY "'.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/." "'.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/" /E');
            }
        } else { //  Assume Linux
            // copy docs template if it doesn't exist
            if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/logs')==false){
                exec('cp -arn "'.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/." "'.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/"');
            }
            // copy static config file from template if it doesn't exist
            if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.config.json')==false)
                copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates/.config.json', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.config.json');
            exec('chmod -R 774 '.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/');
            exec('chmod -R 774 '.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.config.json');
            $socket = new WposSocketIO();
            $socket->generateHashKey();
	    }
    }

    private static $versions = [
        '1.0'=>['name'=>'1.0', 'db'=>true, 'script'=>true],
        '1.1'=>['name'=>'1.1', 'db'=>true, 'script'=>false],
        '1.2'=>['name'=>'1.2', 'db'=>true, 'script'=>true],
        '1.3'=>['name'=>'1.3', 'db'=>true, 'script'=>true],
        '1.4.0'=>['name'=>'1.4.0', 'db'=>true, 'script'=>true],
        '1.4.1'=>['name'=>'1.4.1', 'db'=>true, 'script'=>true],
        '1.4.2'=>['name'=>'1.4.2', 'db'=>false, 'script'=>true],
        '1.4.3'=>['name'=>'1.4.3', 'db'=>true, 'script'=>true]
    ];

    public static function getLatestVersionName(){
        $keys = array_keys(self::$versions);
        return self::$versions[$keys[count($keys) - 1]]['name'];
    }

    public static function getVersionInfo($index){
        if (is_numeric($index))
            $index = array_keys(self::$versions)[$index];
        return self::$versions[$index];
    }

    public function upgrade($version=null, $authneeded=true){
        if ($authneeded){
            $auth = new Auth();
            if (!$auth->isLoggedIn() || !$auth->isAdmin()){
                return "Must be logged in as admin";
            }
        }

        if (!$version) {
            $version = self::getLatestVersionName();
        } else {
            if (!isset(self::$versions[$version])){
                if (!isset($_REQUEST['use_latest']))
                    return "Target version not found";
                $version = self::getLatestVersionName();
            }
        }

        $settings = WposAdminSettings::getSettingsObject('general');
        $cur_version = $settings->version;
        if (!isset($cur_version) || $cur_version==""){
            return "The database has not been installed, use the installer instead";
        }

        if (version_compare($version, $cur_version) < 1){
            return "Already upgraded to version ".$version;
        }

        echo("Backing up database...\n");
        WposAdminUtilities::backUpDatabase(false);

        $keys = array_keys(self::$versions);
        $cur_index = array_search($cur_version, $keys);
        $last_index = array_search($version, $keys);

        echo("Current version is " . $cur_version . "\n");
        echo("Upgrading to version " . $version . "...\n");

        for ($i=$cur_index+1; $i<=$last_index; $i++) {
            $versionInfo = self::getVersionInfo($i);
            echo("Running version " . $versionInfo['name'] . " updates...\n");

            $result = $this->performUpgradeIncrement($versionInfo);
            if ($result !== true) {
                return $result;
            }
        }

        // Send reset request to all online terminals
        $socket = new WposSocketIO();
        $socket->sendResetCommand();

        return "Update completed";
    }

    private function performUpgradeIncrement($versionInfo){

        try {
            if ($versionInfo['db']) {
                echo("Updating database...\n");
                $path = $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/installer/schemas/update".$versionInfo['name'].".sql";
                if (!file_exists($path)){
                    return "Schema does not exist";
                }
                $sql = file_get_contents($path);
                if ($sql) {
                    $result = $this->db->_db->exec($sql);
                    if ($result === false) {
                        return $this->db->_db->errorInfo()[0];
                    }
                }
            }
            if ($versionInfo['script']) {
                echo("Running update script...\n");
                switch ($versionInfo['name']) {
                    case "1.0":
                        $this->upgradeVersion1_0();
                        break;
                    case "1.2":
                        $this->upgradeVersion1_2();
                        break;
                    case "1.3":
                        $this->upgradeVersion1_3();
                        break;
                    case "1.4.0":
                        $this->upgradeVersion1_4_0();
                        break;
                    case "1.4.1":
                        $this->upgradeVersion1_4_1();
                        break;
                    case "1.4.2":
                        $this->upgradeVersion1_4_2();
                        break;
                    case "1.4.3":
                        $this->upgradeVersion1_4_3();
                        break;
                    default:
                        return "Update script referred to in schema but not found.\n";
                }
            }

            WposAdminSettings::putValue('general', 'version', $versionInfo['name']);

            return true;
        } catch (Exception $e){
            return $e->getMessage();
        }
    }

    private function upgradeVersion1_4_1(){
        // set print id setting
        WposAdminSettings::putValue('pos', 'recprintid', false);
        $labels = WposAdminSettings::getSettingsObject('general')->altlabels;
        $labels->{"transaction-id"} = "Transaction ID";
        WposAdminSettings::putValue('general', 'altlabels', $labels);
        return true;
    }

    private function upgradeVersion1_4_2(){
        // set tax setting
        WposAdminSettings::putValue('pos', 'taxedit', 'no');
        WposAdminSettings::putValue('pos', 'recprintdesc', false);
        return true;
    }

    private function upgradeVersion1_4_3(){
        // copy new templates
        copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates/receipt.mustache', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/receipt.mustache');
        copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates/receipt_alt.mustache', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/receipt_alt.mustache');
        copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates/receipt_mixed.mustache', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/receipt_mixed.mustache');
        // extract data for new sale_items fields
        $itemsMdl = new SaleItemsModel();
        $items = $itemsMdl->get();
        foreach ($items as $item){
            $taxData = json_decode($item['tax']);
            $sql = "UPDATE `sale_items` SET `tax_incl`=:tax_incl, `tax_total`=:tax_total WHERE `id`=:id";
            $this->db->update($sql, [":tax_incl"=>($taxData->inclusive ? 1 : 0), ":tax_total"=>$taxData->total, ":id"=>$item['id']]);
        }

        return true;
    }

    private function upgradeVersion1_4_0(){
        WposAdminSettings::putValue('pos', 'negative_items', false);
        // copy static config file if it doesn't exist
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.config.json')==false){
            // copy current version or use template
            if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.config.json')){
                copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.config.json', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.config.json');
            } else {
                copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates/.config.json', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.config.json');
            }
            copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/.htaccess', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/.htaccess');
        }
        $socket = new WposSocketIO();
        $socket->generateHashKey();
    }

    private function upgradeVersion1_3(){
        // set default template values & copy templates
        WposAdminSettings::putValue('pos', 'rectemplate', 'receipt');
        WposAdminSettings::putValue('invoice', 'defaulttemplate', 'invoice');
        if (!file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/templates/"))
            mkdir($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/templates/");
        WposTemplates::restoreDefaults();
        // put alternate language values
        $labels = json_decode('{"cash":"Cash","credit":"Credit","eftpos":"Eftpos","cheque":"Cheque","deposit":"Deposit","tendered":"Tendered","change":"Change","transaction-ref":"Transaction Ref","sale-time":"Sale Time","subtotal":"Subtotal","total":"Total","item":"Item","items":"Items","refund":"Refund","void-transaction":"Void Transaction"}}');
        WposAdminSettings::putValue('general', 'altlabels', $labels);
        // set updated receipt currency symbol support values
        WposAdminSettings::putValue('pos', 'reccurrency', '');
        WposAdminSettings::putValue('pos', 'reccurrency_codepage', 0);

        return true;
    }

    private function upgradeVersion1_2(){
        // update item tax values
        $sql="SELECT * FROM `sale_items`;";
        $items = $this->db->select($sql, []);
        foreach ($items as $item){
            if (is_numeric($item['tax'])){
                $taxdata = new stdClass();
                $taxdata->values = new stdClass();
                $taxdata->inclusive = true;
                if ($item['tax']>0){
                    $taxdata->values->{"1"} = floatval($item['tax']);
                    $taxdata->total = floatval($item['tax']);
                } else {
                    $taxdata->total = 0;
                }
                $sql = "UPDATE `sale_items` SET `tax`=:tax WHERE `id`=:id";
                $this->db->update($sql, [":tax"=>json_encode($taxdata), ":id"=>$item['id']]);
            } else {
                echo("Item record ".$item['id']." already updated, skipping item table update...\n");
            }
        }
        // remove the "notax taxdata field, update gst to id=1"
        $sql="SELECT * FROM `sales`;";
        $sales = $this->db->select($sql, []);
        foreach ($sales as $sale){
            $needsupdate=false;
            $data = json_decode($sale['data']);
            if ($data==false){
                die("Prevented null data entry");
            }
            if (isset($data->taxdata->{"1"}) && $data->taxdata->{"1"}==0){
                if (isset($data->taxdata->{"2"})){
                    $data->taxdata->{"1"} = $data->taxdata->{"2"};
                    unset($data->taxdata->{"2"});
                } else {
                    unset($data->taxdata->{"1"});
                }
                $needsupdate=true;
            } else {
                echo("Record ".$sale['id']." already updated, skipping sale taxdata update...\n");
            }
            foreach($data->items as $skey=>$sitem){
                if (is_numeric($sitem->tax)){
                    $taxdata = new stdClass();
                    $taxdata->values = new stdClass();
                    $taxdata->inclusive = true;
                    if ($sitem->tax>0){
                        $taxdata->values->{"1"} = $sitem->tax;
                        $taxdata->total = $sitem->tax;
                    } else {
                        $taxdata->total = 0;
                    }
                    $data->items[$skey]->tax = $taxdata;
                    $needsupdate=true;
                } else {
                    echo("Item record ".$sale['id']." already updated, skipping sale itemdata update...\n");
                }
            }
            if ($needsupdate){
                $sql = "UPDATE `sales` SET `data`=:data WHERE `id`=:saleid";
                $this->db->update($sql, [":data"=>json_encode($data), ":saleid"=>$sale['id']]);
            }
        }
        // update stored item schema
        $sql="SELECT * FROM `stored_items`;";
        $items = $this->db->select($sql, []);
        $error = false;
        foreach ($items as $item){
            if ($item['data']==""){
                $id = $item['id'];
                unset($item['id']);
                $item['type'] = "general";
                $item['modifiers'] = new stdClass();
                $data = json_encode($item);
                if ($data!=false){
                    $sql = "UPDATE `stored_items` SET `data`=:data WHERE `id`=:id";
                    if (!$this->db->update($sql, [":data"=>$data, ":id"=>$id])) $error = true;
                }
            }
        }
        if (!$error){
            $sql="ALTER TABLE `stored_items` DROP `qty`, DROP `description`, DROP `taxid`;";
            $this->db->update($sql, []);
        }
        // update devices schema
        $sql="SELECT * FROM `devices`;";
        $devices = $this->db->select($sql, []);
        foreach ($devices as $device){
            if ($device['data']==""){
                $data = new stdClass();
                $data->name = $device['name'];
                $data->locationid = $device['locationid'];
                $data->type = "general_register";
                $data->ordertype = "terminal";
                $data->orderdisplay = 1;
                $data->kitchenid = 0;
                $data = json_encode($data);
                if ($data!=false){
                    $sql = "UPDATE `devices` SET `data`=:data WHERE `id`=:id";
                    $this->db->update($sql, [":data"=>$data, ":id"=>$device['id']]);
                }
            } else {
                echo("Device record ".$device['id']." already updated, skipping sale itemdata update...\n");
            }
        }

        WposAdminSettings::putValue('general', 'currencyformat', '$~2~.~,~0');

        return true;
    }

    private function upgradeVersion1_0(){
        // set sales type & channel
        $sql="UPDATE `sales` SET `type`='sale', `channel`='pos';";
        if ($this->db->_db->exec($sql)===false){
            return $this->db->_db->errorInfo()[0];
        }
        // set payment dt to process dt and update sales json with extra params
        $sql="SELECT * FROM `sales`;";
        $sales = $this->db->select($sql, []);
        foreach ($sales as $sale){
            $data = json_decode($sale['data']);
            $data->id = $sale['id'];
            $data->balance = 0.00;
            $data->dt = $sale['dt'];
            $data->status = $sale['status'];
            if ($data==false){
                die("Prevented null data entry");
            }
            $sql = "UPDATE `sales` SET `data`=:data WHERE `id`=:saleid";
            $this->db->update($sql, [":data"=>json_encode($data), ":saleid"=>$sale['id']]);

            $sql = "UPDATE `sale_payments` SET `processdt=:processdt WHERE `saleid`=:saleid";
            $this->db->update($sql, [":processdt"=>$sale['processdt'], ":saleid"=>$sale['id']]);
        }
        // update config, add google keys
        WposAdminSettings::putValue('general', 'gcontact', 0);
        WposAdminSettings::putValue('general', 'gcontacttoken', '');
        WposAdminSettings::putValue('pos', 'priceedit', 'blank');
        // copy new templates
        copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/');

        return true;
    }

}
