<?php
/**
 * Warehouse Management System - Admin Dashboard
 * MODULE 01 — ADMIN DASHBOARD
 */

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';

// Dynamic Statistics Retrieval from MySQL
try {
    // 1. Total Items
    $stmtTotalItems = $pdo->query("SELECT COUNT(*) FROM items");
    $totalItems = (int)$stmtTotalItems->fetchColumn();

    // 2. Total Stock Quantity
    $stmtTotalStock = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM items");
    $totalStockQuantity = (int)$stmtTotalStock->fetchColumn();

    // 3. Low Stock Items Count
    $stmtLowStockCount = $pdo->query("SELECT COUNT(*) FROM items WHERE quantity <= reorder_level");
    $lowStockCount = (int)$stmtLowStockCount->fetchColumn();

    // 4. Today's Stock IN
    $stmtTodayIn = $pdo->query("
        SELECT COALESCE(SUM(quantity), 0) 
        FROM stock_movements 
        WHERE movement_type = 'IN' AND movement_date = CURDATE()
    ");
    $todayStockIn = (int)$stmtTodayIn->fetchColumn();

    // 5. Today's Stock OUT
    $stmtTodayOut = $pdo->query("
        SELECT COALESCE(SUM(quantity), 0) 
        FROM stock_movements 
        WHERE movement_type = 'OUT' AND movement_date = CURDATE()
    ");
    $todayStockOut = (int)$stmtTodayOut->fetchColumn();

    // Low Stock Items Table Data
    $stmtLowStockList = $pdo->query("
        SELECT id, item_code, item_name, category, location, quantity, unit, reorder_level 
        FROM items 
        WHERE quantity <= reorder_level 
        ORDER BY (reorder_level - quantity) DESC, item_name ASC
    ");
    $lowStockItems = $stmtLowStockList->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $totalItems = $totalStockQuantity = $lowStockCount = $todayStockIn = $todayStockOut = 0;
    $lowStockItems = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>Warehouse Dashboard</h1>
        <div class="page-subtitle">Real-time overview of warehouse inventory and daily stock operations</div>
    </div>
    <div class="page-actions">
        <a href="<?= url('/admin/movements.php'); ?>" class="btn btn-primary">
            <span>&plus;</span> Record Stock Movement
        </a>
    </div>
</div>

<!-- 5 Dynamic Statistical Cards -->
<div class="stats-grid">
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
            <span class="stat-value"><?= formatNumber($totalStockQuantity); ?></span>
        </div>
    </div>

    <!-- Card 3: Low Stock Items -->
    <div class="stat-card">
        <div class="stat-icon-wrap warning">
            &#9888;
        </div>
        <div class="stat-details">
            <span class="stat-label">Low Stock Items</span>
            <span class="stat-value"><?= formatNumber($lowStockCount); ?></span>
        </div>
    </div>

    <!-- Card 4: Today's Stock IN -->
    <div class="stat-card">
        <div class="stat-icon-wrap success">
            &#8595;
        </div>
        <div class="stat-details">
            <span class="stat-label">Today's Stock IN</span>
            <span class="stat-value"><?= formatNumber($todayStockIn); ?></span>
        </div>
    </div>

    <!-- Card 5: Today's Stock OUT -->
    <div class="stat-card">
        <div class="stat-icon-wrap danger">
            &#8593;
        </div>
        <div class="stat-details">
            <span class="stat-label">Today's Stock OUT</span>
            <span class="stat-value"><?= formatNumber($todayStockOut); ?></span>
        </div>
    </div>
</div>

<!-- Low Stock Items Table -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#9888;</span> Low Stock Items (At or Below Reorder Level)
        </div>
        <div>
            <span class="badge badge-warning"><?= count($lowStockItems); ?> Items Alert</span>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($lowStockItems)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Reorder Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e($item['item_code']); ?></strong>
                                </td>
                                <td><?= e($item['item_name']); ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?= e($item['category']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning" style="font-size: 0.88rem; padding: 4px 10px;">
                                        <?= formatNumber($item['quantity']); ?> <?= e($item['unit']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?= formatNumber($item['reorder_level']); ?> <?= e($item['unit']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-table-state">
                <div class="empty-icon">&#10004;</div>
                <p style="font-weight: 500; font-size: 1rem;">No low-stock items right now.</p>
                <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">All inventory levels are currently above reorder thresholds.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
