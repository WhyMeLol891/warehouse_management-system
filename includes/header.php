<?php
/**
 * Warehouse Management System - Common Header
 */

defined('APP_INIT') or define('APP_INIT', true);

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle); ?> &mdash; <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/style.css'); ?>">
</head>
<body>
<div class="app-layout">
    <!-- Top Dark Green Header -->
    <header class="app-header">
        <div class="header-brand">
            <button type="button" class="header-toggle" id="headerSidebarToggle" aria-label="Toggle Navigation">
                &#9776;
            </button>
            <div class="header-title"><?= e(APP_NAME); ?></div>
        </div>

        <?php if ($currentUser): ?>
        <div class="header-user">
            <div class="user-profile-info">
                <span class="user-name"><?= e($currentUser['full_name']); ?></span>
                <span class="user-role-badge <?= e($currentUser['role']); ?>">
                    <?= e(strtoupper($currentUser['role'])); ?>
                </span>
            </div>
            <a href="<?= url('/logout.php'); ?>" class="header-logout-btn" title="Sign out of system">
                <span>Logout</span> &rarr;
            </a>
        </div>
        <?php endif; ?>
    </header>

    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/nav.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <main class="main-content">
            <?php
            $flash = getFlash();
            if ($flash):
                $alertTypeClass = match($flash['type']) {
                    'danger', 'error' => 'alert-danger',
                    'warning'         => 'alert-warning',
                    'info'            => 'alert-info',
                    default           => 'alert-success',
                };
            ?>
            <div class="alert <?= $alertTypeClass; ?>" role="alert">
                <div class="alert-content">
                    <span><?= e($flash['text']); ?></span>
                </div>
                <button type="button" class="alert-close" aria-label="Dismiss alert">&times;</button>
            </div>
            <?php endif; ?>
