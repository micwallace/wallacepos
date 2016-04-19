<?php
/**
 * DbConfig is part of Wallace Point of Sale system (WPOS) API
 *
 * DbConfig is the main PDO class. It is extended by all the *Model.php classes to interact with DB tables.
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
 * @since      Class created 11/20/13 11:17 PM
 */
class DbConfig
{
    /**
     *  This is the PDO Error code for a duplicate insertion error
     */
    const ERROR_DUPLICATE = '23000';
    /**
     * @var string Username to login to database, could probably be fetched from a config file instead but w/e
     */
    private static $_username = '';
    /**
     * @var string
     */
    private static $_password = '';
    /**
     * @var string
     */
    private static $_database = '';
    /**
     * @var string
     */
    private static $_hostname = 'localhost';
    /**
     * @var string
     */
    private static $_port = '3306';
    /**
     * @var string
     */
    private static $_dsnPrefix = 'mysql';
    /**
     * @var
     */
    private static $_unixSocket;
    /**
     * @var boolean used by installer for testing config values
     */
    private static $_loadConfig = true;
    /**
     * @var PDO
     */
    public $_db;

    public $errorInfo;

    /**
     * Checks the application environment variable and sets the username, password, database and hostname.
     * Creates a connection to the database
     *
     * @throws PDOException Throws a PDOException when it fails to create a database connection.
     */
    public function __construct()
    {
        if (self::$_loadConfig)
            $this->getConf();

        $dsn = self::$_dsnPrefix . ':host=' . self::$_hostname . ';port=' . self::$_port . ';dbname=' . self::$_database;

        try {
            if (!$this->_db = new \PDO($dsn, self::$_username, self::$_password)){
                throw new PDOException('Failed to connect to database, php PDO extension may not be installed', 0, 0);
            }

            $this->_db->query("SET time_zone = '+00:00'"); //Set timezone to GMT, previous statement didnt work (Australia/Sydney), and GMT preserved daylight savings.
            //var_dump($this->_db->query("SELECT now()")->fetchAll());exit;
            //var_dump($this->_db->query("SELECT @@session.time_zone, @@global.time_zone")->fetchAll(PDO::FETCH_ASSOC));exit;
            $this->_db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        } catch (PDOException $e) {
            throw new PDOException('Failed to connect to database: '.$e->getMessage(), 0, $e);
        }

    }

    /**
     * Returns the appropriate database configuration
     * @return array
     */
    static function getConf(){

        if (($url=getenv("DATABASE_URL"))!==false) {
            // dokku / heroku
            $url=parse_url($url);
            self::$_username = $url['user'];
            self::$_password = $url['pass'];
            self::$_database = substr($url["path"],1);
            self::$_hostname = $url['host'];
            self::$_port = $url["port"];
        } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/dbconfig.php')){
            // legacy config (still used for alpha/demo versions)
            require($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/dbconfig.php');
            self::$_username = $dbConfig['user'];
            self::$_password = $dbConfig['pass'];
            self::$_database = $dbConfig["database"];
            self::$_hostname = $dbConfig['host'];
            self::$_port = $dbConfig["port"];
        } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/.dbconfig.json')){
            // json config
            $dbConfig = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT'].'library/wpos/.dbconfig.json'), true);
            self::$_username = $dbConfig['user'];
            self::$_password = $dbConfig['pass'];
            self::$_database = $dbConfig["database"];
            self::$_hostname = $dbConfig['host'];
            self::$_port = $dbConfig["port"];
        }

        $conf = ["host"=>self::$_hostname, "port"=>self::$_port, "user"=>self::$_username, "pass"=>self::$_password, "db"=>self::$_database,];
        return $conf;
    }

    public static function testConf($host, $port, $database, $user, $pass){
        self::$_username = $user;
        self::$_password = $pass;
        self::$_database = $database;
        self::$_hostname = $host;
        self::$_port = $port;
        self::$_loadConfig = false; // prevent config from being loaded, used for testing database connection
        try {
            $db = new DbConfig();
        } catch (Exception $ex){
            self::$_loadConfig = true;
            return $ex->getMessage();
        }
        self::$_loadConfig = true;
        return true;
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function insert($sql, $placeholders = null)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)){
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
                    }
                }
            }
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }
            return $this->_db->lastInsertId();
        } catch (PDOException $e) {
            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     *
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the delete operation
     */
    public function delete($sql, $placeholders = null)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)){
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
                    }
                }
            }

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            return $stmt->rowCount();
        } catch (PDOException $e) {

            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     *
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function update($sql, $placeholders = null)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)){
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
                    }
                }
            }
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            return $stmt->rowCount();
        } catch (PDOException $e) {

            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     * @param int        $fetchStyle
     *
     * @return bool|array Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function select($sql, $placeholders = null, $fetchStyle = PDO::FETCH_ASSOC)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)){
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
                    }
                }
            }

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: ".$errorInfo[0]." (". $errorInfo[0] .")", 0);
            }

            return $stmt->fetchAll($fetchStyle);
        } catch (PDOException $e) {

            $this->errorInfo = $e->getMessage();
            return false;
        }
    }
} 