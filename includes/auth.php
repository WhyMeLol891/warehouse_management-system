<?php
/**
 * Warehouse Management System - Authentication & Authorization
 */

defined('APP_INIT') or define('APP_INIT', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if the user is currently authenticated.
 */
function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Check if the authenticated user has Admin privileges.
 */
function isAdmin(): bool {
    return isLoggedIn() && (strtolower($_SESSION['user_role'] ?? '') === 'admin');
}

/**
 * Check if the authenticated user has User privileges.
 */
function isUser(): bool {
    return isLoggedIn() && (strtolower($_SESSION['user_role'] ?? '') === 'user');
}

/**
 * Get current session user data.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => (int)$_SESSION['user_id'],
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['user_role'] ?? '',
    ];
}

/**
 * Ensure user is logged in, otherwise redirect to login page.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access this page.');
        redirect('/login.php');
    }
}

/**
 * Ensure user is logged in AND has Admin role.
 * If user has role 'user', redirects to user dashboard with access denied error.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Access denied. Administrator privileges required.');
        redirect('/user/dashboard.php');
    }
}

/**
 * Authenticate credentials against database.
 *
 * @param PDO $pdo
 * @param string $username
 * @param string $password
 * @return array|false User record on success, false on failure
 */
function authenticateUser(PDO $pdo, string $username, string $password): array|false {
    $stmt = $pdo->prepare("SELECT id, full_name, username, password_hash, role, is_active FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        return $user;
    }

    return false;
}

/**
 * Process successful login and initialize session.
 */
function loginUser(array $user): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_role'] = strtolower($user['role']);
    $_SESSION['logged_in_time'] = time();

    // Reset CSRF token on new login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Log out user and destroy session completely.
 */
function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
