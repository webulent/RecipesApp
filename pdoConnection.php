<?php
/**
 * Created by IntelliJ IDEA.
 * User: bulent
 * Date: 11.04.2016
 * Time: 14:52
 */

namespace pd;
use PDO;

class pdoConnection extends PDO
{

    public $con_old;
    public $con_new;

    public function __construct()
    {
        $this->con_old = $this->connect_to_pdo_old();
        $this->con_new = $this->connect_to_pdo_new();

    }

    public function connect_to_pdo_old(){
        try {
            $conn = new PDO('mysql:host=localhost;dbname=yemekdb1;charset=utf8', 'root', '123456', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            $conn->exec("set names utf8");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(\PDOException $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
    public function connect_to_pdo_new(){
        try {
            $conn = new PDO('mysql:host=localhost;dbname=recipes_db;charset=utf8', 'root', '123456', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            $conn->exec("set names utf8");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(\PDOException $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

}