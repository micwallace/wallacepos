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

    function DbUpdater(){
        $this->db = new DbConfig();
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
            }
        } catch (Exception $e){
            return $e->getMessage();
        }
        return "Setup Completed Successfully!";
    }

    public function checkStorageTemplate(){
        // set permissions
        //exec('chmod -R 777 '.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/');
        // copy docs template if it doesn't exist
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/logs')==false){
           exec('cp -a "'.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/." "'.$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/"');
        }
    }

    public function upgrade($version){
        $auth = new Auth();
        if (!$auth->isLoggedIn() || !$auth->isAdmin()){
            return "Must be logged in as admin";
        }
        $path = $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/installer/schemas/update".$version.".sql";
        if (!file_exists($path)){
            return "Schema does not exist";
        }
        $settings = WposAdminSettings::getSettingsObject('general');
        if (floatval($settings->version)>=floatval($version)){
            return "Db already at the latest version";
        }

        $sql = file_get_contents($path);
        try {
        $result = $this->db->_db->exec($sql);
        if ($result!==false){
            switch ($version){
                case "1.0":
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
                    WposAdminSettings::putValue('general', 'version', '1.0');
                    WposAdminSettings::putValue('general', 'gcontact', 0);
                    WposAdminSettings::putValue('general', 'gcontacttoken', '');
                    WposAdminSettings::putValue('pos', 'priceedit', 'blank');
                    // copy new templates
                    copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs-template/templates', $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'docs/');
                    break;

                case "1.1":
                    WposAdminSettings::putValue('general', 'version', '1.1');
            }
            return true;
        } else {
            return $this->db->_db->errorInfo()[0];
        }
        } catch (Exception $e){
            return $e->getMessage();
        }
    }

    private function execCopy($src, $dst){

    }
}