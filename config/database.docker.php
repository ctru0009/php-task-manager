<?php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'task_manager';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASSWORD') ?: '';

            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    public static function getConnection() {
        return self::getInstance();
    }
}
