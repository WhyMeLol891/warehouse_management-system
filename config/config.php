<?php
/**
 * Warehouse Management System - Configuration File
 */

// Prevent direct script execution outside of application flow
defined('APP_INIT') or define('APP_INIT', true);

// Application Metadata
define('APP_NAME', 'Warehouse Management System');
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'synergy1_derricklim_warehouse_manage');
define('DB_USER', 'synergy1_yenping');
define('DB_PASS', 'R.zb0ZwEuGZ}*fW2');
define('DB_CHARSET', 'utf8mb4');

// System Timezone
date_default_timezone_set('UTC');

/**
 * Base Path Resolution
 * Default is auto-detected from server environment so it works out-of-the-box
 * whether placed in /Warehouse, /warehouse_management_system, or cPanel public_html/
 * You can also set a hardcoded string here if desired, e.g. define('BASE_PATH', '/Warehouse');
 */
if (!defined('BASE_PATH')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Normalize to project root by removing known subdirectories (/admin, /user, /api, /includes, etc.)
    $base = preg_replace('#/(admin|user|api|config|database|includes|assets)(/.*)?$#', '', $scriptDir);
    $base = rtrim($base, '/');
    define('BASE_PATH', $base);
}

// Session Lifetime and Cookie Settings
define('SESSION_LIFETIME', 86400); // 24 hours
