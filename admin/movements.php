<?php
/**
 * Warehouse Management System - Stock Movements
 * MODULE 03 — STOCK MOVEMENTS
 */

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pageTitle = 'Stock Movements';
$activeNav = 'movements';

$currentUser = getCurrentUser();
$movementError = '';

// -------------------------------------------------------------
// PROCESS STOCK MOVEMENT FORM (TRANSACTION PROTECTED)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_movement'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (CSRF mismatch). Please try again.');
        redirect('/admin/movements.php');
    }

    $itemId = (int)($_POST['item_id'] ?? 0);
    $movementType = strtoupper(sanitize($_POST['movement_type'] ?? ''));
    $quantity = (int)($_POST['quantity'] ?? 0);
    $movementDate = sanitize($_POST['movement_date'] ?? date('Y-m-d'));
    $referenceNote = sanitize($_POST['reference_note'] ?? '');

    // Form Field Validation
    if ($itemId <= 0) {
        $movementError = 'Please select a valid item from inventory.';
    } elseif (!in_array($movementType, ['IN', 'OUT'], true)) {
        $movementError = 'Movement type must be either IN or OUT.';
    } elseif ($quantity <= 0) {
        $movementError = 'Quantity must be a positive number greater than zero.';
    } elseif (empty($movementDate)) {
        $movementError = 'Transaction date is required.';
    } else {
        // Begin Database Transaction with SELECT ... FOR UPDATE
        try {
            $pdo->beginTransaction();

            // Lock the selected item record
            $stmtLock = $pdo->prepare("SELECT id, item_code, item_name, quantity, unit FROM items WHERE id = ? FOR UPDATE");
            $stmtLock->execute([$itemId]);
            $item = $stmtLock->fetch();

            if (!$item) {
                throw new Exception('The selected item does not exist in inventory.');
            }

            $currentStock = (int)$item['quantity'];

            if ($movementType === 'OUT') {
                if ($quantity > $currentStock) {
                    throw new Exception("Insufficient stock! Available quantity is {$currentStock} {$item['unit']}, requested OUT quantity is {$quantity} {$item['unit']}.");
                }
                $newStock = $currentStock - $quantity;
            } else {
                // Stock IN
                $newStock = $currentStock + $quantity;
            }

            // Insert stock movement record
            $stmtInsertMove = $pdo->prepare("
                INSERT INTO stock_movements (item_id, movement_type, quantity, movement_date, reference_note, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsertMove->execute([$itemId, $movementType, $quantity, $movementDate, $referenceNote, $currentUser['id']]);

            // Update item's current quantity
            $stmtUpdateItem = $pdo->prepare("UPDATE items SET quantity = ? WHERE id = ?");
            $stmtUpdateItem->execute([$newStock, $itemId]);

            // Commit transaction
            $pdo->commit();

            setFlash('success', "Stock {$movementType} transaction of {$quantity} {$item['unit']} for [{$item['item_code']}] {$item['item_name']} recorded successfully. New available balance: {$newStock} {$item['unit']}.");
            redirect('/admin/movements.php');

        } catch (Exception $e) {
            // Roll back transaction on any failure or insufficient stock
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $movementError = $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// RETRIEVE ITEMS LIST FOR SELECT DROPDOWN
// Format: Item Code - Item Name (Current Quantity + Unit)
// -------------------------------------------------------------
$stmtDropdown = $pdo->query("SELECT id, item_code, item_name, category, quantity, unit, reorder_level FROM items ORDER BY item_code ASC");
$inventoryItems = $stmtDropdown->fetchAll();

// -------------------------------------------------------------
// STOCK HISTORY FILTERS & RETRIEVAL
// -------------------------------------------------------------
$filterItemId = (int)($_GET['item_id'] ?? 0);
$filterType = strtoupper(sanitize($_GET['movement_type'] ?? 'ALL'));
$filterStartDate = sanitize($_GET['start_date'] ?? '');
$filterEndDate = sanitize($_GET['end_date'] ?? '');

$historySql = "
    SELECT sm.id, sm.movement_date, sm.movement_type, sm.quantity, sm.reference_note, 
           i.item_code, i.item_name, i.unit,
           u.full_name AS user_name
    FROM stock_movements sm
    JOIN items i ON sm.item_id = i.id
    JOIN users u ON sm.created_by = u.id
    WHERE 1=1
";
$historyParams = [];

if ($filterItemId > 0) {
    $historySql .= " AND sm.item_id = ?";
    $historyParams[] = $filterItemId;
}

if ($filterType === 'IN' || $filterType === 'OUT') {
    $historySql .= " AND sm.movement_type = ?";
    $historyParams[] = $filterType;
}

if (!empty($filterStartDate)) {
    $historySql .= " AND sm.movement_date >= ?";
    $historyParams[] = $filterStartDate;
}

if (!empty($filterEndDate)) {
    $historySql .= " AND sm.movement_date <= ?";
    $historyParams[] = $filterEndDate;
}

// Latest movement first
$historySql .= " ORDER BY sm.movement_date DESC, sm.id DESC";

$stmtHistory = $pdo->prepare($historySql);
$stmtHistory->execute($historyParams);
$movementsHistory = $stmtHistory->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>Stock Movements</h1>
        <div class="page-subtitle">Record stock IN and stock OUT transactions with atomic database consistency</div>
    </div>
</div>

<?php if (!empty($movementError)): ?>
    <div class="alert alert-danger">
        <div class="alert-content">
            <strong>Transaction Blocked:</strong> <?= e($movementError); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Module 03 Part 1: Stock Movement Entry Form -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#8646;</span> Record New Stock Movement
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/movements.php'); ?>" id="stockMovementForm">
            <?= csrfField(); ?>

            <div class="form-row">
                <!-- Item Select Dropdown -->
                <div class="form-group">
                    <label for="movementItemSelect" class="form-label">Warehouse Item <span class="required">*</span></label>
                    <select id="movementItemSelect" name="item_id" class="form-control" required>
                        <option value="">-- Select Item to Adjust --</option>
                        <?php foreach ($inventoryItems as $item): ?>
                            <option value="<?= (int)$item['id']; ?>"
                                    data-stock="<?= (int)$item['quantity']; ?>"
                                    data-unit="<?= e($item['unit']); ?>"
                                    data-reorder="<?= (int)$item['reorder_level']; ?>"
                                    <?= ($filterItemId === (int)$item['id']) ? 'selected' : ''; ?>>
                                <?= e($item['item_code']); ?> - <?= e($item['item_name']); ?> (<?= formatNumber($item['quantity']); ?> <?= e($item['unit']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="selectedItemStockInfo"></div>
                </div>

                <!-- Movement Type -->
                <div class="form-group">
                    <label class="form-label">Movement Type <span class="required">*</span></label>
                    <div style="display: flex; gap: 24px; align-items: center; padding-top: 8px;">
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600; color: #15803d;">
                            <input type="radio" name="movement_type" value="IN" checked>
                            <span>&#8595; Stock IN (Receiving / Restock)</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600; color: #b91c1c;">
                            <input type="radio" name="movement_type" value="OUT">
                            <span>&#8593; Stock OUT (Dispatch / Issue)</span>
                        </label>
                    </div>
                    <div class="form-help">Stock OUT is automatically restricted to current available quantity.</div>
                </div>
            </div>

            <div class="form-row">
                <!-- Quantity -->
                <div class="form-group">
                    <label for="movementQuantity" class="form-label">Quantity <span class="required">*</span></label>
                    <input type="number" 
                           id="movementQuantity" 
                           name="quantity" 
                           class="form-control" 
                           min="1" 
                           placeholder="Enter quantity amount" 
                           required>
                </div>

                <!-- Transaction Date -->
                <div class="form-group">
                    <label for="movementDate" class="form-label">Date <span class="required">*</span></label>
                    <input type="date" 
                           id="movementDate" 
                           name="movement_date" 
                           class="form-control" 
                           value="<?= date('Y-m-d'); ?>" 
                           required>
                </div>
            </div>

            <!-- Reference / Note -->
            <div class="form-group">
                <label for="movementReference" class="form-label">Reference / Note</label>
                <input type="text" 
                       id="movementReference" 
                       name="reference_note" 
                       class="form-control" 
                       placeholder="e.g. Purchase Order #PO-1082, Requisition #REQ-450, Damage Write-off">
                <div class="form-help">Optional purchase order, invoice, requisition number, or explanatory note.</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="reset" class="btn btn-secondary">Reset Form</button>
                <button type="submit" name="submit_movement" class="btn btn-primary" style="padding: 10px 24px;">
                    <span>&#10003;</span> Confirm &amp; Save Movement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Module 03 Part 2: Stock History Filters -->
<div class="filter-card">
    <div style="font-weight: 600; margin-bottom: 14px; font-size: 1rem; color: #1e293b;">
        <span>&#128269;</span> Filter Stock History
    </div>
    <form method="GET" action="<?= url('/admin/movements.php'); ?>" class="filter-grid">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="filterItem" class="form-label">Item</label>
            <select id="filterItem" name="item_id" class="form-control">
                <option value="">All Inventory Items</option>
                <?php foreach ($inventoryItems as $item): ?>
                    <option value="<?= (int)$item['id']; ?>" <?= ($filterItemId === (int)$item['id']) ? 'selected' : ''; ?>>
                        <?= e($item['item_code']); ?> - <?= e($item['item_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filterType" class="form-label">Movement Type</label>
            <select id="filterType" name="movement_type" class="form-control">
                <option value="ALL" <?= ($filterType === 'ALL') ? 'selected' : ''; ?>>All Movements (IN &amp; OUT)</option>
                <option value="IN" <?= ($filterType === 'IN') ? 'selected' : ''; ?>>IN Only</option>
                <option value="OUT" <?= ($filterType === 'OUT') ? 'selected' : ''; ?>>OUT Only</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filterStartDate" class="form-label">Start Date</label>
            <input type="date" id="filterStartDate" name="start_date" class="form-control" value="<?= e($filterStartDate); ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filterEndDate" class="form-label">End Date</label>
            <input type="date" id="filterEndDate" name="end_date" class="form-control" value="<?= e($filterEndDate); ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                Apply Filters
            </button>
            <?php if ($filterItemId > 0 || $filterType !== 'ALL' || !empty($filterStartDate) || !empty($filterEndDate)): ?>
                <a href="<?= url('/admin/movements.php'); ?>" class="btn btn-secondary">
                    Clear
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Module 03 Part 3: Stock History Table -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#128340;</span> Stock History Log (<?= count($movementsHistory); ?> Records)
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($movementsHistory)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th class="text-center" style="color: #15803d;">IN</th>
                            <th class="text-center" style="color: #b91c1c;">OUT</th>
                            <th>Reference / Note</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movementsHistory as $row): ?>
                            <tr>
                                <td><strong><?= formatDate($row['movement_date']); ?></strong></td>
                                <td><code><?= e($row['item_code']); ?></code></td>
                                <td><?= e($row['item_name']); ?></td>
                                <!-- IN Column -->
                                <td class="text-center">
                                    <?php if ($row['movement_type'] === 'IN'): ?>
                                        <span class="movement-tag in">
                                            +<?= formatNumber($row['quantity']); ?> <?= e($row['unit']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <!-- OUT Column -->
                                <td class="text-center">
                                    <?php if ($row['movement_type'] === 'OUT'): ?>
                                        <span class="movement-tag out">
                                            -<?= formatNumber($row['quantity']); ?> <?= e($row['unit']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($row['reference_note'] ?: '-'); ?></td>
                                <td><span class="badge badge-secondary"><?= e($row['user_name']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-table-state">
                <div class="empty-icon">&#128340;</div>
                <p style="font-weight: 500; font-size: 1rem;">No stock movement records found.</p>
                <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">Adjust the filter criteria or record a stock IN / OUT transaction above.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
