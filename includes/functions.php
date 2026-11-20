<?php
/**
 * Warehouse Management System - Helper Functions
 */

defined('APP_INIT') or define('APP_INIT', true);

/**
 * Escape output for safe HTML display.
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a complete application URL based on BASE_PATH.
 */
function url(string $path = ''): string {
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    $path = '/' . ltrim($path, '/');
    if ($path === '/' && !empty($base)) {
        return $base;
    }
    return rtrim($base, '/') . $path;
}

/**
 * Redirect to an application URL and terminate script.
 */
function redirect(string $path): void {
    $destination = (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
        ? $path
        : url($path);

    header('Location: ' . $destination);
    exit;
}

/**
 * Sanitize text input.
 */
function sanitize(?string $value): string {
    return trim((string)($value ?? ''));
}

/**
 * Format date for consistent display.
 */
function formatDate(?string $dateStr, string $format = 'Y-m-d'): string {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : '-';
}

/**
 * Format datetime for consistent display.
 */
function formatDateTime(?string $dateStr, string $format = 'Y-m-d H:i'): string {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : '-';
}

/**
 * Format number for clean display.
 */
function formatNumber(int|float $num): string {
    return number_format($num);
}

/**
 * Set a session flash message.
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger'/'error', 'warning', 'info'
        'text' => $message,
    ];
}

/**
 * Retrieve and clear session flash message.
 */
function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Generate or get existing CSRF token.
 */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden CSRF token input field.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(getCsrfToken()) . '">';
}

/**
 * Verify submitted CSRF token.
 */
function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Retrieve list of all unique categories from items table.
 */
function getItemCategories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
