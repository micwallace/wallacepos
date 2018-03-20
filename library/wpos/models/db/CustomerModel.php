<?php
/**
 * ConfigModel is part of Wallace Point of Sale system (WPOS) API
 *
 * ConfigModel extends the DbConfig PDO class to interact with the config DB table
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
 * @since      Class created 11/23/13 10:36 PM
 */

class CustomerModel extends DbConfig
{

    /**
     * @var array of available DB columns
     */
    protected $_columns = ['id', 'email', 'name', 'phone', 'mobile', 'address', 'suburb', 'postcode', 'country', 'dt'];

    /**
     *  Initialize the DB object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $email
     * @param $name
     * @param $phone
     * @param $mobile
     * @param $address
     * @param $suburb
     * @param $postcode
     * @param $state
     * @param $country
     * @param string $gid
     * @return bool|string eturns false on an unexpected failure or the inserted row ID
     */
    public function create($email, $name, $phone, $mobile, $address, $suburb, $postcode, $state, $country, $gid='')
    {

        $sql = "INSERT INTO customers (email, name, phone, mobile, address, suburb, postcode, state, country, googleid, dt) VALUES (:email, :name, :phone, :mobile, :address, :suburb, :postcode, :state, :country, :googleid, '".date("Y-m-d H:i:s")."')";
        $placeholders = [":email"=>$email, ":name"=>$name, ":phone"=>$phone, ":mobile"=>$mobile, ":address"=>$address, ":suburb"=>$suburb, ":postcode"=>intval($postcode), ":state"=>$state, ":country"=>$country, ":googleid"=>$gid];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param $customerid
     * @param $email
     * @param $name
     * @param $phone
     * @param $mobile
     * @param $position
     * @param $recinv
     * @return bool|string eturns false on an unexpected failure or the inserted row ID
     */
    public function createContact($customerid, $email, $name, $phone, $mobile, $position, $recinv)
    {
        $sql = "INSERT INTO customer_contacts (customerid, email, name, phone, mobile, position, receivesinv) VALUES (:custid, :email, :name, :phone, :mobile, :position, :recinv)";
        $placeholders = [":custid"=>$customerid, ":email"=>$email, ":name"=>$name, ":phone"=>$phone, ":mobile"=>$mobile, ":position"=>$position, ":recinv"=>$recinv];
        // removes any default flag on current contacts
        if ($recinv==1) $this->setDefaultContact(0);
        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $customerId
     * @param null $email
     * @param int $limit
     * @param int $offset
     * @return array|bool Returns false on an unexpected failure or an array of selected customers
     */
    public function get($customerId = null, $email = null, $limit = 0, $offset = 0)
    {
        $sql          = 'SELECT * FROM customers';
        $placeholders = [];
        if ($customerId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = '.$customerId;
            $placeholders[] = $customerId;
        }
        if ($email !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' email = '.$email;
            $placeholders[] = $email;
        }
        if ($limit !== 0 && is_int($limit)) {
            $sql .= ' LIMIT :limit';
            $placeholders[':limit'] = $limit;
        }
        if ($offset !== 0 && is_int($offset)) {
            $sql .= ' OFFSET :offset';
            $placeholders[':offset'] = $offset;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param null $custId
     * @param null $contactId
     * @param null $email
     * @param int $limit
     * @param int $offset
     * @return array|bool Returns false on an unexpected failure or an array of selected customers
     */
    public function getContacts($custId=null, $contactId = null, $email = null, $limit = 0, $offset = 0)
    {
        $sql          = 'SELECT * FROM customer_contacts';
        $placeholders = [];
        if ($contactId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = '.$contactId;
            $placeholders[] = $contactId;
        }
        if ($custId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' customerid = '.$custId;
            $placeholders[] = $custId;
        }
        if ($email !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' email = '.$email;
            $placeholders[] = $email;
        }
        if ($limit !== 0 && is_int($limit)) {
            $sql .= ' LIMIT :limit';
            $placeholders[':limit'] = $limit;
        }
        if ($offset !== 0 && is_int($offset)) {
            $sql .= ' OFFSET :offset';
            $placeholders[':offset'] = $offset;
        }


        return $this->select($sql, $placeholders);
    }

    /**
     * @param $id
     * @param $email
     * @param $name
     * @param $phone
     * @param $mobile
     * @param $address
     * @param $suburb
     * @param $postcode
     * @param $state
     * @param $country
     * @param $notes
     * @param string $gid
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function edit($id, $email, $name, $phone, $mobile, $address, $suburb, $postcode, $state, $country, $notes=null, $gid=null)
    {

        $sql = "UPDATE customers SET email= :email, name= :name, phone= :phone, mobile= :mobile, address= :address, suburb= :suburb, postcode= :postcode, state= :state, country= :country";
        $placeholders = [":id"=>$id, ":email"=>$email, ":name"=>$name, ":phone"=>$phone, ":mobile"=>$mobile, ":address"=>$address, ":suburb"=>$suburb, ":postcode"=>$postcode, ":state"=>$state, ":country"=>$country];
        if ($notes!==null){
            $sql.= ", notes= :notes";
            $placeholders[':notes'] = $notes;
        }
        if ($gid!==null){
            $sql.= ", googleid= :googleid";
            $placeholders[':googleid'] = $gid;
        }
        $sql.= " WHERE id= :id";

        return $this->update($sql, $placeholders);
    }

    /**
     * Set authentication info for a given customer
     * @param $id
     * @param null $hash
     * @param null $activated
     * @param null $disabled
     * @return bool|int
     */
    public function editAuth($id, $hash=null, $activated=null, $disabled=null){
        $sql = "UPDATE customers SET";
        $placeholders = [":id"=>$id];
        if ($hash!==null){
            $sql.= " pass= :hash";
            $placeholders[':hash'] = $hash;
        }
        if ($disabled!==null){
            if ($hash!==null) $sql.= ",";
            $sql.= " disabled= :disabled";
            $placeholders[':disabled'] = $disabled;
        }
        if ($activated!==null){
            if ($hash!==null || $disabled!==null) $sql.= ",";
            $sql.= " activated= :activated";
            $placeholders[':activated'] = $activated;
        }
        $sql.= " WHERE id= :id";

        return $this->update($sql, $placeholders);
    }

    /**
     * Set auth token for the given customer
     * @param $id
     * @param $token
     * @return bool|int
     */
    public function setAuthToken($id, $token){
        $sql = "UPDATE customers SET token= :token WHERE id= :id";
        $placeholders = [":id"=>$id, ":token"=>$token];

        return $this->update($sql, $placeholders);
    }

    /**
     * Activate & clear token on the record with the given token
     * @param $token
     * @return bool|int
     */
    public function tokenActivate($token){
        $sql = "UPDATE customers SET activated=1, token='' WHERE token= :token";
        $placeholders = [":token"=>$token];

        return $this->update($sql, $placeholders);
    }

    /**
     * Set Password for the customer with the given token
     * @param $token
     * @param $hash
     * @return bool|int
     */
    public function tokenReset($token, $hash){
        $sql = "UPDATE customers SET pass=:pass, token='' WHERE token= :token";
        $placeholders = [":token"=>$token, ":pass"=>$hash];

        return $this->update($sql, $placeholders);
    }

    /**
     * Authenticate provided user credentials
     * @param $username
     * @param $hash
     * @param bool $returnUser
     * @return bool|int
     */
    public function login($username, $hash, $returnUser = true){
        $sql          = 'SELECT * FROM customers WHERE email= :email AND pass= :hash;';
        $placeholders = [':email'=>$username, ':hash'=>$hash];
        $users = $this->select($sql, $placeholders);
        if (count($users) > 0) {
            $user = $users[0];
            if ($user['disabled']==1){
                return -1;
            }
            if ($user['activated']==0){
                return -2;
            }
            if ($returnUser === true) {
                return $user;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $id
     * @param $email
     * @param $name
     * @param $phone
     * @param $mobile
     * @param $position
     * @param $recinv
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function editContact($id, $email, $name, $phone, $mobile, $position, $recinv)
    {

        $sql = "UPDATE customer_contacts SET email= :email, name= :name, phone= :phone, mobile= :mobile, position= :position, receivesinv= :recinv WHERE id= :id";
        $placeholders = [":id"=>$id, ":email"=>$email, ":name"=>$name, ":phone"=>$phone, ":mobile"=>$mobile, ":position"=>$position, ":recinv"=>$recinv];
        if ($recinv==1) $this->setDefaultContact($id);
        return $this->update($sql, $placeholders);
    }

    /**
     * Set contact default
     * @param $id
     * @return bool|int
     */
    public function setDefaultContact($id){
        $sql = "UPDATE customer_contacts SET receivesinv=0";$this->update($sql, []);
        $result = $this->update($sql, []);
        if ($id!=0 && $result!=false){
            $sql = "UPDATE customer_contacts SET receivesinv=1 WHERE id= :id;";
            return $this->update($sql, [":id"=>$id]);
        } else {
            return $result;
        }
    }

    /**
     * @param null $id
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the delete operation
     */
    public function remove($id)
    {
        if ($id === null) {
            return false;
        }
        // Remove contacts
        $sql = "DELETE FROM customer_contacts WHERE customerid= :id";
        $placeholders = [":id"=>$id];
        if (($result = $this->delete($sql, $placeholders))===false){
            return $result;
        }

        $sql = "DELETE FROM customers WHERE id= :id";

        return $this->delete($sql, $placeholders);
    }

    /**
     * @param null $id
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the delete operation
     */
    public function removeContact($id)
    {
        if ($id === null) {
            return false;
        }
        $sql = "DELETE FROM customer_contacts WHERE id= :id";
        $placeholders = [":id"=>$id];

        return $this->delete($sql, $placeholders);
    }

}