<?php
/**
 * Warehouse Management System - User Item Details
 * MODULE 07 — ITEM DETAILS
 */

require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pageTitle = 'Item Details';
$activeNav = 'items';

$itemId = (int)($_GET['id'] ?? 0);
if ($itemId <= 0) {
    setFlash('danger', 'Invalid item identifier.');
    redirect('/user/items.php');
}

// Fetch Item Information
$stmtItem = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmtItem->execute([$itemId]);
$item = $stmtItem->fetch();

if (!$item) {
    setFlash('danger', 'Warehouse item not found.');
    redirect('/user/items.php');
}

// Fetch Stock History for this item
$stmtHistory = $pdo->prepare("
    SELECT sm.movement_date, sm.movement_type, sm.quantity, sm.reference_note,
           u.full_name AS user_name
    FROM stock_movements sm
    JOIN users u ON sm.created_by = u.id
    WHERE sm.item_id = ?
    ORDER BY sm.movement_date DESC, sm.id DESC
");
$stmtHistory->execute([$itemId]);
$stockMovements = $stmtHistory->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>Item Details &mdash; <?= e($item['item_code']); ?></h1>
        <div class="page-subtitle"><?= e($item['item_name']); ?> (Read-Only View)</div>
    </div>
    <div class="page-actions">
        <a href="<?= url('/user/items.php'); ?>" class="btn btn-secondary">
            &larr; Back to Items List
        </a>
    </div>
</div>

<!-- Item Information Specifications Panel -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#9638;</span> Specifications &amp; Storage Bin Information
        </div>
        <div>
            <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                <span class="badge badge-warning" style="font-size: 0.85rem; padding: 5px 12px;">
                    &#9888; Low Stock Warning
                </span>
            <?php else: ?>
                <span class="badge badge-success" style="font-size: 0.85rem; padding: 5px 12px;">
                    &#10003; Healthy Stock Level
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Item Code</span>
                <strong style="font-size: 1.15rem; color: #1e293b;"><?= e($item['item_code']); ?></strong>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Item Name</span>
                <strong style="font-size: 1.15rem; color: #1e293b;"><?= e($item['item_name']); ?></strong>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Category</span>
                <span class="badge badge-secondary" style="font-size: 0.9rem;"><?= e($item['category']); ?></span>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Warehouse Location</span>
                <code style="font-size: 1.1rem;"><?= e($item['location']); ?></code>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Current Quantity</span>
                <strong style="font-size: 1.3rem; color: #1b4332;">
                    <?= formatNumber($item['quantity']); ?> <?= e($item['unit']); ?>
                </strong>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Unit of Measure</span>
                <span style="font-size: 1.1rem; color: #1e293b;"><?= e($item['unit']); ?></span>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Reorder Level</span>
                <span style="font-size: 1.1rem; color: #1e293b;"><?= formatNumber($item['reorder_level']); ?> <?= e($item['unit']); ?></span>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Record Created</span>
                <span style="font-size: 0.95rem; color: #475569;"><?= formatDateTime($item['created_at']); ?></span>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Last Updated</span>
                <span style="font-size: 0.95rem; color: #475569;"><?= formatDateTime($item['updated_at']); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Stock History Table for this Item -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#128340;</span> Stock History for <?= e($item['item_code']); ?>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($stockMovements)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-center" style="color: #15803d;">IN</th>
                            <th class="text-center" style="color: #b91c1c;">OUT</th>
                            <th>Reference / Note</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockMovements as $mv): ?>
                            <tr>
                                <td><strong><?= formatDate($mv['movement_date']); ?></strong></td>
                                <!-- IN Column -->
                                <td class="text-center">
                                    <?php if ($mv['movement_type'] === 'IN'): ?>
                                        <span class="movement-tag in">
                                            +<?= formatNumber($mv['quantity']); ?> <?= e($item['unit']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <!-- OUT Column -->
                                <td class="text-center">
                                    <?php if ($mv['movement_type'] === 'OUT'): ?>
                                        <span class="movement-tag out">
                                            -<?= formatNumber($mv['quantity']); ?> <?= e($item['unit']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($mv['reference_note'] ?: '-'); ?></td>
                                <td><span class="badge badge-secondary"><?= e($mv['user_name']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-table-state">
                <div class="empty-icon">&#128340;</div>
                <p style="font-weight: 500; font-size: 1rem;">No stock movements recorded yet for this item.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
