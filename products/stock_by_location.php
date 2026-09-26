<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Stock by Location';
$page_subtitle = 'Inventory availability distributed across warehouses & racks';

// Fetch all active locations
$locations = db_select("
    SELECT l.id, l.name as loc_name, l.barcode, w.name as wh_name, w.code as wh_code
    FROM locations l
    JOIN warehouses w ON l.warehouse_id = w.id
    ORDER BY w.name ASC, l.name ASC
");

// Fetch products and their stocks in each location
$products = db_select("
    SELECT p.id, p.name, p.sku, p.unit_of_measure, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.name ASC
");

// Fetch all stock levels mapped by [product_id][location_id]
$stock_matrix = [];
$raw_stocks = db_select("SELECT product_id, location_id, quantity FROM stock_levels");
foreach ($raw_stocks as $s) {
    $stock_matrix[$s['product_id']][$s['location_id']] = $s['quantity'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-map-location-dot me-2"></i>Stock Availability by Location
        </h4>
        <p class="text-muted small mb-0">Multi-warehouse inventory distribution matrix across specific storage bays and racks.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-boxes-stacked me-1"></i> Products Catalog
        </a>
        <a href="../operations/transfers.php" class="btn btn-sm btn-brand">
            <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Transfer Stock
        </a>
    </div>
</div>

<!-- Search Input -->
<div class="card card-odoo mb-4">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="small fw-bold text-muted">Real-Time Multi-Location Matrix</span>
            <div class="input-group input-group-sm" style="width: 280px;">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" class="form-control" id="matrixSearch" placeholder="Filter by product name, SKU...">
            </div>
        </div>
    </div>
</div>

<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-odoo table-bordered align-middle mb-0" id="matrixTable">
            <thead>
                <tr>
                    <th class="ps-4" style="min-width: 240px;">Product &amp; SKU</th>
                    <th class="text-center" style="min-width: 100px;">Total Stock</th>
                    <?php foreach ($locations as $loc): ?>
                        <th class="text-center" style="min-width: 130px;">
                            <div class="fw-bold"><?= htmlspecialchars($loc['loc_name']) ?></div>
                            <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($loc['wh_code']) ?>
                            </span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php 
                        $total_row_stock = 0;
                        foreach ($locations as $loc) {
                            $total_row_stock += $stock_matrix[$p['id']][$loc['id']] ?? 0;
                        }
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($p['sku']) ?></small>
                            <span class="badge bg-light text-muted border ms-1 small"><?= htmlspecialchars($p['category_name']) ?></span>
                        </td>
                        <td class="text-center bg-light">
                            <span class="fw-extrabold fs-6 <?= ($total_row_stock > 0) ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($total_row_stock, 1) ?>
                            </span>
                            <small class="text-muted d-block"><?= htmlspecialchars($p['unit_of_measure']) ?></small>
                        </td>
                        <?php foreach ($locations as $loc): ?>
                            <?php $qty = $stock_matrix[$p['id']][$loc['id']] ?? 0; ?>
                            <td class="text-center <?= ($qty > 0) ? 'bg-white' : 'bg-light text-muted' ?>">
                                <?php if ($qty > 0): ?>
                                    <span class="fw-bold text-dark fs-6"><?= number_format($qty, 1) ?></span>
                                    <small class="text-muted d-block"><?= htmlspecialchars($p['unit_of_measure']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted opacity-50">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('matrixSearch');
    const table = document.getElementById('matrixTable');
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
