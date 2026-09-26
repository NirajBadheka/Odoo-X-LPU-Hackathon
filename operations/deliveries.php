<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Delivery Orders (Outgoing Stock)';
$page_subtitle = 'Pick, pack, and ship inventory to customers';

$status_filter = $_GET['status'] ?? 'all';

$sql = "
    SELECT d.*, loc.name as location_name, w.code as wh_code, u.full_name as creator_name,
           COALESCE((SELECT COUNT(*) FROM delivery_items WHERE delivery_id = d.id), 0) as item_count,
           COALESCE((SELECT SUM(quantity_demanded) FROM delivery_items WHERE delivery_id = d.id), 0) as total_demanded
    FROM delivery_orders d
    JOIN locations loc ON d.source_location_id = loc.id
    JOIN warehouses w ON loc.warehouse_id = w.id
    JOIN users u ON d.created_by = u.id
";

if ($status_filter !== 'all') {
    $sql .= " WHERE d.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY d.created_at DESC";

$deliveries = db_select($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-truck-ramp-box me-2"></i>Outgoing Delivery Orders
        </h4>
        <p class="text-muted small mb-0">Customer shipments, picking slips, packing lists, and automated stock dispatch reductions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="delivery_create.php" class="btn btn-sm btn-brand">
            <i class="fa-solid fa-plus me-1"></i> New Delivery Order
        </a>
    </div>
</div>

<!-- Status Filters -->
<div class="card card-odoo mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="btn-group btn-group-sm">
                <a href="deliveries.php?status=all" class="btn <?= ($status_filter === 'all') ? 'btn-brand active' : 'btn-outline-secondary' ?>">All Deliveries</a>
                <a href="deliveries.php?status=waiting" class="btn <?= ($status_filter === 'waiting') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Waiting (To Pick)</a>
                <a href="deliveries.php?status=ready" class="btn <?= ($status_filter === 'ready') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Ready (Packed)</a>
                <a href="deliveries.php?status=done" class="btn <?= ($status_filter === 'done') ? 'btn-brand active' : 'btn-outline-secondary' ?>">Done (Shipped)</a>
            </div>

            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" class="form-control" id="deliverySearch" placeholder="Search Customer, Ref...">
            </div>
        </div>
    </div>
</div>

<!-- Deliveries Table -->
<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0" id="deliveriesTable">
            <thead>
                <tr>
                    <th class="ps-4">Reference #</th>
                    <th>Customer Name</th>
                    <th>Source Warehouse / Location</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Units Demanded</th>
                    <th class="text-center">Workflow Status</th>
                    <th>Created By</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deliveries)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-truck-fast fa-3x mb-3 text-secondary d-block"></i>
                            No delivery orders found. <a href="delivery_create.php">Create a new customer delivery</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deliveries as $d): ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold" style="color: #714B67;">
                                <?= htmlspecialchars($d['reference_no']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($d['customer_name']) ?></div>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime($d['created_at'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($d['wh_code']) ?> &rarr; <?= htmlspecialchars($d['location_name']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold"><?= $d['item_count'] ?></span>
                            </td>
                            <td class="text-end fw-bold text-danger">
                                -<?= number_format($d['total_demanded'], 2) ?>
                            </td>
                            <td class="text-center">
                                <?= get_status_badge($d['status']) ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($d['creator_name']) ?></small>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="delivery_view.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-3">
                                    <?= ($d['status'] === 'done') ? 'View Dispatch' : 'Pick, Pack &amp; Validate' ?> &rarr;
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
    const searchInput = document.getElementById('deliverySearch');
    const table = document.getElementById('deliveriesTable');
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
