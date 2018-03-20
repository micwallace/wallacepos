<?php
/**
 * Test Data is part of Wallace Point of Sale system (WPOS) API
 *
 * Test data is used to generate random sales for testing and demo purposes
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
 * @since      File available since 19/07/14 5:14 PM
 */
class TestData {

    private $items;
    private $users;
    private $devices;
    private $paymentMethods = ['eftpos','credit','cheque','deposit','cash'];
    private $wposSales;

    public function generateTestData($purge=false){
        if ($purge)
            $this->purgeRecords();
        echo("Purged Data and restored.<br/>");
        $this->insertDemoRecords();
        $this->generate(200, 'invoice');
        $this->generate(800);
        echo("Inserted demo transactions.<br/>");
        // remove logs
        if ($purge)
            $this->resetDocuments();
    }

    public function resetDocuments(){
        exec("rm -r ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/*");
        exec("cp -rp ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs-template/* ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs");
        exec("chmod -R 777 ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs");
    }

    public function generate($numtransactions, $type='sale'){
        // get dependant record
        $this->getRecords();
        // set cur time
        $curprocessdt = time() * 1000;
        if (date('D', $curprocessdt)>16){
            $curprocessdt = strtotime(date("Y-m-d", ($curprocessdt/1000))." 17:00:00")*1000;
        }
        $initprocessdt = $curprocessdt;

        for ($i = 0; $i < $numtransactions; $i++) {
            // contruct JSON test data
            $saleobj = new stdClass();
            $saleobj->processdt = $curprocessdt;
            // pick a random device if  pos sale
            if ($type=='sale'){
                $device = $this->devices[rand(0, sizeof($this->devices) - 1)];
                $saleobj->devid = $device['id'];
                $saleobj->locid = $device['locationid'];
            }
            $saleobj->ref = $curprocessdt . "-" . ($type=='sale'?$device['id']:0) . "-" . rand(1000, 9999);
            // pick a random user
            $saleobj->userid = $this->users[rand(0, sizeof($this->users) - 1)]['id'];
            // add misc data
            $saleobj->custid = "";
            $saleobj->custemail = "";
            $saleobj->notes = "";
            $saleobj->discount = 0;
            $saleobj->discountval = 0;
            // add random items
            $numitems = (rand(1, 100)>75?(rand(1, 100)>95?rand(7,10):rand(4,6)):rand(1,3));
            $totalitemqty = 0;
            $total = 0.00;
            $cost = 0.00;
            $totaltax = 0.00;
            $taxes = [];
            $items = [];
            // loop through num items time
            for ($inum=0; $inum<$numitems; $inum++){
                $item = $this->items[rand(0, sizeof($this->items) - 1)];
                // If price is 0 or "" pick a random price
                if ($item['price']=="" || $item['price']==0){
                    $item['price']=rand(1, 100);
                }
                // select random qty and get item total
                $randqty = rand(1, 100);
                $qty = ($randqty>80?($randqty>95?3:2):1);
                $totalitemqty+= $qty;
                $itemtotal = round(($item['price']*$qty), 2);
                $itemcost = round(($item['cost']*$qty), 2);

                // work out tax and add totals
                $itemtax = WposAdminUtilities::calculateTax($item['taxid'], isset($saleobj->locid)?$saleobj->locid:0, $itemtotal);
                if (!$itemtax->inclusive){
                    $itemtotal += $itemtax->total;
                };
                $total+=$itemtotal;
                $cost+=$itemcost;
                $totaltax+= $itemtax->total;
                foreach ($itemtax->values as $key=>$value){
                    if (isset($taxes[$key])){
                        $taxes[$key]+= $value;
                    } else {
                        $taxes[$key]= $value;
                    }
                }

                $itemObj = new stdClass();
                $itemObj->ref=$inum+1;
                $itemObj->sitemid=$item['id'];
                $itemObj->qty=$qty;
                $itemObj->name=$item['name'];
                $itemObj->desc=$item['description'];
                $itemObj->cost=$item['cost'];
                $itemObj->unit=$item['price'];
                $itemObj->taxid=$item['taxid'];
                $itemObj->tax=$itemtax;
                $itemObj->price=$itemtotal;
                $items[] = $itemObj;
            }
            $saleobj->items = $items;
            $subtotal = $total - $totaltax;
            // if method cash round the total & add rounding amount, no cash payments for invoices
            if ($type=='sale'){
                $paymethod = $this->paymentMethods[rand(0, sizeof($this->paymentMethods) -1)];
            } else {
                $paymethod = $this->paymentMethods[rand(0, sizeof($this->paymentMethods) -2)];
            }
            if ($type=='sale' && $paymethod=="cash"){
                // round to nearest five cents
                $temptotal = $total;
                $total = round($total / 0.05) * 0.05;
                $saleobj->rounding = number_format($total - $temptotal , 2, '.', '');
                //if (floatval($saleobj->rounding)!=0)
                    //echo($temptotal." ".$total."<br/>");
            } else {
                $saleobj->rounding = 0.00;
            }
            // add payment to the sale
            if ($type=='sale'){ // leave a few invoices unpaid.
                $payment = new stdClass(); $payment->method=$paymethod; $payment->amount=number_format($total, 2, '.', '');
                if ($paymethod=="cash"){
                    $tender = (round($total)%5 === 0) ? round($total) : round(($total+5/2)/5)*5;
                    $payment->tender=number_format($tender, 2, '.', '');
                    $payment->change=number_format($tender-$total, 2, '.', '');
                }
                $saleobj->payments = [$payment];
            } else if ($type=='invoice'){
                if ($i<2 || $i==60){
                    $saleobj->payments = [];
                } else {
                    $payment = new stdClass(); $payment->method=($paymethod=='cash'?'eftpos':$paymethod); $payment->amount=number_format($total, 2, '.', '');
                    $saleobj->payments = [$payment];

                }
            }

            // add totals and tax
            $saleobj->numitems = $totalitemqty;
            $saleobj->taxdata = $taxes;
            $saleobj->tax = number_format($totaltax, 2, '.', '');
            $saleobj->cost = number_format($cost, 2, '.', '');
            $saleobj->subtotal = number_format($subtotal, 2, '.', '');
            $saleobj->total = number_format($total, 2, '.', '');

            // randomly add a void/refund to the sale
            if ($type=='sale' && (rand(1, 30) == 1)) {
                $voidobj = new stdClass();
                // pick another random device
                $device = $this->devices[rand(0, sizeof($this->devices) - 1)];
                $voidobj->deviceid = $device['id'];
                $voidobj->locationid = $device['locationid'];
                // pick another random user
                $voidobj->userid = $this->users[rand(0, sizeof($this->users) - 1)]['id'];
                // set sometime in the future but do not set before the initial date (now).
                $voidobj->processdt = (($curprocessdt+rand(30, 60*24))>$initprocessdt?$initprocessdt:$curprocessdt+rand(30, 60*24));

                if ((rand(1, 2) == 1)) {
                    // add reason
                    $voidobj->reason = "Faulty Item";
                    // refund, add additional data
                    $voidobj->method = $this->paymentMethods[rand(0, sizeof($this->paymentMethods) - 1)];
                    // pick item to return
                    $retitem = $items[rand(0, sizeof($items) - 1)];
                    $itemdata = new stdClass();
                    $itemdata->numreturned = 1;
                    $itemdata->ref = $retitem->ref;
                    $voidobj->items = [$itemdata];
                    $voidobj->amount = $retitem->unit;
                    // put in array before adding to saleobj
                    $saleobj->refunddata = [$voidobj];
                } else {
                    // add reason
                    $voidobj->reason = "Mistake";
                    // void
                    $saleobj->voiddata = $voidobj;
                }
            }
            // process the sale
            if ($type=='sale'){
                $this->wposSales = new WposPosSale($saleobj);
                $this->wposSales->setNoBroadcast();
                $saleobj->custid = 0;
                $result = $this->wposSales->insertTransaction(["errorCode" => "OK", "error" => "OK", "data" => ""]);
                if ($result['error']!="OK")
                    die("Failed to add devices: ".$result['error']);
            } else {
                // add invoice only fields
                $saleobj->duedt = $curprocessdt + 1209600000;
                $saleobj->custid = rand(1, 2);
                $saleobj->channel = "manual";

                $this->wposSales = new WposInvoices($saleobj, null, true);
                $result = $this->wposSales->createInvoice(["errorCode" => "OK", "error" => "OK", "data" => ""]);
                if ($result['error']!="OK")
                    die("Failed to add devices: ".$result['error']);
            }
            // decrement by a random time between 2-40 minutes
            if ($type=='sale'){
                $curprocessdt = $curprocessdt - (rand(2, 40) * 60 * 1000);
            } else {
                $curprocessdt = $curprocessdt - (rand(40, 280) * 60 * 1000);
            }
            // if it's before shop open time, decrement to the last days closing time.
            $hour = date("H", $curprocessdt/1000);
            if ($hour<9){
                $curprocessdt = strtotime(date("Y-m-d", ($curprocessdt/1000)-86400)." 17:00:00")*1000;
            }
        }
        return;
    }

