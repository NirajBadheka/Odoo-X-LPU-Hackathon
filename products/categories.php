<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_manager(); // Restricted to Inventory Managers - product management is a manager function
$current_user = get_current_user_data();

$page_title = 'Product Categories';
$page_subtitle = 'Organize catalog items by classification groups';

$error = '';

// Handle Category Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_category') {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Session expired. Please try again.';
    } elseif (empty($name) || empty($code)) {
        $error = 'Category name and unique code are required.';
    } else {
        $res = db_query("INSERT INTO categories (name, code, description, created_at) VALUES (?, ?, ?, NOW())", [
            $name, $code, $desc
        ], "sss");

        if ($res['success']) {
            set_flash('success', "Category '{$name}' created successfully!");
            header('Location: ' . BASE_URL . 'products/categories.php');
            exit;
        } else {
            $error = 'Failed to create category: ' . $res['error'];
        }
    }
}

// Fetch categories with product counts
$categories = db_select("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
");

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-tags me-2"></i>Product Categories
        </h4>
        <p class="text-muted small mb-0">Manage taxonomies for inventory organization.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-boxes-stacked me-1"></i> View Products
        </a>
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
            <i class="fa-solid fa-plus me-1"></i> Add Category
        </button>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($categories as $cat): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card card-odoo h-100 shadow-sm p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-purple text-white font-monospace" style="background:#714B67;">
                        <?= htmlspecialchars($cat['code']) ?>
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary small">
                        <?= $cat['product_count'] ?> Products
                    </span>
                </div>
                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($cat['name']) ?></h5>
                <p class="text-muted small mb-4 flex-grow-1">
                    <?= htmlspecialchars($cat['description'] ?: 'No description provided.') ?>
                </p>
                <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted smaller">Created <?= date('M Y', strtotime($cat['created_at'])) ?></span>
                    <a href="index.php" class="small fw-semibold text-decoration-none" style="color: #00A09D;">
                        Browse Items &rarr;
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: New Category -->
<div class="modal fade" id="newCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categories.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_category">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="color: #714B67;">
                        <i class="fa-solid fa-tag me-2"></i>Add Product Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Category Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Electrical &amp; Wiring" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Category Code *</label>
                        <input type="text" name="code" class="form-control font-monospace text-uppercase" placeholder="e.g. CAT-ELEC" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Category notes and scope..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
