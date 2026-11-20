<?php
/**
 * Warehouse Management System - Global Initialization
 */

defined('APP_INIT') or define('APP_INIT', true);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        // Basic session cookie security parameters
        session_set_cookie_params([
            'lifetime' => 86400,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    } else {
        @session_start();
    }
}

// Require dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Obtain shared database connection
$pdo = getDBConnection();
