<?php
/**
 * Warehouse Management System - User Item List
 * MODULE 07 — USER ITEM LIST
 */

require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pageTitle = 'Warehouse Items';
$activeNav = 'items';

$search = sanitize($_GET['search'] ?? '');
$categoryFilter = sanitize($_GET['category'] ?? '');

$categories = getItemCategories($pdo);

$sql = "SELECT id, item_code, item_name, category, location, quantity, unit, reorder_level FROM items WHERE 1=1";
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
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>Warehouse Items Catalog</h1>
        <div class="page-subtitle">Search, filter and inspect warehouse inventory and storage bin locations (Read-Only)</div>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="filter-card">
    <form method="GET" action="<?= url('/user/items.php'); ?>" class="filter-grid">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="userSearch" class="form-label">Search Items</label>
            <input type="text" 
                   id="userSearch" 
                   name="search" 
                   class="form-control" 
                   placeholder="Search by Code, Name, or Location..." 
                   value="<?= e($search); ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="userCategory" class="form-label">Filter by Category</label>
            <select id="userCategory" name="category" class="form-control">
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
                <a href="<?= url('/user/items.php'); ?>" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Items Table -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#9638;</span> Inventory Items (<?= count($items); ?> Records)
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th class="text-center">Quantity</th>
                            <th>Unit</th>
                            <th class="text-center">Reorder Level</th>
                            <th class="text-center" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?= e($item['item_code']); ?></strong></td>
                                <td><?= e($item['item_name']); ?></td>
                                <td><span class="badge badge-secondary"><?= e($item['category']); ?></span></td>
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
                                <td class="text-center">
                                    <a href="<?= url('/user/item-details.php?id=' . (int)$item['id']); ?>" class="btn btn-secondary btn-sm">
                                        View Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-table-state">
                <div class="empty-icon">&#128230;</div>
                <p style="font-weight: 500; font-size: 1rem;">No items found matching the search criteria.</p>
                <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">Try modifying the keyword or category filter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
