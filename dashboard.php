<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth_check.php';

// Authentication Guard
require_login();
$current_user = get_current_user_data();

$page_title = 'Inventory Dashboard';
$page_subtitle = 'Telemetry & Real-Time Operational Controls';

// -------------------------------------------------------------
// 1. Fetch Dashboard KPIs
// -------------------------------------------------------------
$total_products = 0;
$low_stock_count = 0;
$pending_receipts = 0;
$pending_deliveries = 0;
$scheduled_transfers = 0;
$total_inventory_value = 0.00;

if ($db_connected) {
    // Total Products
    $res = db_select_one("SELECT COUNT(*) as cnt FROM products");
    $total_products = $res['cnt'] ?? 0;

    // Low Stock / Out of Stock Items (Sum across locations <= min_level)
    $low_stock_rows = db_select("
        SELECT p.id, p.name, p.sku, p.reorder_min_level, COALESCE(SUM(sl.quantity), 0) as current_stock
        FROM products p
        LEFT JOIN stock_levels sl ON p.id = sl.product_id
        GROUP BY p.id
        HAVING current_stock <= p.reorder_min_level
    ");
    $low_stock_count = count($low_stock_rows);

    // Pending Receipts
    $res = db_select_one("SELECT COUNT(*) as cnt FROM receipts WHERE status IN ('waiting', 'ready', 'draft')");
    $pending_receipts = $res['cnt'] ?? 0;

    // Pending Deliveries
    $res = db_select_one("SELECT COUNT(*) as cnt FROM delivery_orders WHERE status IN ('waiting', 'ready', 'draft')");
    $pending_deliveries = $res['cnt'] ?? 0;

    // Internal Transfers Scheduled
    $res = db_select_one("SELECT COUNT(*) as cnt FROM internal_transfers WHERE status IN ('draft', 'ready')");
    $scheduled_transfers = $res['cnt'] ?? 0;

    // Total Inventory Valuation (sum(quantity * cost_price))
    $res = db_select_one("
        SELECT SUM(sl.quantity * p.cost_price) as total_val
        FROM stock_levels sl
        JOIN products p ON sl.product_id = p.id
    ");
    $total_inventory_value = $res['total_val'] ?? 0.00;

    // Category breakdown for Chart.js
    $category_stats = db_select("
        SELECT c.name as category_name, COUNT(p.id) as product_count, COALESCE(SUM(sl.quantity * p.cost_price), 0) as cat_val
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN stock_levels sl ON p.id = sl.product_id
        GROUP BY c.id
    ");

    // Dynamic Filter Options: Warehouses, Categories
    $warehouses = db_select("SELECT id, name, code FROM warehouses WHERE is_active = 1");
    $categories = db_select("SELECT id, name FROM categories");

    // Unified Operations Queue (Receipts, Deliveries, Transfers, Adjustments)
    $all_operations = [];

    // Receipts
    $r_list = db_select("
        SELECT r.id, r.reference_no, 'receipt' as doc_type, r.status, r.supplier_name as party_name,
               r.created_at, loc.name as location_name, loc.warehouse_id,
               COALESCE((SELECT COUNT(*) FROM receipt_items WHERE receipt_id = r.id), 0) as items_count
        FROM receipts r
        JOIN locations loc ON r.destination_location_id = loc.id
        ORDER BY r.created_at DESC LIMIT 15
    ");
    foreach ($r_list as $row) { $all_operations[] = $row; }

    // Deliveries
    $d_list = db_select("
        SELECT d.id, d.reference_no, 'delivery' as doc_type, d.status, d.customer_name as party_name,
               d.created_at, loc.name as location_name, loc.warehouse_id,
               COALESCE((SELECT COUNT(*) FROM delivery_items WHERE delivery_id = d.id), 0) as items_count
        FROM delivery_orders d
        JOIN locations loc ON d.source_location_id = loc.id
        ORDER BY d.created_at DESC LIMIT 15
    ");
    foreach ($d_list as $row) { $all_operations[] = $row; }

    // Transfers
    $t_list = db_select("
        SELECT t.id, t.reference_no, 'internal' as doc_type, t.status, 
               CONCAT(l1.name, ' -> ', l2.name) as party_name,
               t.created_at, l1.name as location_name, l1.warehouse_id, 1 as items_count
        FROM internal_transfers t
        JOIN locations l1 ON t.source_location_id = l1.id
        JOIN locations l2 ON t.destination_location_id = l2.id
        ORDER BY t.created_at DESC LIMIT 15
    ");
    foreach ($t_list as $row) { $all_operations[] = $row; }

    // Adjustments
    $a_list = db_select("
        SELECT a.id, a.reference_no, 'adjustment' as doc_type, a.status,
               CONCAT('Diff: ', IF(a.difference_quantity > 0, '+', ''), a.difference_quantity, ' (', a.reason, ')') as party_name,
               a.created_at, loc.name as location_name, loc.warehouse_id, 1 as items_count
        FROM stock_adjustments a
        JOIN locations loc ON a.location_id = loc.id
        ORDER BY a.created_at DESC LIMIT 15
    ");
    foreach ($a_list as $row) { $all_operations[] = $row; }

    // Sort combined operations by timestamp DESC
    usort($all_operations, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}

include __DIR__ . '/includes/header.php';
?>

<!-- Top Welcome & Role Banner -->
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-3 text-white shadow-sm d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #714B67;">
                <i class="fa-solid fa-gauge-high fa-xl"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0" style="color: #212529;">Welcome back, <?= htmlspecialchars($current_user['name']) ?>!</h4>
                <div class="text-muted small">
                    Active Role: <span class="badge py-1 px-2 text-uppercase fw-bold" style="background:#00A09D;"><?= htmlspecialchars($current_user['role']) ?></span>
                    &bull; Database: <code>pro_stocksense</code> &bull; Real-time Multi-Location Tracking
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <a href="operations/receipt_create.php" class="btn btn-sm btn-brand">
                <i class="fa-solid fa-plus me-1"></i> Receive Stock
            </a>
            <a href="operations/delivery_create.php" class="btn btn-sm btn-odoo">
                <i class="fa-solid fa-truck-fast me-1"></i> New Delivery
            </a>
        </div>
    </div>
</div>

<!-- Dashboard KPIs (5 Key Metrics From Problem Statement) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Products in Stock -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-title">Total Products</span>
                <div class="kpi-icon" style="background: rgba(113, 75, 103, 0.1); color: #714B67;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="kpi-value"><?= number_format($total_products) ?></div>
            <div class="small text-success mt-2 font-medium">
                <i class="fa-solid fa-circle-check me-1"></i>Catalog Active
            </div>
        </div>
    </div>

    <!-- 2. Low Stock / Out of Stock -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-title">Low Stock Alerts</span>
                <div class="kpi-icon bg-danger-subtle text-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="kpi-value text-danger"><?= number_format($low_stock_count) ?></div>
            <div class="small text-danger mt-2 font-medium">
                <i class="fa-solid fa-bell me-1"></i>Reorder Level Hit
            </div>
        </div>
    </div>

    <!-- 3. Pending Receipts -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-title">Pending Receipts</span>
                <div class="kpi-icon bg-warning-subtle text-warning">
                    <i class="fa-solid fa-inbox"></i>
                </div>
            </div>
            <div class="kpi-value"><?= number_format($pending_receipts) ?></div>
            <div class="small text-warning-emphasis mt-2 font-medium">
                <i class="fa-solid fa-truck-ramp-box me-1"></i>Incoming Vendors
            </div>
        </div>
    </div>

    <!-- 4. Pending Deliveries -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-title">Pending Deliveries</span>
                <div class="kpi-icon bg-info-subtle text-info">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
            <div class="kpi-value"><?= number_format($pending_deliveries) ?></div>
            <div class="small text-info-emphasis mt-2 font-medium">
                <i class="fa-solid fa-box-open me-1"></i>To Pick &amp; Pack
            </div>
        </div>
    </div>

    <!-- 5. Internal Transfers Scheduled -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-title">Internal Transfers</span>
                <div class="kpi-icon bg-secondary-subtle text-secondary">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                </div>
            </div>
            <div class="kpi-value"><?= number_format($scheduled_transfers) ?></div>
            <div class="small text-secondary mt-2 font-medium">
                <i class="fa-solid fa-route me-1"></i>Rack Movement
            </div>
        </div>
    </div>
</div>

<!-- Charts Section: Stock Valuation by Category & Inventory Status -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-odoo h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-0" style="color: #714B67;">
                        <i class="fa-solid fa-chart-column me-2 text-primary"></i>Stock Valuation by Product Category (₹)
                    </h6>
                    <small class="text-muted">Total inventory holding value: ₹<?= number_format($total_inventory_value, 2) ?></small>
                </div>
                <span class="badge bg-light text-dark border">Real-Time Data</span>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-odoo h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0" style="color: #714B67;">
                    <i class="fa-solid fa-bell me-2 text-danger"></i>Low Stock Warning Items
                </h6>
                <small class="text-muted">Items requiring immediate reorder</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($low_stock_rows)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                        All products are above minimum safety thresholds!
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush small">
                        <?php foreach (array_slice($low_stock_rows, 0, 5) as $low): ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($low['name']) ?></div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($low['sku']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?= number_format($low['current_stock']) ?> Left</span>
                                    <div class="text-muted smaller">Min: <?= $low['reorder_min_level'] ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top text-center py-2">
                <a href="products/index.php?filter=low_stock" class="small text-decoration-none fw-semibold" style="color: #00A09D;">
                    View All Low Stock Items &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Filters Bar (PDF Page 1 Specification) -->
<div class="card card-odoo mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center justify-content-between">
            <!-- Filter by Document Type -->
            <div class="col-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="small fw-bold text-muted me-2">Document Type:</span>
                    <button type="button" class="btn btn-sm btn-brand active" data-filter-type="all">All Operations</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-type="receipt">Receipts</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-type="delivery">Deliveries</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-type="internal">Internal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-type="adjustment">Adjustments</button>
                </div>
            </div>

            <!-- Dynamic Dropdowns & Instant Search -->
            <div class="col-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Filter by Status -->
                    <select class="form-select form-select-sm" id="statusFilter" style="width: 130px;">
                        <option value="all">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="waiting">Waiting</option>
                        <option value="ready">Ready</option>
                        <option value="done">Done</option>
                        <option value="canceled">Canceled</option>
                    </select>

                    <!-- Filter by Warehouse -->
                    <select class="form-select form-select-sm" id="warehouseFilter" style="width: 150px;">
                        <option value="all">All Warehouses</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['code']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control" id="opSearch" placeholder="Search Ref, Party...">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Operations Queue Table -->
<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0" style="color: #714B67;">
            <i class="fa-solid fa-list-check me-2 text-primary"></i>Live Operations Queue
        </h6>
        <span class="badge bg-light text-muted border font-monospace" id="visibleRowCount">Showing <?= count($all_operations) ?> Records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0" id="operationsTable">
            <thead>
                <tr>
                    <th class="ps-4">Reference #</th>
                    <th>Document Type</th>
                    <th>Destination / Source / Party</th>
                    <th>Location</th>
                    <th>Items</th>
                    <th class="text-center">Status</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_operations)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No operations recorded. <a href="setup.php">Initialize demo data</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_operations as $op): ?>
                        <tr data-type="<?= $op['doc_type'] ?>" data-status="<?= $op['status'] ?>" data-warehouse="<?= $op['warehouse_id'] ?? '' ?>">
                            <td class="ps-4 font-monospace fw-bold" style="color: #714B67;">
                                <?= htmlspecialchars($op['reference_no']) ?>
                            </td>
                            <td>
                                <?php if ($op['doc_type'] === 'receipt'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-arrow-down me-1"></i>Receipt</span>
                                <?php elseif ($op['doc_type'] === 'delivery'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-solid fa-arrow-up me-1"></i>Delivery</span>
                                <?php elseif ($op['doc_type'] === 'internal'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Internal</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa-solid fa-sliders me-1"></i>Adjustment</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($op['party_name']) ?></div>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime($op['created_at'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($op['location_name']) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold"><?= $op['items_count'] ?></span> <small class="text-muted">items</small>
                            </td>
                            <td class="text-center">
                                <?= get_status_badge($op['status']) ?>
                            </td>
                            <td class="pe-4 text-end">
                                <?php if ($op['doc_type'] === 'receipt'): ?>
                                    <a href="operations/receipt_view.php?id=<?= $op['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                        View &amp; Validate
                                    </a>
                                <?php elseif ($op['doc_type'] === 'delivery'): ?>
                                    <a href="operations/delivery_view.php?id=<?= $op['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                        Pick &amp; Pack
                                    </a>
                                <?php elseif ($op['doc_type'] === 'internal'): ?>
                                    <a href="operations/transfers.php" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                        Details
                                    </a>
                                <?php else: ?>
                                    <a href="operations/adjustments.php" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                        View
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Inline JavaScript for Chart.js and Live Dynamic Multi-Filters -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart.js Category Breakdown Chart
    const ctx = document.getElementById('categoryChart');
    if (ctx && typeof Chart !== 'undefined') {
        const catLabels = <?= json_encode(array_column($category_stats ?? [], 'category_name')) ?>;
        const catValues = <?= json_encode(array_column($category_stats ?? [], 'cat_val')) ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Inventory Value (₹)',
                    data: catValues,
                    backgroundColor: [
                        'rgba(113, 75, 103, 0.85)',
                        'rgba(0, 160, 157, 0.85)',
                        'rgba(59, 130, 246, 0.85)',
                        'rgba(245, 158, 11, 0.85)'
                    ],
                    borderColor: [
                        '#714B67',
                        '#00A09D',
                        '#3B82F6',
                        '#F59E0B'
                    ],
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '$' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    }

    // 2. Real-Time Dynamic Filters on Operations Queue
    const table = document.getElementById('operationsTable');
    const rows = table ? table.querySelectorAll('tbody tr') : [];
    let currentType = 'all';
    let currentStatus = 'all';
    let currentWarehouse = 'all';
    let searchQuery = '';

    function applyFilters() {
        let visibleCount = 0;
        rows.forEach(row => {
            const rType = row.getAttribute('data-type');
            const rStatus = row.getAttribute('data-status');
            const rWh = row.getAttribute('data-warehouse');
            const rText = row.innerText.toLowerCase();

            const matchType = (currentType === 'all' || rType === currentType);
            const matchStatus = (currentStatus === 'all' || rStatus === currentStatus);
            const matchWh = (currentWarehouse === 'all' || rWh === currentWarehouse);
            const matchSearch = (!searchQuery || rText.includes(searchQuery));

            if (matchType && matchStatus && matchWh && matchSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const counter = document.getElementById('visibleRowCount');
        if (counter) {
            counter.innerText = 'Showing ' + visibleCount + ' Records';
        }
    }

    // Doc Type buttons
    document.querySelectorAll('[data-filter-type]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-filter-type]').forEach(b => {
                b.classList.remove('btn-brand', 'active');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-brand', 'active');
            currentType = this.getAttribute('data-filter-type');
            applyFilters();
        });
    });

    // Status dropdown
    const statusSelect = document.getElementById('statusFilter');
    if (statusSelect) {
        statusSelect.addEventListener('change', function () {
            currentStatus = this.value;
            applyFilters();
        });
    }

    // Warehouse dropdown
    const whSelect = document.getElementById('warehouseFilter');
    if (whSelect) {
        whSelect.addEventListener('change', function () {
            currentWarehouse = this.value;
            applyFilters();
        });
    }

    // Search query
    const searchInput = document.getElementById('opSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchQuery = this.value.toLowerCase().trim();
            applyFilters();
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
