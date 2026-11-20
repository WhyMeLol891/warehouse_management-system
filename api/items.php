<?php
/**
 * Warehouse Management System - Items API Endpoint
 * Provides JSON item lookup for frontend dynamic updates
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// Verify authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = sanitize($_GET['action'] ?? 'list');

try {
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, item_code, item_name, category, location, quantity, unit, reorder_level FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Item not found']);
        }
        exit;
    }

    if ($action === 'search') {
        $query = sanitize($_GET['q'] ?? '');
        $searchParam = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT id, item_code, item_name, category, location, quantity, unit, reorder_level 
            FROM items 
            WHERE item_code LIKE ? OR item_name LIKE ? OR location LIKE ? 
            ORDER BY item_name ASC 
            LIMIT 50
        ");
        $stmt->execute([$searchParam, $searchParam, $searchParam]);
        $items = $stmt->fetchAll();
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }

    // Default: list all items
    $stmt = $pdo->query("SELECT id, item_code, item_name, category, location, quantity, unit, reorder_level FROM items ORDER BY item_code ASC");
    $items = $stmt->fetchAll();
    echo json_encode(['success' => true, 'items' => $items]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
}
