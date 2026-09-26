<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Receipts (Incoming Stock)';
$page_subtitle = 'Manage vendor deliveries, incoming shipments, and receiving docks';

$status_filter = $_GET['status'] ?? 'all';

$sql = "
    SELECT r.*, loc.name as location_name, w.code as wh_code, u.full_name as creator_name,
           COALESCE((SELECT COUNT(*) FROM receipt_items WHERE receipt_id = r.id), 0) as item_count,
           COALESCE((SELECT SUM(quantity_received) FROM receipt_items WHERE receipt_id = r.id), 0) as total_qty
    FROM receipts r
    JOIN locations loc ON r.destination_location_id = loc.id
    JOIN warehouses w ON loc.warehouse_id = w.id
    JOIN users u ON r.created_by = u.id
";

if ($status_filter !== 'all') {
    $sql .= " WHERE r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY r.created_at DESC";

$receipts = db_select($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-arrow-down-left-and-arrow-up-right-to-center me-2"></i>Incoming Stock Receipts
        </h4>
        <p class="text-muted small mb-0">Record items arriving from vendors, inspect quantities, and validate to increase inventory.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="receipt_create.php" class="btn btn-sm btn-brand">
            <i class="fa-solid fa-plus me-1"></i> New Receipt
        </a>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card card-odoo mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="btn-group btn-group-sm">
                <a href="receipts.php?status=all" class="btn <?= ($status_filter === 'all') ? 'btn-brand active' : 'btn-outline-secondary' ?>">All Receipts</a>
                <a href="receipts.php?status=draft" class="btn <?= ($status_filter === 'draft') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Draft</a>
                <a href="receipts.php?status=waiting" class="btn <?= ($status_filter === 'waiting') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Waiting</a>
                <a href="receipts.php?status=ready" class="btn <?= ($status_filter === 'ready') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Ready to Receive</a>
                <a href="receipts.php?status=done" class="btn <?= ($status_filter === 'done') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Done (Validated)</a>
            </div>

            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" class="form-control" id="receiptSearch" placeholder="Search Supplier, Ref...">
            </div>
        </div>
    </div>
</div>

<!-- Receipts Table -->
<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0" id="receiptsTable">
            <thead>
                <tr>
                    <th class="ps-4">Reference #</th>
                    <th>Supplier / Vendor</th>
                    <th>Destination Location</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Total Received</th>
                    <th class="text-center">Status</th>
                    <th>Created By</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receipts)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                            No receipts found. <a href="receipt_create.php">Create a new incoming receipt</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($receipts as $r): ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold" style="color: #714B67;">
                                <?= htmlspecialchars($r['reference_no']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['supplier_name']) ?></div>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime($r['created_at'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($r['wh_code']) ?> &rarr; <?= htmlspecialchars($r['location_name']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold"><?= $r['item_count'] ?></span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                <?= number_format($r['total_qty'], 2) ?>
                            </td>
                            <td class="text-center">
                                <?= get_status_badge($r['status']) ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($r['creator_name']) ?></small>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="receipt_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-3">
                                    <?= ($r['status'] === 'done') ? 'View Record' : 'Inspect &amp; Validate' ?> &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('receiptSearch');
    const table = document.getElementById('receiptsTable');
    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
