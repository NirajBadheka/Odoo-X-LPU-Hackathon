<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Move History (Stock Ledger)';
$page_subtitle = 'Immutable double-entry audit log of all inventory movements';

$type_filter = $_GET['type'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT sm.*, p.name as product_name, p.sku, p.unit_of_measure,
           l1.name as src_name, w1.code as src_wh,
           l2.name as dest_name, w2.code as dest_wh,
           u.full_name as user_name, u.role as user_role
    FROM stock_moves sm
    JOIN products p ON sm.product_id = p.id
    LEFT JOIN locations l1 ON sm.source_location_id = l1.id
    LEFT JOIN warehouses w1 ON l1.warehouse_id = w1.id
    LEFT JOIN locations l2 ON sm.destination_location_id = l2.id
    LEFT JOIN warehouses w2 ON l2.warehouse_id = w2.id
    JOIN users u ON sm.user_id = u.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($type_filter !== 'all') {
    $sql .= " AND sm.move_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (sm.reference_doc LIKE ? OR p.name LIKE ? OR p.sku LIKE ? OR u.full_name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY sm.created_at DESC";

$moves = db_select($sql, $params, $types);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>Double-Entry Stock Ledger
        </h4>
        <p class="text-muted small mb-0">Immutable historical record of every inventory receipt, transfer, dispatch, and physical count adjustment.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Print / Export Ledger
        </button>
    </div>
</div>

<!-- Ledger Filters -->
<div class="card card-odoo mb-4">
    <div class="card-body p-3">
        <form method="GET" action="move_history.php" class="row g-2 align-items-center justify-content-between">
            <div class="col-md-auto">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold text-muted">Movement Type:</span>
                    <select name="type" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()">
                        <option value="all" <?= ($type_filter === 'all') ? 'selected' : '' ?>>All Movements</option>
                        <option value="receipt" <?= ($type_filter === 'receipt') ? 'selected' : '' ?>>Receipts (Incoming)</option>
                        <option value="delivery" <?= ($type_filter === 'delivery') ? 'selected' : '' ?>>Deliveries (Outgoing)</option>
                        <option value="internal" <?= ($type_filter === 'internal') ? 'selected' : '' ?>>Internal Transfers</option>
                        <option value="adjustment" <?= ($type_filter === 'adjustment') ? 'selected' : '' ?>>Adjustments</option>
                    </select>
                </div>
            </div>

            <div class="col-md-auto">
                <div class="input-group input-group-sm" style="width: 280px;">
                    <input type="text" name="search" class="form-control" placeholder="Search Ref, SKU, User..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-brand" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <?php if (!empty($search) || $type_filter !== 'all'): ?>
                        <a href="move_history.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Ledger Table -->
<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-book-bookmark me-2 text-primary"></i>Stock Movement Journal
        </h6>
        <span class="badge bg-light text-muted border font-monospace"><?= count($moves) ?> Audit Entries</span>
    </div>

    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Date &amp; Time</th>
                    <th>Reference Doc</th>
                    <th>Type</th>
                    <th>Product &amp; SKU</th>
                    <th>Source &rarr; Destination</th>
                    <th class="text-end">Quantity Change</th>
                    <th>User Responsible</th>
                    <th class="pe-4">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($moves)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clock-rotate-left fa-3x mb-3 text-secondary d-block"></i>
                            No stock moves found matching the filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($moves as $m): ?>
                        <tr>
                            <td class="ps-4 text-muted small font-monospace">
                                <?= date('Y-m-d H:i:s', strtotime($m['created_at'])) ?>
                            </td>
                            <td>
                                <span class="font-monospace fw-bold" style="color: #714B67;">
                                    <?= htmlspecialchars($m['reference_doc']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($m['move_type'] === 'receipt'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-arrow-down me-1"></i>Receipt</span>
                                <?php elseif ($m['move_type'] === 'delivery'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-solid fa-arrow-up me-1"></i>Delivery</span>
                                <?php elseif ($m['move_type'] === 'internal'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Internal</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa-solid fa-sliders me-1"></i>Adjustment</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($m['product_name']) ?></div>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($m['sku']) ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($m['src_name'] ? ($m['src_wh'] . '/' . $m['src_name']) : 'Vendor / External') ?></small>
                                <i class="fa-solid fa-arrow-right mx-1 text-muted smaller"></i>
                                <small class="fw-medium text-dark"><?= htmlspecialchars($m['dest_name'] ? ($m['dest_wh'] . '/' . $m['dest_name']) : 'Customer / Scrap') ?></small>
                            </td>
                            <td class="text-end fw-extrabold fs-6 <?= ($m['quantity'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                <?= ($m['quantity'] > 0 ? '+' : '') . number_format($m['quantity'], 2) ?> <?= htmlspecialchars($m['unit_of_measure']) ?>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($m['user_name']) ?></div>
                                <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.6rem;"><?= htmlspecialchars($m['user_role']) ?></span>
                            </td>
                            <td class="pe-4 text-muted small">
                                <?= htmlspecialchars($m['notes'] ?: '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
