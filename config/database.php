<?php


class Database
{
    private static ?PDO $instance = null;

    private const DB_HOST = 'db';
    private const DB_NAME = 'wasmlightroom';
    private const DB_USER = 'wasm_user';
    private const DB_PASS = 'wasm_pass';

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                self::DB_HOST,
                self::DB_NAME
            );

            self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }

    
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}
}
