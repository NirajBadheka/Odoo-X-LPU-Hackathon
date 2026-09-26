<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Product Management';
$page_subtitle = 'Catalog, inventory levels & safety reordering rules';

// Handle filter param if set (e.g. ?filter=low_stock)
$filter_mode = $_GET['filter'] ?? 'all';

// Fetch Products with category and aggregated stock levels
$sql = "
    SELECT p.*, c.name as category_name, c.code as category_code,
           COALESCE(SUM(sl.quantity), 0) as total_stock
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_levels sl ON p.id = sl.product_id
    GROUP BY p.id
    ORDER BY p.name ASC
";
$products = db_select($sql);

if ($filter_mode === 'low_stock') {
    $products = array_filter($products, function($p) {
        return $p['total_stock'] <= $p['reorder_min_level'];
    });
}

// Fetch categories for filtering
$categories = db_select("SELECT id, name FROM categories ORDER BY name ASC");

include __DIR__ . '/../includes/header.php';
?>

<!-- Header Toolbar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-boxes-stacked me-2"></i>Product Catalog &amp; Stock Levels
        </h4>
        <p class="text-muted small mb-0">Monitor SKU inventory balances, threshold alerts, and pricing.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="stock_by_location.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-map-location-dot me-1"></i> Stock by Location
        </a>
        <a href="categories.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-tags me-1"></i> Categories
        </a>
        <a href="create.php" class="btn btn-sm btn-brand">
            <i class="fa-solid fa-plus me-1"></i> New Product
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card card-odoo mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-auto">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold text-muted">Category:</span>
                    <select class="form-select form-select-sm" id="categoryFilter" style="width: 200px;">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="btn-group btn-group-sm">
                        <a href="index.php" class="btn <?= ($filter_mode === 'all') ? 'btn-brand active' : 'btn-outline-secondary' ?>">All</a>
                        <a href="index.php?filter=low_stock" class="btn <?= ($filter_mode === 'low_stock') ? 'btn-danger active' : 'btn-outline-danger' ?>">
                            <i class="fa-solid fa-bell me-1"></i>Low Stock Only
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-auto">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" class="form-control" id="productSearch" placeholder="Search Product, SKU...">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Table -->
<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0" id="productTable">
            <thead>
                <tr>
                    <th class="ps-4">Product Name</th>
                    <th>SKU / Code</th>
                    <th>Category</th>
                    <th class="text-end">Cost / Sell</th>
                    <th class="text-end">Available Stock</th>
                    <th class="text-center">Reorder Rule (Min / Max)</th>
                    <th class="text-center">Stock Status</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary d-block"></i>
                            No products found matching criteria. <a href="create.php">Add a new product now</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                            $is_low_stock = $p['total_stock'] <= $p['reorder_min_level'];
                            $is_out_of_stock = $p['total_stock'] <= 0;
                        ?>
                        <tr data-category="<?= htmlspecialchars($p['category_name']) ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #714B67;">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['description'] ? substr($p['description'], 0, 45) . '...' : 'No description') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($p['sku']) ?></span>
                            </td>
                            <td>
                                <span class="text-secondary fw-medium"><?= htmlspecialchars($p['category_name']) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold text-dark">₹<?= number_format($p['selling_price'], 2) ?></div>
                                <small class="text-muted">Cost: ₹<?= number_format($p['cost_price'], 2) ?></small>
                            </td>
                            <td class="text-end">
                                <span class="fw-extrabold fs-6 <?= $is_low_stock ? 'text-danger' : 'text-dark' ?>">
                                    <?= number_format($p['total_stock'], 2) ?>
                                </span>
                                <small class="text-muted"><?= htmlspecialchars($p['unit_of_measure']) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border font-monospace">
                                    <?= $p['reorder_min_level'] ?> / <?= $p['reorder_max_level'] ?> <?= htmlspecialchars($p['unit_of_measure']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($is_out_of_stock): ?>
                                    <span class="badge bg-danger text-white px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>Out of Stock</span>
                                <?php elseif ($is_low_stock): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Low Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Healthy</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit Product">
                                    <i class="fa-solid fa-pencil"></i>
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
    const table = document.getElementById('productTable');
    const rows = table ? table.querySelectorAll('tbody tr') : [];
    const catSelect = document.getElementById('categoryFilter');
    const searchInput = document.getElementById('productSearch');

    function filterProducts() {
        const cat = catSelect ? catSelect.value : 'all';
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        rows.forEach(row => {
            const rCat = row.getAttribute('data-category');
            const rText = row.innerText.toLowerCase();

            const matchCat = (cat === 'all' || rCat === cat);
            const matchSearch = (!query || rText.includes(query));

            if (matchCat && matchSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (catSelect) catSelect.addEventListener('change', filterProducts);
    if (searchInput) searchInput.addEventListener('input', filterProducts);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
