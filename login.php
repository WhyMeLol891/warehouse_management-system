<?php
/**
 * Warehouse Management System - Login
 */

require_once __DIR__ . '/includes/init.php';

// Redirect if already authenticated
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
}

$error = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = sanitize($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (empty($usernameVal) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $user = authenticateUser($pdo, $usernameVal, $password);
        if ($user) {
            loginUser($user);
            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
            if (strtolower($user['role']) === 'admin') {
                redirect('/admin/dashboard.php');
            } else {
                redirect('/user/dashboard.php');
            }
        } else {
            $error = 'Invalid username or password. Please verify your credentials.';
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/style.css'); ?>">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-header">
        <div class="login-logo">W</div>
        <h1>Warehouse System</h1>
        <p>Sign in to manage inventory &amp; stock movements</p>
    </div>

    <div class="login-body-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= ($flash['type'] === 'danger' || $flash['type'] === 'error') ? 'danger' : e($flash['type']); ?>">
                <div class="alert-content"><?= e($flash['text']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <div class="alert-content"><?= e($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login.php'); ?>" autocomplete="off">
            <?= csrfField(); ?>

            <div class="form-group">
                <label for="loginUsername" class="form-label">Username <span class="required">*</span></label>
                <input type="text" 
                       id="loginUsername" 
                       name="username" 
                       class="form-control" 
                       value="<?= e($usernameVal); ?>" 
                       required 
                       autofocus 
                       placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label for="loginPassword" class="form-label">Password <span class="required">*</span></label>
                <input type="password" 
                       id="loginPassword" 
                       name="password" 
                       class="form-control" 
                       required 
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
                Sign In &rarr;
            </button>
        </form>

        <div class="login-demo-box">
            <div class="login-demo-title">Default Demo Credentials:</div>
            <div class="demo-account-row">
                <span><strong>Admin:</strong></span>
                <span>
                    <span class="demo-badge-click" onclick="fillDemoLogin('admin', 'admin123')">admin</span> / 
                    <span class="demo-badge-click" onclick="fillDemoLogin('admin', 'admin123')">admin123</span>
                </span>
            </div>
            <div class="demo-account-row">
                <span><strong>User:</strong></span>
                <span>
                    <span class="demo-badge-click" onclick="fillDemoLogin('demo', 'user123')">demo</span> / 
                    <span class="demo-badge-click" onclick="fillDemoLogin('demo', 'user123')">user123</span>
                </span>
            </div>
            <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 6px;">(Click credentials above to auto-fill)</div>
        </div>
    </div>
</div>

<script src="<?= url('/assets/js/app.js'); ?>"></script>
</body>
</html>
