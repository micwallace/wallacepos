<?php
/**
 * AuthModel is part of Wallace Point of Sale system (WPOS) API
 *
 * AuthModel extends the DbConfig PDO class to interact with the auth DB table
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
 * @author     Adam Jacquier-Parr <aljparr0@gmail.com>, Michael B Wallace <micwallace@gmx.com>
 * @since      Class created 11/23/13 4:47 PM
 */
class AuthModel extends DbConfig
{

    /**
     * @var array available columns in the DB
     */
    protected $_columns = ['id', 'username', 'password', 'uuid', 'admin'];

    /**
     * Initialises the $_db config variable
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $username
     * @param $password
     * @param $isadmin
     * @param $permissions
     * @return bool|int Returns false on an unexpected failure, returns -1 if the a unique constraint in the database fails, or the new rows id and uuid and uuid if the insert is successful
     */
    public function create($username, $password, $isadmin, $permissions)
    {
        $uuid         = uniqid();
        $sql          = "INSERT INTO auth (username, password, uuid, admin, permissions) VALUES (:username,:password,:uuid,:isadmin,:perm)";
        $placeholders = [':username' => $username, ':password' => $password, ':uuid' => $uuid, ':isadmin'=>$isadmin, ':perm'=>$permissions];

        $result = parent::insert($sql, $placeholders);
        if ($result && $result > 0) {
            return ['id' => $result, 'uuid' => $uuid];
        } else {
            return $result;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool   $returnUser
     *
     * @return bool Returns true if the person exists and false otherwise and 0 if the user is diabled, if the $returnUser variable is set to true then it will return the user row on success, and false on failure
     */
    public function login($username, $password, $returnUser = false)
    {
        $sql          = 'SELECT id, username, password AS hash, admin, permissions, disabled FROM auth WHERE (username=:username AND password=:password);';
        $placeholders = ["username"=>$username, "password"=>$password];
        $users        = $this->select($sql, $placeholders);
        if (count($users) > 0) {
            $user = $users[0];
            if ($user['disabled']==1){
                return -1;
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
     * @param null $userId
     * @param null $username
     * @param null $password
     * @param null $disabled
     * @param bool $getAuthValues
     * @return array|bool returns false on failure or an array of users on success
     */
    public function get($userId = null, $username = null, $password = null, $disabled = null, $getAuthValues=false)
    {
        $sql          = 'SELECT id, username, admin, permissions, disabled'.($getAuthValues?', password AS hash, token':'').' FROM auth';
        $placeholders = [];
        if ($userId !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = :id';
            $placeholders[':id'] = $userId;
        }
        if ($username !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' username = :username';
            $placeholders[':username'] = $username;
        }
        if ($password !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' password = :password';
            $placeholders[':password'] = $password;
        }
        if ($disabled !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' disabled = :disabled';
            $placeholders[':disabled'] = $disabled?1:0;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param string      $uuid
     * @param null|string $username
     * @param null|null   $password
     *
     * @return bool|int Returns false on an unexpected failure or the number of rows affected
     */
    public function updateByUuid($uuid, $username = null, $password = null)
    {
        if ($username === null && $password === null) {
            return 0;
        }
        $sql          = 'UPDATE auth SET';
        $placeholders = [];
        if ($username !== null) {
            $sql .= ' username = :username';
            $placeholders[':username'] = $username;
        }
        if ($password !== null) {
            $sql .= ' password = :password';
            $placeholders[':password'] = $password;
        }
        $sql .= ' WHERE uuid = :uuid';
        $placeholders[':uuid'] = $uuid;

        return parent::update($sql, $placeholders);
    }


    /**
     * @param null $id
     * @param null $username
     * @return bool|int Returns false on failure or the number of rows affected
     */
    public function remove($id = null, $username = null)
    {
        if ($id === null && $username === null) { //Do not delete the whole thing ever.
            return 0;
        }
        $sql          = 'DELETE FROM auth';
        $placeholders = [];
        if ($id !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' id = :id';
            $placeholders[':id'] = $id;
        }
        if ($username !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $sql .= ' username = :username';
            $placeholders[':username'] = $username;
        }

        return parent::delete($sql, $placeholders);
    }


    /**
     * @param $id
     * @param null $username
     * @param null $password
     * @param null $isadmin
     * @param null $permissions
     * @param null $disabled
     * @return bool|int Returns false on failure or the number of rows affected
     */
    public function edit($id, $username = null, $password = null, $isadmin = null, $permissions = null, $disabled = null)
    {
        $sql = 'UPDATE auth SET';
        $placeholders = [];
        if ($username !== null) {
            $sql .= ' username= :username';
            $placeholders[':username'] = $username;
        }
        if ($password !== null) {
            if (!empty($placeholders)) $sql.=",";
            $sql .= ' password= :password';
            $placeholders[':password'] = $password;
        }
        if ($disabled !== null) {
            if (!empty($placeholders)) $sql.=",";
            $sql .= ' disabled = :disabled';
            $placeholders[':disabled'] = $disabled;
        }
        if ($isadmin !== null) {
            if (!empty($placeholders)) $sql.=",";
            $sql .= ' admin = :isadmin';
            $placeholders[':isadmin'] = $isadmin;
        }
        if ($permissions !== null) {
            if (!empty($placeholders)) $sql.=",";
            $sql .= ' permissions = :perm';
            $placeholders[':perm'] = $permissions;
        }
        $sql .= ' WHERE id = :id';
        $placeholders[':id'] = $id;

        return $this->update($sql, $placeholders);
    }

    /**
     * Updated auth token for session renewal
     * @param $id
     * @param $token
     * @return bool|int
     */
    public function setAuthToken($id, $token){
        $sql = 'UPDATE auth SET token=:token WHERE id=:id';
        $placeholders = [':id'=>$id, ':token'=>$token];

        return $this->update($sql, $placeholders);
    }

    /**
     * @param $userId
     * @param bool $disable
     * @return bool|int returns false on failure or the number of DB rows affected on success
     */
    public function setDisabled($userId, $disable = true){
        return $this->edit($userId, null, null, null, null, $disable);
    }
} 