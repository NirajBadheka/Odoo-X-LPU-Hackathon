<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_manager(); // Restricted to Inventory Managers - product management is a manager function
$current_user = get_current_user_data();

$page_title = 'Create New Product';
$page_subtitle = 'Add product to master catalog with optional initial stock';

$categories = db_select("SELECT id, name FROM categories ORDER BY name ASC");
$locations = db_select("
    SELECT l.id, l.name as loc_name, w.name as wh_name, w.code as wh_code
    FROM locations l
    JOIN warehouses w ON l.warehouse_id = w.id
    ORDER BY w.name ASC, l.name ASC
");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $category_id = intval($_POST['category_id'] ?? 0);
    $unit_of_measure = trim($_POST['unit_of_measure'] ?? 'Units');
    $cost_price = floatval($_POST['cost_price'] ?? 0.00);
    $selling_price = floatval($_POST['selling_price'] ?? 0.00);
    $reorder_min_level = intval($_POST['reorder_min_level'] ?? 10);
    $reorder_max_level = intval($_POST['reorder_max_level'] ?? 100);
    $initial_stock = floatval($_POST['initial_stock'] ?? 0.00);
    $initial_location_id = intval($_POST['initial_location_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($name) || empty($sku) || $category_id <= 0) {
        $error = 'Please fill out product name, SKU code, and category.';
    } else {
        // Check if SKU already exists
        $existing = db_select_one("SELECT id FROM products WHERE sku = ?", [$sku], "s");
        if ($existing) {
            $error = "A product with SKU '{$sku}' already exists. Please use a unique SKU.";
        } else {
            // Insert product
            $sql = "INSERT INTO products (category_id, name, sku, unit_of_measure, cost_price, selling_price, reorder_min_level, reorder_max_level, description, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $res = db_query($sql, [
                $category_id, $name, $sku, $unit_of_measure, $cost_price, $selling_price, $reorder_min_level, $reorder_max_level, $description
            ], "isssddiis");

            if ($res['success']) {
                $new_product_id = $res['insert_id'];

                // Handle optional initial stock
                if ($initial_stock > 0 && $initial_location_id > 0) {
                    update_location_stock($new_product_id, $initial_location_id, $initial_stock);
                    
                    // Log to Stock Moves Ledger
                    record_stock_move(
                        MOVE_RECEIPT,
                        "INIT-" . $sku,
                        $new_product_id,
                        null,
                        $initial_location_id,
                        $initial_stock,
                        $current_user['id'],
                        "Initial stock on product creation"
                    );
                }

                set_flash('success', "Product '{$name}' ({$sku}) created successfully!");
                header('Location: ' . BASE_URL . 'products/index.php');
                exit;
            } else {
                $error = 'Failed to create product: ' . $res['error'];
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #714B67;">
                    <i class="fa-solid fa-plus-circle me-2"></i>Create New Product
                </h4>
                <p class="text-muted small mb-0">Define product master details, SKU, and safety reorder rules.</p>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Catalog
            </a>
        </div>

        <div class="card card-odoo shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="create.php">
                    <?= csrf_field() ?>

                    <h6 class="fw-bold text-uppercase small text-muted mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-tag me-1 text-primary"></i> 1. Basic Identification
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-muted">Product Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Heavy Duty Steel Rods 25mm" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted">SKU / Code *</label>
                            <div class="input-group">
                                <input type="text" name="sku" id="skuInput" class="form-control font-monospace text-uppercase" placeholder="e.g. STL-ROD-25" required value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="generateSku()">Auto</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Product Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Unit of Measure (UoM) *</label>
                            <select name="unit_of_measure" class="form-select" required>
                                <option value="Units">Units (pcs)</option>
                                <option value="kg">Kilograms (kg)</option>
                                <option value="Liters">Liters (L)</option>
                                <option value="Boxes">Boxes (bx)</option>
                                <option value="Meters">Meters (m)</option>
                                <option value="Pallets">Pallets (plt)</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-uppercase small text-muted mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-coins me-1 text-warning"></i> 2. Pricing &amp; Reordering Thresholds
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Cost Price (₹)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($_POST['cost_price'] ?? '0.00') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Selling Price (₹)</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($_POST['selling_price'] ?? '0.00') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Min Safety Level</label>
                            <input type="number" name="reorder_min_level" class="form-control" placeholder="10" value="<?= htmlspecialchars($_POST['reorder_min_level'] ?? '10') ?>">
                            <div class="form-text smaller">Triggers Low Stock Alert</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Max Target Level</label>
                            <input type="number" name="reorder_max_level" class="form-control" placeholder="100" value="<?= htmlspecialchars($_POST['reorder_max_level'] ?? '100') ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold text-uppercase small text-muted mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-warehouse me-1 text-info"></i> 3. Initial Stock Placement (Optional)
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Initial Quantity Received</label>
                            <input type="number" step="0.01" name="initial_stock" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($_POST['initial_stock'] ?? '0') ?>">
                            <div class="form-text smaller">Stock will be credited immediately to location</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Initial Storage Location</label>
                            <select name="initial_location_id" class="form-select">
                                <option value="">Select Location...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['wh_code']) ?> &rarr; <?= htmlspecialchars($loc['loc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Description / Specifications</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Material grade, supplier part number, notes..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="fa-solid fa-save me-1"></i> Save Product to Catalog
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function generateSku() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = 'SKU-';
    for (let i = 0; i < 6; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('skuInput').value = code;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
