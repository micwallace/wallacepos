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

class WposStoredItem extends stdClass {

    public $code = "";
    public $qty = "";
    public $name = "";
    public $alt_name = "";
    public $description = "";
    public $taxid = 1;
    public $price = "";
    public $cost = "";
    public $supplierid = 0;
    public $categoryid = 0;
    public $type = "general";
    public $modifiers = [];

    /**
     * Set any provided data
     * @param $data
     */
    function __construct($data){
        foreach ($data as $key=>$value){
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

}
