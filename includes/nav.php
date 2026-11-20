<?php
/**
 * Warehouse Management System - Sidebar Navigation
 */

defined('APP_INIT') or define('APP_INIT', true);

$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? 'user';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$activeNav = $activeNav ?? '';
?>
<!-- Dark Green Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-icon">W</div>
        <div class="sidebar-brand-text">Warehouse OS</div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <div class="nav-section-title">Admin Management</div>
            <ul class="nav-list">
                <li>
                    <a href="<?= url('/admin/dashboard.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'dashboard' || $currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#9634;</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/admin/items.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'items' || $currentScript === 'items.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#9636;</span>
                        <span>Items</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/admin/movements.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'movements' || $currentScript === 'movements.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#8646;</span>
                        <span>Stock Movements</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/admin/reports.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'reports' || $currentScript === 'reports.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#9776;</span>
                        <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/admin/users.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'users' || $currentScript === 'users.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#128101;</span>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/logout.php'); ?>" class="nav-link">
                        <span class="nav-icon">&#10148;</span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        <?php else: ?>
            <div class="nav-section-title">Warehouse Inventory</div>
            <ul class="nav-list">
                <li>
                    <a href="<?= url('/user/dashboard.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'dashboard' || $currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#9634;</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/user/items.php'); ?>" 
                       class="nav-link <?= ($activeNav === 'items' || $currentScript === 'items.php' || $currentScript === 'item-details.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">&#9636;</span>
                        <span>Items</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('/logout.php'); ?>" class="nav-link">
                        <span class="nav-icon">&#10148;</span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div>Logged in as: <strong><?= e($currentUser['username'] ?? ''); ?></strong></div>
        <div style="font-size: 0.72rem; margin-top: 4px; opacity: 0.8;">XAMPP &bull; PHP <?= phpversion(); ?></div>
    </div>
</aside>
