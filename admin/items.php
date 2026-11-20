<?php
/**
 * Warehouse Management System - Item Management
 * MODULE 02 — ITEM MANAGEMENT
 */

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pageTitle = 'Item Management';
$activeNav = 'items';

$errors = [];
$search = sanitize($_GET['search'] ?? '');
$categoryFilter = sanitize($_GET['category'] ?? '');

// -------------------------------------------------------------
// POST ACTION HANDLERS (ADD, EDIT, DELETE)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (CSRF token mismatch). Please try again.');
        redirect('/admin/items.php');
    }

    $action = sanitize($_POST['form_action'] ?? '');

    // 1. ADD ITEM
    if ($action === 'add') {
        $itemCode = strtoupper(sanitize($_POST['item_code'] ?? ''));
        $itemName = sanitize($_POST['item_name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $unit = sanitize($_POST['unit'] ?? '');
        $reorderLevel = (int)($_POST['reorder_level'] ?? 0);

        // Validation
        if (empty($itemCode)) $errors[] = 'Item Code is required.';
        if (empty($itemName)) $errors[] = 'Item Name is required.';
        if (empty($category)) $errors[] = 'Category is required.';
        if (empty($location)) $errors[] = 'Warehouse Location is required.';
        if (empty($unit)) $errors[] = 'Unit of measurement is required.';
        if ($quantity < 0) $errors[] = 'Initial quantity cannot be negative.';
        if ($reorderLevel < 0) $errors[] = 'Reorder level cannot be negative.';

        // Check Unique Item Code
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM items WHERE item_code = ? LIMIT 1");
            $stmtCheck->execute([$itemCode]);
            if ($stmtCheck->fetch()) {
                $errors[] = "Item Code '{$itemCode}' is already registered. Please use a unique item code.";
            }
        }

        if (empty($errors)) {
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO items (item_code, item_name, category, location, quantity, unit, reorder_level)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([$itemCode, $itemName, $category, $location, $quantity, $unit, $reorderLevel]);
                setFlash('success', "Item '{$itemCode} - {$itemName}' added successfully.");
                redirect('/admin/items.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }

    // 2. EDIT ITEM
    if ($action === 'edit') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $itemCode = strtoupper(sanitize($_POST['item_code'] ?? ''));
        $itemName = sanitize($_POST['item_name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $unit = sanitize($_POST['unit'] ?? '');
        $reorderLevel = (int)($_POST['reorder_level'] ?? 0);
        // Note: quantity is tracked via stock movements, but admin can adjust baseline if needed
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($itemId <= 0) $errors[] = 'Invalid item ID.';
        if (empty($itemCode)) $errors[] = 'Item Code is required.';
        if (empty($itemName)) $errors[] = 'Item Name is required.';
        if (empty($category)) $errors[] = 'Category is required.';
        if (empty($location)) $errors[] = 'Warehouse Location is required.';
        if (empty($unit)) $errors[] = 'Unit of measurement is required.';
        if ($quantity < 0) $errors[] = 'Quantity cannot be negative.';
        if ($reorderLevel < 0) $errors[] = 'Reorder level cannot be negative.';

        // Check Unique Item Code excluding self
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM items WHERE item_code = ? AND id != ? LIMIT 1");
            $stmtCheck->execute([$itemCode, $itemId]);
            if ($stmtCheck->fetch()) {
                $errors[] = "Item Code '{$itemCode}' is already assigned to another item.";
            }
        }

        if (empty($errors)) {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE items 
                    SET item_code = ?, item_name = ?, category = ?, location = ?, quantity = ?, unit = ?, reorder_level = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$itemCode, $itemName, $category, $location, $quantity, $unit, $reorderLevel, $itemId]);
                setFlash('success', "Item '{$itemCode}' updated successfully.");
                redirect('/admin/items.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error while updating item: ' . $e->getMessage();
            }
        }
    }

    // 3. DELETE ITEM
    if ($action === 'delete') {
        $itemId = (int)($_POST['item_id'] ?? 0);

        // Check if item has stock movements
        $stmtMovements = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE item_id = ?");
        $stmtMovements->execute([$itemId]);
        $hasMovements = (int)$stmtMovements->fetchColumn();

        if ($hasMovements > 0) {
            setFlash('danger', 'Cannot delete this item because it is already associated with stock movement records.');
            redirect('/admin/items.php');
        } else {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM items WHERE id = ?");
                $stmtDel->execute([$itemId]);
                setFlash('success', 'Item deleted successfully.');
                redirect('/admin/items.php');
            } catch (PDOException $e) {
                setFlash('danger', 'Unable to delete item: ' . $e->getMessage());
                redirect('/admin/items.php');
            }
        }
    }
}

