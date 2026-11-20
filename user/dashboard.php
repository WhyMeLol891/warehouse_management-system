<?php
/**
 * Warehouse Management System - User Dashboard
 * MODULE 06 — USER DASHBOARD
 */

require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

try {
    // 1. Total Items
    $stmtItems = $pdo->query("SELECT COUNT(*) FROM items");
    $totalItems = (int)$stmtItems->fetchColumn();

    // 2. Total Stock Quantity
    $stmtStock = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM items");
    $totalStock = (int)$stmtStock->fetchColumn();

    // 3. Low Stock Items
    $stmtLow = $pdo->query("SELECT COUNT(*) FROM items WHERE quantity <= reorder_level");
    $lowStockItems = (int)$stmtLow->fetchColumn();

    // Recent Stock History (Latest 10 records)
    $stmtRecent = $pdo->query("
        SELECT sm.movement_date, sm.movement_type, sm.quantity, sm.reference_note,
               i.item_code, i.item_name, i.unit
        FROM stock_movements sm
        JOIN items i ON sm.item_id = i.id
        ORDER BY sm.movement_date DESC, sm.id DESC
        LIMIT 10
    ");
    $recentMovements = $stmtRecent->fetchAll();

} catch (PDOException $e) {
    error_log("User dashboard query error: " . $e->getMessage());
    $totalItems = $totalStock = $lowStockItems = 0;
    $recentMovements = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>Warehouse Dashboard</h1>
        <div class="page-subtitle">Inventory overview and recent warehouse activity logs (Read-Only)</div>
    </div>
    <div class="page-actions">
        <a href="<?= url('/user/items.php'); ?>" class="btn btn-secondary">
            <span>&#128269;</span> Browse Items
        </a>
    </div>
</div>

<!-- 3 Statistical Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
    <!-- Card 1: Total Items -->
    <div class="stat-card">
        <div class="stat-icon-wrap primary">
            &#9638;
        </div>
        <div class="stat-details">
            <span class="stat-label">Total Items</span>
            <span class="stat-value"><?= formatNumber($totalItems); ?></span>
        </div>
    </div>

    <!-- Card 2: Total Stock Quantity -->
    <div class="stat-card">
        <div class="stat-icon-wrap info">
            &#9635;
        </div>
        <div class="stat-details">
            <span class="stat-label">Total Stock Quantity</span>
            <span class="stat-value"><?= formatNumber($totalStock); ?></span>
        </div>
    </div>

    <!-- Card 3: Low Stock Items -->
    <div class="stat-card">
        <div class="stat-icon-wrap warning">
            &#9888;
        </div>
        <div class="stat-details">
            <span class="stat-label">Low Stock Items</span>
            <span class="stat-value"><?= formatNumber($lowStockItems); ?></span>
        </div>
    </div>
</div>

<!-- Recent Stock History (Latest 10 Records) -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#128340;</span> Recent Stock History (Latest 10 Movements)
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($recentMovements)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Qty</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMovements as $mv): ?>
                            <tr>
                                <td><strong><?= formatDate($mv['movement_date']); ?></strong></td>
                                <td>
                                    <strong><?= e($mv['item_code']); ?></strong> &mdash; <?= e($mv['item_name']); ?>
                                </td>
                                <td class="text-center">
                                    <span class="movement-tag <?= strtolower($mv['movement_type']); ?>">
                                        <?= e($mv['movement_type']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong><?= formatNumber($mv['quantity']); ?></strong> <?= e($mv['unit']); ?>
                                </td>
                                <td><?= e($mv['reference_note'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-table-state">
                <div class="empty-icon">&#128340;</div>
                <p style="font-weight: 500; font-size: 1rem;">No recent stock movements recorded.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
