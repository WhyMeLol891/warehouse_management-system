<?php
/**
 * Warehouse Management System - Database Connection (PDO)
 */

defined('APP_INIT') or define('APP_INIT', true);

require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO database instance.
 *
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Clean error response avoiding exposing sensitive credentials
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failure. Please verify database settings in config/config.php and ensure MySQL is running.');
        }
    }

    return $pdo;
}