// -------------------------------------------------------------
// VIEW ITEM & HISTORY (IF VIEW REQUESTED)
// -------------------------------------------------------------
$viewItem = null;
$viewItemMovements = [];
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId > 0) {
    $stmtView = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmtView->execute([$viewId]);
    $viewItem = $stmtView->fetch();

    if ($viewItem) {
        $stmtSm = $pdo->prepare("
            SELECT sm.movement_date, sm.movement_type, sm.quantity, sm.reference_note, u.full_name AS user_name
            FROM stock_movements sm
            JOIN users u ON sm.created_by = u.id
            WHERE sm.item_id = ?
            ORDER BY sm.movement_date DESC, sm.id DESC
            LIMIT 20
        ");
        $stmtSm->execute([$viewId]);
        $viewItemMovements = $stmtSm->fetchAll();
    }
}

// -------------------------------------------------------------
// EDIT ITEM DATA (IF EDIT REQUESTED VIA URL)
// -------------------------------------------------------------
$editItem = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmtEdit = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editItem = $stmtEdit->fetch();
}

// -------------------------------------------------------------
// FILTER & SEARCH QUERY
// -------------------------------------------------------------
$categories = getItemCategories($pdo);

$sql = "SELECT * FROM items WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (item_code LIKE ? OR item_name LIKE ? OR location LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($categoryFilter)) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY item_name ASC";
$stmtItems = $pdo->prepare($sql);
$stmtItems->execute($params);
$items = $stmtItems->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>Item Management</h1>
        <div class="page-subtitle">Add, edit, search, filter and monitor inventory items and locations</div>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-modal-target="addItemModal">
            <span>&plus;</span> Add New Item
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div class="alert-content">
            <ul style="margin-left: 18px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Search and Filter Form -->
