<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_manager(); // Restricted to Inventory Managers - product management is a manager function
$current_user = get_current_user_data();

$page_title = 'Edit Product';
$page_subtitle = 'Update product specifications and reordering thresholds';

$id = intval($_GET['id'] ?? 0);
$product = db_select_one("SELECT * FROM products WHERE id = ?", [$id], "i");

if (!$product) {
    set_flash('error', 'Product not found.');
    header('Location: ' . BASE_URL . 'products/index.php');
    exit;
}

$categories = db_select("SELECT id, name FROM categories ORDER BY name ASC");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $unit_of_measure = trim($_POST['unit_of_measure'] ?? 'Units');
    $cost_price = floatval($_POST['cost_price'] ?? 0.00);
    $selling_price = floatval($_POST['selling_price'] ?? 0.00);
    $reorder_min_level = intval($_POST['reorder_min_level'] ?? 10);
    $reorder_max_level = intval($_POST['reorder_max_level'] ?? 100);
    $description = trim($_POST['description'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($name) || $category_id <= 0) {
        $error = 'Please provide product name and valid category.';
    } else {
        $sql = "UPDATE products SET name = ?, category_id = ?, unit_of_measure = ?, cost_price = ?, selling_price = ?, reorder_min_level = ?, reorder_max_level = ?, description = ? WHERE id = ?";
        $res = db_query($sql, [
            $name, $category_id, $unit_of_measure, $cost_price, $selling_price, $reorder_min_level, $reorder_max_level, $description, $id
        ], "sisddiisi");

        if ($res['success']) {
            set_flash('success', "Product '{$name}' updated successfully.");
            header('Location: ' . BASE_URL . 'products/index.php');
            exit;
        } else {
            $error = 'Failed to update: ' . $res['error'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #714B67;">
                    <i class="fa-solid fa-pencil me-2"></i>Edit Product: <?= htmlspecialchars($product['name']) ?>
                </h4>
                <div class="text-muted small">SKU: <span class="font-monospace fw-bold"><?= htmlspecialchars($product['sku']) ?></span></div>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Products
            </a>
        </div>

        <div class="card card-odoo shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Product Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">SKU (Immutable)</label>
                            <input type="text" class="form-control font-monospace bg-light" value="<?= htmlspecialchars($product['sku']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Unit of Measure (UoM) *</label>
                            <select name="unit_of_measure" class="form-select" required>
                                <option value="Units" <?= ($product['unit_of_measure'] === 'Units') ? 'selected' : '' ?>>Units (pcs)</option>
                                <option value="kg" <?= ($product['unit_of_measure'] === 'kg') ? 'selected' : '' ?>>Kilograms (kg)</option>
                                <option value="Liters" <?= ($product['unit_of_measure'] === 'Liters') ? 'selected' : '' ?>>Liters (L)</option>
                                <option value="Boxes" <?= ($product['unit_of_measure'] === 'Boxes') ? 'selected' : '' ?>>Boxes (bx)</option>
                                <option value="Meters" <?= ($product['unit_of_measure'] === 'Meters') ? 'selected' : '' ?>>Meters (m)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Cost Price (₹)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= htmlspecialchars($product['cost_price']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Selling Price (₹)</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" value="<?= htmlspecialchars($product['selling_price']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Min Reorder Level</label>
                            <input type="number" name="reorder_min_level" class="form-control" value="<?= htmlspecialchars($product['reorder_min_level']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Max Target Level</label>
                            <input type="number" name="reorder_max_level" class="form-control" value="<?= htmlspecialchars($product['reorder_max_level']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Description / Notes</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="fa-solid fa-save me-1"></i> Update Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
