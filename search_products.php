<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthorized', [], 401);
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    jsonResponse(true, '', ['results' => []]);
}

$conn = db();
$like = '%' . $q . '%';
$results = [];

// Products by name or SKU
$stmt = $conn->prepare("SELECT product_id, name, sku FROM products WHERE (name LIKE ? OR sku LIKE ?) AND is_active = 1 LIMIT 5");
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'title' => $row['name'],
        'subtitle' => 'SKU: ' . $row['sku'],
        'type' => 'Product',
        'url' => BASE_URL . 'modules/products/view.php?id=' . $row['product_id'],
    ];
}
$stmt->close();

// Operations by reference number
$stmt = $conn->prepare("SELECT operation_id, reference_no, operation_type, status FROM stock_operations WHERE reference_no LIKE ? LIMIT 5");
$stmt->bind_param('s', $like);
$stmt->execute();
$res = $stmt->get_result();
$moduleMap = ['receipt' => 'receipts', 'delivery' => 'delivery', 'internal' => 'transfers', 'adjustment' => 'adjustments'];
while ($row = $res->fetch_assoc()) {
    $mod = $moduleMap[$row['operation_type']] ?? 'move_history';
    $results[] = [
        'title' => $row['reference_no'],
        'subtitle' => operationTypeLabel($row['operation_type']) . ' · ' . ucfirst($row['status']),
        'type' => 'Operation',
        'url' => BASE_URL . 'modules/' . $mod . '/view.php?id=' . $row['operation_id'],
    ];
}
$stmt->close();

jsonResponse(true, '', ['results' => $results]);