    private function getRecords()
    {
        // get items
        $itemMdl = new StoredItemsModel();
        $this->items = $itemMdl->get();
        // get items
        $authMdl = new AuthModel();
        $this->users = $authMdl->get(null, null, null, false);
        // get locations
        $devMdl = new WposPosData();
        $this->devices = $devMdl->getPosDevices([])['data'];
    }

    // Purge all records and set demo data
    private function purgeRecords(){
        $dbMdl = new DbConfig();
        $sql = file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/installer/schemas/install.sql");
        if ($sql!=false){
            $dbMdl->_db->exec("TRUNCATE TABLE sales; ALTER TABLE sales AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE sale_items; ALTER TABLE sale_items AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE sale_payments; ALTER TABLE sale_payments AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE sale_voids; ALTER TABLE sale_voids AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE sale_history; ALTER TABLE sale_history AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE stored_items; ALTER TABLE stored_items AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE stored_suppliers; ALTER TABLE stored_suppliers AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE stored_categories; ALTER TABLE stored_categories AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE devices; ALTER TABLE devices AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE device_map; ALTER TABLE device_map AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE locations; ALTER TABLE locations AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE customers; ALTER TABLE customers AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE customer_contacts; ALTER TABLE customer_contacts AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE auth; ALTER TABLE auth AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE config; ALTER TABLE config AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE tax_rules; ALTER TABLE tax_rules AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE tax_items; ALTER TABLE tax_items AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE stock_levels; ALTER TABLE stock_levels AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec("TRUNCATE TABLE stock_history; ALTER TABLE stock_history AUTO_INCREMENT = 1;");
            $dbMdl->_db->exec($sql);
        } else {
            die("Could not import sql.");
        }
    }

    private function insertDemoRecords(){
        $suppliers = json_decode('[{"id": 1, "name":"Joe\'s Fruit&Veg Supplies", "dt":"0"},
                        {"id": 2, "name":"Elecsys Electronic Distibution", "dt":"0"},
                        {"id": 3, "name":"Fitwear Clothing Wholesale", "dt":"0"},
                        {"id": 4, "name":"Yumbox Packaged Goods", "dt":"0"},
                        {"id": 5, "name":"No Place Like Home-warehouse", "dt":"0"}]');

        if ($suppliers==false){
            die("Failed to add suppliers");
        } else {
            $supMdl = new SuppliersModel();
            foreach($suppliers as $supplier){
                $result = $supMdl->create($supplier->name);
                if ($result===false)
                    die("Failed to add suppliers: ".$supMdl->errorInfo);
            }
            echo("Inserted Suppliers.<br/>");
        }

        $items = json_decode('[{"id": 1,"categoryid": 1,"supplierid": 1,"code": "A","qty": 1,"name": "Apple","description": "Golden Delicious","taxid": "2","cost": "0.70","price": "0.99", "type":"general", "modifiers":[]},
                    {"id": 2,"categoryid": 1,"supplierid": 1,"code": "B","qty": 1,"name": "Bannana","description": "Lady Finger","taxid": "2","cost": "2.10","price": "3.00", "type":"general", "modifiers":[]},
                    {"id": 3,"categoryid": 1,"supplierid": 1,"code": "C","qty": 1,"name": "Coconut","description": "","taxid": "2","cost": "2.20","price": "3.00", "type":"general", "modifiers":[]},
                    {"id": 4,"categoryid": 1,"supplierid": 4,"code": "D","qty": 1,"name": "Doritos","description": "","taxid": "2","cost": "1.70","price": "2.50", "type":"general", "modifiers":[]},
                    {"id": 5,"categoryid": 1, "supplierid": 4,"code": "E","qty": 1,"name": "Energy Drink","description": "","taxid": "2","cost": "2.90","price": "3.45", "type":"general", "modifiers":[]},
                    {"id": 6,"categoryid": 1, "supplierid": 4,"code": "F","qty": 1,"name": "Chocolate Fudge","description": "","taxid": "2","cost": "0.95","price": "1.55", "type":"general", "modifiers":[]},
                    {"id": 7,"categoryid": 2, "supplierid": 5,"code": "G","qty": 1,"name": "Gardening Gloves","description": "","taxid": "2","cost": "5.00","price": "8.55", "type":"general", "modifiers":[]},
                    {"id": 8,"categoryid": 2,"supplierid": 5,"code": "H","qty": 1,"name": "Homewares","description": "","taxid": "1","cost": "0.00","price": "", "type":"general", "modifiers":[]},
                    {"id": 9,"categoryid": 1,"supplierid": 4,"code": "I","qty": 1,"name": "Ice Cream","description": "","taxid": "1","cost": "2.80","price": "4.65", "type":"general", "modifiers":[]},
                    {"id": 10,"categoryid": 2,"supplierid": 5,"code": "J","qty": 1,"name": "Jug","description": "","taxid": "1","cost": "5.20","price": "11.00", "type":"general", "modifiers":[]},
                    {"id": 11,"categoryid": 2,"supplierid": 5,"code": "K","qty": 1,"name": "Kettle","description": "","taxid": "1","cost": "9.30","price": "15.00", "type":"general", "modifiers":[]},
                    {"id": 12,"categoryid": 1,"supplierid": 1,"code": "L","qty": 1,"name": "Lime","description": "","taxid": "1","cost": "1.15","price": "2.00", "type":"general", "modifiers":[]},
                    {"id": 13,"categoryid": 4,"supplierid": 3,"code": "M","qty": 1,"name": "Men\'s Clothing","description": "","taxid": "1","cost": "0.00","price": "", "type":"general", "modifiers":[]},
                    {"id": 14,"categoryid": 1,"supplierid": 4,"code": "N","qty": 1,"name": "Nut mix","description": "","taxid": "1","cost": "3.00","price": "4.60", "type":"general", "modifiers":[]},
                    {"id": 15,"categoryid": 1,"supplierid": 1,"code": "O","qty": 1,"name": "Orange","description": "","taxid": "1","cost": "0.85","price": "1.50", "type":"general", "modifiers":[]},
                    {"id": 16,"categoryid": 1,"supplierid": 1,"code": "P","qty": 1,"name": "Pineapple","description": "","taxid": "1","cost": "3.10","price": "4.00", "type":"general", "modifiers":[]},
                    {"id": 17,"categoryid": 1,"supplierid": 1,"code": "Q","qty": 1,"name": "Quince","description": "","taxid": "1","cost": "0.96","price": "1.70", "type":"general", "modifiers":[]},
                    {"id": 18,"categoryid": 1,"supplierid": 4,"code": "R","qty": 1,"name": "Raviolli","description": "","taxid": "1","cost": "3.00","price": "7.35", "type":"general", "modifiers":[]},
                    {"id": 19,"categoryid": 1,"supplierid": 4,"code": "S","qty": 1,"name": "Shapes Pizza","description": "","taxid": "1","cost": "1.20","price": "3.00", "type":"general", "modifiers":[]},
                    {"id": 20,"categoryid": 0,"supplierid": 5,"code": "T","qty": 1,"name": "Toys","description": "","taxid": "1","cost": "0.00","price": "", "type":"general", "modifiers":[]},
                    {"id": 21,"categoryid": 0,"supplierid": 5,"code": "U","qty": 1,"name": "Ukelele","description": "","taxid": "1","cost": "10.10","price": "16.90", "type":"general", "modifiers":[]},
                    {"id": 22,"categoryid": 4,"supplierid": 3,"code": "V","qty": 1,"name": "Vest","description": "","taxid": "1","cost": "35.00","price": "47.00", "type":"general", "modifiers":[]},
                    {"id": 23,"categoryid": 4,"supplierid": 3,"code": "W","qty": 1,"name": "Women\'s Clothing","description": "","taxid": "1","cost": "0.00","price": "", "type":"general", "modifiers":[]},
                    {"id": 24,"categoryid": 0,"supplierid": 5,"code": "X","qty": 1,"name": "Xylophone","description": "","taxid": "1","cost": "135.60","price": "200.50", "type":"general", "modifiers":[]},
                    {"id": 25,"categoryid": 1,"supplierid": 4,"code": "Y","qty": 1,"name": "Yeast","description": "","taxid": "1","cost": "2.00","price": "5.80", "type":"general", "modifiers":[]},
                    {"id": 26,"categoryid": 1,"supplierid": 1,"code": "Z","qty": 1,"name": "Zuccini","description": "","taxid": "1","cost": "0.65","price": "1.10", "type":"general", "modifiers":[]},
                    {"id": 27,"categoryid": 1,"supplierid": 4,"code": "BEER","qty": 1,"name": "Tasman Bitter","description": "375ml bottle","taxid": "1","cost": "1.00","price": "2.20", "type":"general", "modifiers":[]},
                    {"id": 28,"categoryid": 3,"supplierid": 2,"code": "ROBO3D","qty": 1,"name": "Robo 3d Printer","description": "","taxid": "2","cost": "320.00","price": "599.00", "type":"general", "modifiers":[]},
                    {"id": 29,"categoryid": 3,"supplierid": 2,"code": "PS4","qty": 1,"name": "Sony Playstation 4","description": "","taxid": "2","cost": "405.00","price": "600.00", "type":"general", "modifiers":[]},
                    {"id": 30,"categoryid": 3,"supplierid": 2,"code": "XBOX","qty": 1,"name": "Xbox","description": "","taxid": "2","cost": "420.00","price": "600.00", "type":"general", "modifiers":[]}]');

        if ($items==false){
            die("Failed to add items");
        } else {
            $itemMdl = new StoredItemsModel();
            foreach($items as $item){
                $result = $itemMdl->create($item);
                if ($result===false)
                    die("Failed to add items: ".$itemMdl->errorInfo);
            }
            echo("Inserted Items.<br/>");
        }

        $categories = json_decode('[{"id": 1,"name": "Food","dt": "2016-04-18 04:54:21"}, {"id": 2,"name": "Homwares","dt": "2016-04-18 04:54:31"}, {"id": 3,"name": "Electronics","dt": "2016-04-18 04:56:32"}, {"id": 4,"name": "Clothing","dt": "2016-04-18 04:57:01"}]');

        if ($categories==false){
            die("Failed to add categories");
        } else {
            $catMdl = new CategoriesModel();
            foreach($categories as $category){
                $result = $catMdl->create($category->name);
                if ($result===false)
                    die("Failed to add categories: ".$catMdl->errorInfo);
            }
            echo("Inserted Categories.<br/>");
        }

        $locations = json_decode('[{"id": 1, "name":"Sydney", "dt":"1970-01-01 00:00:00"},
                        {"id": 2, "name":"Melbourne", "dt":"1970-01-01 00:00:00"},
                        {"id": 3, "name":"Adelaide", "dt":"1970-01-01 00:00:00"},
                        {"id": 4, "name":"Perth", "dt":"1970-01-01 00:00:00"}]');

        if ($locations==false){
            die("Failed to add locations");
        } else {
            $locMdl = new LocationsModel();
            foreach($locations as $location){
                $result = $locMdl->create($location->name);
                if ($result===false)
                    die("Failed to add locations: ".$locMdl->errorInfo);
            }
            echo("Inserted Locations.<br/>");
        }

        $devices = json_decode('[{"id": 1, "name":"Register 1", "locationid":1, "type":"general_register", "ordertype":"", "orderdisplay":"", "dt":"1970-01-01 00:00:00"},
                        {"id": 2, "name":"Register 2", "locationid":1, "type":"general_register", "ordertype":"", "orderdisplay":"", "dt":"1970-01-01 00:00:00"},
                        {"id": 3, "name":"Register 1", "locationid":2, "type":"general_register", "ordertype":"", "orderdisplay":"", "dt":"1970-01-01 00:00:00"},
                        {"id": 4, "name":"Register 2", "locationid":2, "type":"general_register", "ordertype":"", "orderdisplay":"", "dt":"1970-01-01 00:00:00"},
                        {"id": 5, "name":"Register 1", "locationid":3, "type":"general_register", "ordertype":"", "orderdisplay":"", "dt":"1970-01-01 00:00:00"},
                        {"id": 6, "name":"Register 1", "locationid":4, "type":"general_register", "ordertype":"", "orderdisplay":"", "dt":"1970-01-01 00:00:00"}]');

        if ($devices===false){
            die("Failed to add devices");
        } else {
            $devMdl = new DevicesModel();
            foreach($devices as $device){
                $result = $devMdl->create($device);
                if ($result===false)
                    die("Failed to add devices: ".$devMdl->errorInfo);
            }
            echo("Inserted Devices.<br/>");
        }

        $customers = json_decode('[{"id":1,"name":"Jo Doe", "email":"jdoe@domainname.com", "address":"10 Fake St", "phone":"99999999", "mobile":"111111111", "suburb":"Faketown", "state":"NSW", "postcode":"2000", "country":"Australia", "notes":"", "dt":"1970-01-01 00:00:00"},
                        {"id": 2, "name":"Jane Doe", "email":"jdoe@domainname.com", "address":"10 Fake St", "phone":"99999999", "mobile":"111111111", "suburb":"Faketown", "state":"NSW", "postcode":"2000", "country":"Australia", "notes":"", "dt":"1970-01-01 00:00:00"}]');

        if ($customers===false){
            die("Failed to add customers");
        } else {
            $devMdl = new CustomerModel();
            foreach($customers as $cust){
                $result = $devMdl->create($cust->email, $cust->name, $cust->phone, $cust->mobile, $cust->address, $cust->suburb, $cust->postcode, $cust->state, $cust->country);
                if ($result===false)
                    die("Failed to add customers: ".$devMdl->errorInfo);
            }
            echo("Inserted Customers.<br/>");
        }

    }
}