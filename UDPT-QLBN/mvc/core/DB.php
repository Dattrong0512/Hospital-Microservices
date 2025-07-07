<?php

//use Dom\Mysql;
$config_path = __DIR__ . "/../../config.inc";
require_once($config_path);

class DB
{
    public $conn;

    function __construct()
    {
        $this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
        mysqli_select_db($this->conn, DB_NAME);
        mysqli_query($this->conn, "SET NAMES 'utf8mb4'");
    }
}