<div class="filter-card">
    <form method="GET" action="<?= url('/admin/items.php'); ?>" class="filter-grid">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="itemSearch" class="form-label">Search Items</label>
            <input type="text" 
                   id="itemSearch" 
                   name="search" 
                   class="form-control" 
                   placeholder="Item Code, Name, or Location..." 
                   value="<?= e($search); ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="categoryFilter" class="form-label">Filter by Category</label>
            <select id="categoryFilter" name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat); ?>" <?= ($categoryFilter === $cat) ? 'selected' : ''; ?>>
                        <?= e($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <span>&#128269;</span> Search / Filter
            </button>
            <?php if (!empty($search) || !empty($categoryFilter)): ?>
                <a href="<?= url('/admin/items.php'); ?>" class="btn btn-secondary">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Items Listing Table -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#9638;</span> Warehouse Items (<?= count($items); ?> Records)
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th class="text-center">Quantity</th>
                            <th>Unit</th>
                            <th class="text-center">Reorder Level</th>
                            <th>Updated</th>
                            <th class="text-center" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= (int)$item['id']; ?></td>
                                <td><strong><?= e($item['item_code']); ?></strong></td>
                                <td><?= e($item['item_name']); ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?= e($item['category']); ?></span>
                                </td>
                                <td><code><?= e($item['location']); ?></code></td>
                                <td class="text-center">
                                    <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                                        <span class="badge badge-warning" title="Low stock alert!">
                                            <?= formatNumber($item['quantity']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">
                                            <?= formatNumber($item['quantity']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($item['unit']); ?></td>
                                <td class="text-center"><?= formatNumber($item['reorder_level']); ?></td>
                                <td style="font-size: 0.82rem; color: #64748b;"><?= formatDateTime($item['updated_at']); ?></td>
                                <td class="text-center">
                                    <div class="action-buttons" style="justify-content: center;">
                                        <!-- View Action -->
                                        <a href="<?= url('/admin/items.php?view=' . (int)$item['id']); ?>" 
                                           class="btn btn-secondary btn-sm" 
                                           title="View details and stock history">
                                            View
                                        </a>

                                        <!-- Edit Action -->
                                        <button type="button" 
                                                class="btn btn-warning btn-sm" 
                                                title="Edit item"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)">
                                            Edit
                                        </button>

                                        <!-- Delete Action Form -->
                                        <form method="POST" action="<?= url('/admin/items.php'); ?>" style="display: inline;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="item_id" value="<?= (int)$item['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm" 
                                                    data-confirm="Are you sure you want to delete item '<?= e($item['item_code']); ?>'? This action cannot be undone."
                                                    title="Delete item">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-table-state">
                <div class="empty-icon">&#128230;</div>
                <p style="font-weight: 500; font-size: 1rem;">No warehouse items found.</p>
                <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">Try modifying your search or filter criteria, or add a new item.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- =========================================================
     MODAL 1: ADD NEW ITEM
     ========================================================= -->
<div class="modal-overlay" id="addItemModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">&plus; Add New Warehouse Item</h3>
            <button type="button" class="modal-close-btn">&times;</button>
        </div>
        <form method="POST" action="<?= url('/admin/items.php'); ?>">
            <?= csrfField(); ?>
            <input type="hidden" name="form_action" value="add">

            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="addItemCode">Item Code <span class="required">*</span></label>
                        <input type="text" 
                               id="addItemCode" 
                               name="item_code" 
                               class="form-control auto-uppercase" 
                               required 
                               placeholder="e.g. ITM-006">
                        <div class="form-help">Unique code, automatically converted to uppercase.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="addItemName">Item Name <span class="required">*</span></label>
                        <input type="text" 
                               id="addItemName" 
                               name="item_name" 
                               class="form-control" 
                               required 
                               placeholder="e.g. Safety Helmets">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="addCategory">Category <span class="required">*</span></label>
                        <input type="text" 
                               id="addCategory" 
                               name="category" 
                               class="form-control" 
                               list="existingCategories" 
                               required 
                               placeholder="e.g. Safety, Packaging, Maintenance">
                        <datalist id="existingCategories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="addLocation">Warehouse Location <span class="required">*</span></label>
                        <input type="text" 
                               id="addLocation" 
                               name="location" 
                               class="form-control" 
                               required 
                               placeholder="e.g. A1-02, B3-01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="addQuantity">Initial Quantity <span class="required">*</span></label>
                        <input type="number" 
                               id="addQuantity" 
                               name="quantity" 
                               class="form-control" 
                               min="0" 
                               value="0" 
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="addUnit">Unit of Measure <span class="required">*</span></label>
                        <input type="text" 
                               id="addUnit" 
                               name="unit" 
                               class="form-control" 
                               required 
                               placeholder="e.g. pcs, pairs, rolls, boxes">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addReorderLevel">Reorder Level <span class="required">*</span></label>
                    <input type="number" 
                           id="addReorderLevel" 
                           name="reorder_level" 
                           class="form-control" 
                           min="0" 
                           value="10" 
                           required>
                    <div class="form-help">Items with stock &le; reorder level trigger low stock warnings.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     MODAL 2: EDIT ITEM
     ========================================================= -->
<div class="modal-overlay" id="editItemModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">&#9998; Edit Warehouse Item</h3>
            <button type="button" class="modal-close-btn">&times;</button>
        </div>
        <form method="POST" action="<?= url('/admin/items.php'); ?>">
            <?= csrfField(); ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" id="editItemId" name="item_id" value="">

            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editItemCode">Item Code <span class="required">*</span></label>
                        <input type="text" 
                               id="editItemCode" 
                               name="item_code" 
                               class="form-control auto-uppercase" 
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editItemName">Item Name <span class="required">*</span></label>
                        <input type="text" 
                               id="editItemName" 
                               name="item_name" 
                               class="form-control" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editCategory">Category <span class="required">*</span></label>
                        <input type="text" 
                               id="editCategory" 
                               name="category" 
                               class="form-control" 
                               list="existingCategories" 
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editLocation">Warehouse Location <span class="required">*</span></label>
                        <input type="text" 
                               id="editLocation" 
                               name="location" 
                               class="form-control" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editQuantity">Current Quantity <span class="required">*</span></label>
                        <input type="number" 
                               id="editQuantity" 
                               name="quantity" 
                               class="form-control" 
                               min="0" 
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editUnit">Unit of Measure <span class="required">*</span></label>
                        <input type="text" 
                               id="editUnit" 
                               name="unit" 
                               class="form-control" 
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editReorderLevel">Reorder Level <span class="required">*</span></label>
                    <input type="number" 
                           id="editReorderLevel" 
                           name="reorder_level" 
                           class="form-control" 
                           min="0" 
                           required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     MODAL 3: VIEW ITEM DETAILS & LATEST 20 STOCK MOVEMENTS
     ========================================================= -->
<?php if ($viewItem): ?>
<div class="modal-overlay active" id="viewItemModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Item Details: <?= e($viewItem['item_code']); ?> &mdash; <?= e($viewItem['item_name']); ?></h3>
            <a href="<?= url('/admin/items.php'); ?>" class="modal-close-btn" style="text-decoration: none;">&times;</a>
        </div>
        <div class="modal-body">
            <!-- Item Specifications Box -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; display: block;">Item Code</span>
                    <strong><?= e($viewItem['item_code']); ?></strong>
                </div>
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; display: block;">Category</span>
                    <span class="badge badge-secondary"><?= e($viewItem['category']); ?></span>
                </div>
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; display: block;">Location</span>
                    <code><?= e($viewItem['location']); ?></code>
                </div>
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; display: block;">Current Stock</span>
                    <span class="badge <?= ($viewItem['quantity'] <= $viewItem['reorder_level']) ? 'badge-warning' : 'badge-success'; ?>" style="font-size: 0.95rem; padding: 4px 10px;">
                        <?= formatNumber($viewItem['quantity']); ?> <?= e($viewItem['unit']); ?>
                    </span>
                </div>
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; display: block;">Reorder Level</span>
                    <span><?= formatNumber($viewItem['reorder_level']); ?> <?= e($viewItem['unit']); ?></span>
                </div>
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; display: block;">Last Updated</span>
                    <span><?= formatDateTime($viewItem['updated_at']); ?></span>
                </div>
            </div>

            <!-- Recent Stock Movement History Table (Latest 20 Records) -->
            <h4 style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                <span>Latest Stock Movement History (Last 20 Records)</span>
                <a href="<?= url('/admin/movements.php?item_id=' . (int)$viewItem['id']); ?>" class="btn btn-sm btn-secondary">
                    View Full Movements &rarr;
                </a>
            </h4>

            <div class="table-responsive" style="max-height: 350px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-center">Quantity</th>
                            <th>Reference / Note</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($viewItemMovements)): ?>
                            <?php foreach ($viewItemMovements as $mv): ?>
                                <tr>
                                    <td><?= formatDate($mv['movement_date']); ?></td>
                                    <td>
                                        <span class="movement-tag <?= strtolower($mv['movement_type']); ?>">
                                            <?= e($mv['movement_type']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= formatNumber($mv['quantity']); ?></strong>
                                    </td>
                                    <td><?= e($mv['reference_note'] ?: '-'); ?></td>
                                    <td><?= e($mv['user_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 24px; color: #94a3b8;">
                                    No stock movements recorded yet for this item.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <a href="<?= url('/admin/items.php'); ?>" class="btn btn-secondary">Close</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openEditModal(item) {
    document.getElementById('editItemId').value = item.id;
    document.getElementById('editItemCode').value = item.item_code;
    document.getElementById('editItemName').value = item.item_name;
    document.getElementById('editCategory').value = item.category;
    document.getElementById('editLocation').value = item.location;
    document.getElementById('editQuantity').value = item.quantity;
    document.getElementById('editUnit').value = item.unit;
    document.getElementById('editReorderLevel').value = item.reorder_level;
    openModal('editItemModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
