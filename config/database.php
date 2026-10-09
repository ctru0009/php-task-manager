<?php

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $host = self::env('DB_HOST');
        $name = self::env('DB_NAME');
        $user = self::env('DB_USER');
        $password = self::env('DB_PASSWORD');

        try {
            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $password);
            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION,
            );
            $this->connection->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC,
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Service unavailable: database connection failed.');
        }
    }

    private static function env($name)
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            error_log('Missing required environment variable: ' . $name);
            http_response_code(500);
            exit('Service unavailable: missing required configuration (' . $name . ').');
        }

        return $value;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    public static function getConnection()
    {
        return self::getInstance();
    }
}
