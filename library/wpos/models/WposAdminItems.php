<?php

/**
 * WposAdminItems is part of Wallace Point of Sale system (WPOS) API
 *
 * WposAdminItems is used to modify administrative items including stored items, suppliers, customers and users.
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
 * @since      File available since 24/12/13 2:05 PM
 */
class WposAdminItems {
    private $data;

    /**
     * Set any provided data
     * @param $data
     * @return $this|bool
     */
    function WposAdminItems($data)
    {
        // parse the data and put it into an object
        if ($data!==false){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
        return $this;
    }
    // STORED ITEMS
    /**
     * Add a stored item into the system
     * @param $result
     * @return mixed
     */
    public function addStoredItem($result)
    {
        // validate input
        $jsonval = new JsonValidate($this->data, '{"code":"","qty":1, "name":"", "taxid":1, "price":-1,"type":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        // create model and check for duplicate stockcode
        $itemMdl = new StoredItemsModel();
        $this->data->code = strtoupper($this->data->code); // make sure stockcode is upper case
        if (sizeof($itemMdl->get(null, $this->data->code)) > 0) {
            $result['error'] = "An item with that stockcode already exists";
            return $result;
        }
        // create the new item
        $qresult = $itemMdl->create($this->data);
        if ($qresult === false) {
            $result['error'] = "Could not add the item: " . $itemMdl->errorInfo;
        } else {
            $this->data->id = $qresult;
            $result['data'] = $this->data;
            // broadcast the item
            $socket = new WposSocketIO();
            $socket->sendItemUpdate($this->data);

            // log data
            Logger::write("Item added with id:" . $this->data->id, "ITEM", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update a stored item
     * @param $result
     * @return mixed
     */
    public function updateStoredItem($result)
    {
        // validate input
        $jsonval = new JsonValidate($this->data, '{"id":1, "code":"", "qty":1, "name":"", "taxid":1, "price":-1}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        // create model and check for duplicate stockcode
        $itemMdl = new StoredItemsModel();
        $this->data->code = strtoupper($this->data->code); // make sure stockcode is upper case
        $dupitems = $itemMdl->get(null, $this->data->code);
        if (sizeof($dupitems) > 0) {
            $dupitem = $dupitems[0];
            if ($dupitem['id'] != $this->data->id) {
                $result['error'] = "An item with that stockcode already exists";
                return $result;
            }
        }
        // update the item
        $qresult = $itemMdl->edit($this->data->id, $this->data);
        if ($qresult === false) {
            $result['error'] = "Could not edit the item";
        } else {
            $result['data'] = $this->data;
            // broadcast the item
            $socket = new WposSocketIO();
            $socket->sendItemUpdate($this->data);

            // log data
            Logger::write("Item updated with id:" . $this->data->id, "ITEM", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Delete a stored item
     * @param $result
     * @return mixed
     */
    public function deleteStoredItem($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // remove the item
        $itemMdl = new StoredItemsModel();
        $qresult = $itemMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the item";
        } else {
            $result['data'] = true;
            // broadcast the item; supplying the id only indicates deletion
            $socket = new WposSocketIO();
            $socket->sendItemUpdate($this->data->id);

            // log data
            Logger::write("Item deleted with id:" . $this->data->id, "ITEM");
        }
        return $result;
    }
    // ITEM CATEGORIES
    /**
     * Add a new category
     * @param $result
     * @return mixed
     */
    public function addCategory($result)
    {
        $jsonval = new JsonValidate($this->data, '{"name":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $supMdl = new CategoriesModel();
        $qresult = $supMdl->create($this->data->name);
        if ($qresult === false) {
            $result['error'] = "Could not add the category";
        } else {
            $result['data'] = $this->getCategoryRecord($qresult);
            // broadcast update
            $socket = new WposSocketIO();
            $socket->sendConfigUpdate('item_categories', $result['data']);
            // log data
            Logger::write("Category added with id:" . $this->data->id, "CATEGORY", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update a category
     * @param $result
     * @return mixed
     */
    public function updateCategory($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "name":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $supMdl = new CategoriesModel();
        $qresult = $supMdl->edit($this->data->id, $this->data->name);
        if ($qresult === false) {
            $result['error'] = "Could not edit the category";
        } else {
            $result['data'] = $this->getCategoryRecord($this->data->id);
            // broadcast update
            $socket = new WposSocketIO();
            $socket->sendConfigUpdate('item_categories', $result['data']);
            // log data
            Logger::write("Category updated with id:" . $this->data->id, "CATEGORY", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Returns category array by ID
     * @param $id
     * @return mixed
     */
    private function getCategoryRecord($id){
        $supMdl = new CategoriesModel();
        $result = $supMdl->get($id)[0];
        return $result;
    }

    /**
     * Delete category
     * @param $result
     * @return mixed
     */
    public function deleteCategory($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $supMdl = new CategoriesModel();
        $qresult = $supMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the category";
        } else {
            $result['data'] = true;
            // broadcast update
            $socket = new WposSocketIO();
            $socket->sendConfigUpdate('item_categories', $this->data->id);
            // log data
            Logger::write("Category deleted with id:" . $this->data->id, "CATEGORY");
        }
        return $result;
    }
    // SUPPLIERS
    /**
     * Add a new supplier
     * @param $result
     * @return mixed
     */
    public function addSupplier($result)
    {
        $jsonval = new JsonValidate($this->data, '{"name":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $supMdl = new SuppliersModel();
        $qresult = $supMdl->create($this->data->name);
        if ($qresult === false) {
            $result['error'] = "Could not add the supplier";
        } else {
            $result['data'] = $this->getSupplierRecord($qresult);
            // log data
            Logger::write("Supplier added with id:" . $this->data->id, "SUPPLIER", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update a supplier
     * @param $result
     * @return mixed
     */
    public function updateSupplier($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "name":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $supMdl = new SuppliersModel();
        $qresult = $supMdl->edit($this->data->id, $this->data->name);
        if ($qresult === false) {
            $result['error'] = "Could not edit the supplier";
        } else {
            $result['data'] = $this->getSupplierRecord($this->data->id);

            // log data
            Logger::write("Supplier updated with id:" . $this->data->id, "SUPPLIER", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Returns supplier array by ID
     * @param $id
     * @return mixed
     */
    private function getSupplierRecord($id){
        $supMdl = new SuppliersModel();
        $result = $supMdl->get($id)[0];
        return $result;
    }

    /**
     * Delete supplier
     * @param $result
     * @return mixed
     */
    public function deleteSupplier($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $supMdl = new SuppliersModel();
        $qresult = $supMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the supplier";
        } else {
            $result['data'] = true;

            // log data
            Logger::write("Supplier deleted with id:" . $this->data->id, "SUPPLIER");
        }
        return $result;
    }
    // USERS
    private $defaultPermissions = [
        "sections" => ['access' => "no", 'dashboard' => "none", 'reports' => 0, 'graph' => 0, 'realtime' => 0, 'sales' => 0, 'items' => 0, 'stock' => 0, 'categories' => 0, 'suppliers' => 0, 'customers' => 0],
        "apicalls" => []
    ];
    /**
     * Maps permissions with their corresponding section name and API actions
     * @var array
     */
    private $permissionMap = [
        "readapicalls" => [
            "dashboard" => ['stats/general', 'stats/takings', 'stats/itemselling', 'stats/locations', 'stats/devices', 'graph/general'],
            "reports" => ['stats/general', 'stats/takings', 'stats/itemselling','stats/categoryselling', 'stats/supplyselling', 'stats/stock', 'stats/devices', 'stats/locations', 'stats/users', 'stats/tax'],
            "graph" => ['graph/general', 'graph/takings', 'graph/devices', 'graph/locations'],
            "realtime" => ['stats/general', 'graph/general'],
            "sales" => [],
            "invoices"=> ['invoices/get'],
            "items" => ['suppliers/get', 'categories/get'],
            "stock" => ['stock/get', 'stock/history'],
            "categories" => ['categories/get'],
            "suppliers" => ['suppliers/get'],
            "customers" => [],
        ],
        "editapicalls" => [
            "dashboard" => [],
            "reports" => [],
            "graph" => [],
            "realtime" => [],
            "sales" => ['sales/delete', 'sales/deletevoid', 'sales/adminvoid'],
            "invoices"=> ['invoices/add' ,'invoices/edit', 'invoices/delete', 'invoices/items/add', 'invoices/items/edit', 'invoices/items/delete',
                'invoices/payments/add','invoices/payments/edit','invoices/payments/delete','invoices/generate','invoices/email'],
            "items" => ['items/add', 'items/edit', 'items/delete'],
            "stock" => ['stock/add', 'stock/set', 'stock/transfer'],
            "categories" => ['categories/add', 'categories/edit', 'categories/delete'],
            "suppliers" => ['suppliers/add', 'suppliers/edit', 'suppliers/delete'],
            "customers" => ['customers/add', 'customers/edit', 'customers/delete', 'customers/contacts/add', 'customers/contacts/edit', 'customers/contacts/delete', 'customers/setaccess', 'customers/setpassword', 'customers/sendreset'],
        ]
    ];

    /**
     * Add user
     * @param $result
     * @return mixed
     */
    public function addUser($result)
    {
        $jsonval = new JsonValidate($this->data, '{"username":"", "pass":"", "admin":1}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        // check for duplicate username
        $authMdl = new AuthModel();
        if (sizeof($authMdl->get(0, 0, null, $this->data->username)) > 0) {
            $result['error'] = "The username specified is already taken";
            return $result;
        }
        // insert entry if the user is admin, preset all permissions
        $qresult = $authMdl->create($this->data->username, $this->data->pass, $this->data->admin, json_encode($this->defaultPermissions));
        if ($qresult === false) {
            $result['error'] = "Could not add the user";
        } else {
            $result['data'] = true;

            // log data
            unset($this->data->pass);
            Logger::write("User added with id:" . $this->data->id, "USER", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update user
     * @param $result
     * @return mixed
     */
    public function updateUser($result)
    {
        // prevent updating of master admin username
        if ($this->data->id == 1 && !isset($this->data->pass)) {
            $result['error'] = "Only the master admin password may be updated.";
            return $result;
        }
        // validate input
        $jsonval = new JsonValidate($this->data, '{"id":1, "username":"", "admin":1}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $authMdl = new AuthModel();
        if ($this->data->id == 1) {
            // Only rhe admin users password can be updated
            $qresult = $authMdl->edit($this->data->id, $this->data->username, $this->data->pass);
            unset($this->data->permissions);
            unset($this->data->admin);

        } else {

            $dupitems = $authMdl->get(0, 0, null, $this->data->username);
            if (sizeof($dupitems) > 0) {
                $dupitem = $dupitems[0];
                if ($dupitem['id'] != $this->data->id) {
                    $result['error'] = "The username specified is already taken";
                    return $result;
                }
            }
            // generate permissions object
            $permObj = [
                "sections" => $this->data->permissions,
                "apicalls" => []
            ];
            foreach ($this->data->permissions as $key => $value) {
                switch ($key) {
                    case "access";
                        if ($value != "no") {
                            $permObj['apicalls'][] = "adminconfig/get";
                        }
                        break;
                    case "dashboard";
                        if ($value == "both" || $value == "standard") {
                            $permObj['apicalls'] = array_merge($permObj['apicalls'], $this->permissionMap['readapicalls']['dashboard']);
                        }
                        if ($value == "both" || $value == "realtime") {
                            $permObj['apicalls'] = array_merge($permObj['apicalls'], $this->permissionMap['readapicalls']['realtime']);
                        }
                        break;
                    default:
                        switch ($value) {
                            case 2:
                                // add write api calls
                                if (isset($this->permissionMap['editapicalls'][$key])) {
                                    $permObj['apicalls'] = array_merge($permObj['apicalls'], $this->permissionMap['editapicalls'][$key]);
                                }
                            case 1:
                                // add read api calls
                                if (isset($this->permissionMap['readapicalls'][$key])) {
                                    $permObj['apicalls'] = array_merge($permObj['apicalls'], $this->permissionMap['readapicalls'][$key]);
                                }
                                break;
                        }
                }
            }
            if ($this->data->pass == "") {
                $qresult = $authMdl->edit($this->data->id, $this->data->username, null, $this->data->admin, json_encode($permObj));
            } else {
                $qresult = $authMdl->edit($this->data->id, $this->data->username, $this->data->pass, $this->data->admin, json_encode($permObj));
            }
        }
        if ($qresult === false) {
            $result['error'] = "Could not update the user";
        } else {
            $result['data'] = true;

            // log data
            unset($this->data->pass);
            Logger::write("User updated with id:" . $this->data->id, "USER", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Delete user
     * @param $result
     * @return mixed
     */
    public function deleteUser($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $authMdl = new AuthModel();
        $qresult = $authMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the user";
        } else {
            $result['data'] = true;

            // log data
            Logger::write("User deleted with id:" . $this->data->id, "USER");
        }
        return $result;
    }

    /**
     * Set user disabled
     * @param $result
     * @return mixed
     */
    public function setUserDisabled($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // prevent updating of master admin username
        if ($this->data->id == 1 && !isset($this->data->pass)) {
            $result['error'] = "The master admin user cannot be disabled";
            return $result;
        }
        $userMdl = new AuthModel();
        if ($userMdl->setDisabled($this->data->id, boolval($this->data->disable)) === false) {
            $result['error'] = "Could not enable/disable the user";
        }

        // log data
        Logger::write("User " . ($this->data->disable == true ? "disabled" : "enabled") . " with id:" . $this->data->id, "USER");

        return $result;
    }
    // Tax items
    /**
     * Add a new tax rule
     * @param $result
     * @return mixed
     */
    public function addTaxRule($result)
    {
        $jsonval = new JsonValidate($this->data, '{"name":"", "inclusive":true, "base":"", "locations":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $taxRuleMdl = new TaxRulesModel();
        $qresult = $taxRuleMdl->create($this->data);
        if ($qresult === false) {
            $result['error'] = "Could not add the tax rule: ".$taxRuleMdl->errorInfo;
        } else {
            $this->data->id = $qresult;
            $result['data'] = $this->data;
            $this->broadcastTaxUpdate();
            // log data
            Logger::write("Tax rule added with id:" . $this->data->id, "TAX", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update a tax rule
     * @param $result
     * @return mixed
     */
    public function updateTaxRule($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "name":"", "inclusive":true, "base":"", "locations":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        if ($this->data->id==1){
            $result['error'] = "The No Tax rule cannot be edited";
            return $result;
        }
        $taxRuleMdl = new TaxRulesModel();
        $qresult = $taxRuleMdl->edit($this->data->id, $this->data);
        if ($qresult === false) {
            $result['error'] = "Could not edit the tax rule: ".$taxRuleMdl->errorInfo;
        } else {
            $result['data'] = $this->data;
            $this->broadcastTaxUpdate();
            // log data
            Logger::write("Tax rule updated with id:" . $this->data->id, "TAX", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Delete a tax rule
     * @param $result
     * @return mixed
     */
    public function deleteTaxRule($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        if ($this->data->id==1){
            $result['error'] = "The No Tax rule cannot be deleted";
            return $result;
        }
        $taxRuleMdl = new TaxRulesModel();
        $qresult = $taxRuleMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the tax rule: ".$taxRuleMdl->errorInfo;
        } else {
            $result['data'] = true;
            $this->broadcastTaxUpdate();
            // log data
            Logger::write("Tax rule deleted with id:" . $this->data->id, "TAX");
        }
        return $result;
    }

    /**
     * @param $value
     * @return float
     */
    public static function calculateTaxMultiplier($value){
        return ($value/100);
    }
    /**
     * Add a new tax rule
     * @param $result
     * @return mixed
     */
    public function addTaxItem($result)
    {
        $jsonval = new JsonValidate($this->data, '{"name":"", "type":"", "value":1}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $this->data->multiplier = WposAdminItems::calculateTaxMultiplier($this->data->value);
        $taxItemMdl = new TaxItemsModel();
        $qresult = $taxItemMdl->create($this->data->name, $this->data->altname, $this->data->type, $this->data->value, $this->data->multiplier);
        if ($qresult === false) {
            $result['error'] = "Could not add the tax item: ".$taxItemMdl->errorInfo;
        } else {
            $this->data->id = $qresult;
            $result['data'] = $this->data;
            $this->broadcastTaxUpdate();
            // log data
            Logger::write("Tax item added with id:" . $this->data->id, "TAX", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update a tax rule
     * @param $result
     * @return mixed
     */
    public function updateTaxItem($result)
    {
        $jsonval = new JsonValidate($this->data, '{"name":"", "type":"", "value":1}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $this->data->multiplier = WposAdminItems::calculateTaxMultiplier($this->data->value);
        $taxItemMdl = new TaxItemsModel();
        $qresult = $taxItemMdl->edit($this->data->id, $this->data->name, $this->data->altname, $this->data->type, $this->data->value, $this->data->multiplier);
        if ($qresult === false) {
            $result['error'] = "Could not edit the tax item: ".$taxItemMdl->errorInfo;
        } else {
            $result['data'] = $this->data;
            $this->broadcastTaxUpdate();
            // log data
            Logger::write("Tax item updated with id:" . $this->data->id, "TAX", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Delete a tax rule
     * @param $result
     * @return mixed
     */
    public function deleteTaxItem($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $taxItemMdl = new TaxItemsModel();
        $qresult = $taxItemMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the tax item: ".$taxItemMdl->errorInfo;
        } else {
            $result['data'] = true;
            $this->broadcastTaxUpdate();
            // log data
            Logger::write("Tax item deleted with id:" . $this->data->id, "TAX");
        }
        return $result;
    }

    private function broadcastTaxUpdate(){
        $taxconfig = WposPosData::getTaxes();
        if (!isset($taxconfig['error'])){
            $socket = new WposSocketIO();
            $socket->sendConfigUpdate($taxconfig['data'], "tax");
        }
    }
}