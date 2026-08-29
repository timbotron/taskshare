<?php

namespace Initium;

class DB {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new \Medoo\Medoo([
            'database_type' => 'mysql',
            'database_name' => DB_NAME,
            'server' => DB_SERVER,
            'username' => DB_USER,
            'password' => DB_PASS,
            'charset' => 'utf8',
            //'error' => \PDO::ERRMODE_SILENT, // when live, turn on for handling db issues, see User::create_user()
        ]);

    }

    // Prevent cloning
    private function __clone() { }

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connect() {
        return $this->connection;
    }
}
