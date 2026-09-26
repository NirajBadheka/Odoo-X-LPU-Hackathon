<?php
/**
 * StockSense - Database Connection
 * MySQLi Object-Oriented, prepared statements throughout the app.
 */

class Database
{
    private static ?mysqli $instance = null;

    // Update these to match your XAMPP MySQL credentials
    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'stocksense';
    private const DB_PORT = 3306;

    public static function getConnection(): mysqli
    {
        if (self::$instance === null) {
            mysqli_report(MYSQLI_REPORT_OFF); // we handle errors manually (no info leakage)

            $conn = @new mysqli(
                self::DB_HOST,
                self::DB_USER,
                self::DB_PASS,
                self::DB_NAME,
                self::DB_PORT
            );

            if ($conn->connect_error) {
                error_log('Database connection failed: ' . $conn->connect_error);
                http_response_code(500);
                die('Service temporarily unavailable. Please try again later.');
            }

            $conn->set_charset('utf8mb4');
            self::$instance = $conn;
        }

        return self::$instance;
    }
}

// Convenience global for procedural-style pages
function db(): mysqli
{
    return Database::getConnection();
}
