<?php

class database
{
    private $dsn  = "mysql:host=localhost;dbname=nafsi_db";
    private $username = "root";
    private $password = "";
    private $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
    );
    public $pdo;
    public function __construct()
    {
        try {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
    }
    public function getConnection() {
        return $this->pdo;
    }
}

$db = new database();
$conn = $db->getConnection();
