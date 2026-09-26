<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_manager(); // Restricted to Inventory Managers - manages outgoing stock
$current_user = get_current_user_data();

$page_title = 'New Delivery Order (Outgoing Stock)';
$page_subtitle = 'Schedule customer sales shipment and dispatch';

// Generate Next Reference No
$last_del = db_select_one("SELECT id FROM delivery_orders ORDER BY id DESC LIMIT 1");
$next_id = ($last_del['id'] ?? 0) + 1;
$default_ref = 'DEL-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

// Fetch Locations & Products
$locations = db_select("
    SELECT l.id, l.name as loc_name, w.name as wh_name, w.code as wh_code
    FROM locations l
    JOIN warehouses w ON l.warehouse_id = w.id
    ORDER BY w.name ASC, l.name ASC
");

$products = db_select("SELECT id, name, sku, unit_of_measure, selling_price FROM products ORDER BY name ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_no = trim($_POST['reference_no'] ?? $default_ref);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $source_location_id = intval($_POST['source_location_id'] ?? 0);
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($customer_name) || $source_location_id <= 0 || empty($product_ids)) {
        $error = 'Please enter customer name, source location, and at least one item.';
    } else {
        $res = db_query("
            INSERT INTO delivery_orders (reference_no, customer_name, source_location_id, status, shipping_address, created_by, created_at)
            VALUES (?, ?, ?, 'waiting', ?, ?, NOW())
        ", [$reference_no, $customer_name, $source_location_id, $shipping_address, $current_user['id']], "ssisi");

        if ($res['success']) {
            $delivery_id = $res['insert_id'];

            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = intval($product_ids[$i]);
                $qty = floatval($quantities[$i] ?? 0);
                $price = floatval($prices[$i] ?? 0);

                if ($pid > 0 && $qty > 0) {
                    db_query("INSERT INTO delivery_items (delivery_id, product_id, quantity_demanded, quantity_picked, quantity_packed, unit_price) VALUES (?, ?, ?, 0.00, 0.00, ?)", [
                        $delivery_id, $pid, $qty, $price
                    ], "iidd");
                }
            }

            set_flash('success', "Delivery Order {$reference_no} created. Proceed to Pick & Pack.");
            header('Location: ' . BASE_URL . 'operations/delivery_view.php?id=' . $delivery_id);
            exit;
        } else {
            $error = 'Failed to create delivery order: ' . $res['error'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #714B67;">
                    <i class="fa-solid fa-truck-ramp-box me-2"></i>Create Delivery Order
                </h4>
                <p class="text-muted small mb-0">Prepare customer shipment for warehouse picking &amp; packing.</p>
            </div>
            <a href="deliveries.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Deliveries
            </a>
        </div>

        <div class="card card-odoo shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="delivery_create.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Delivery Reference #</label>
                            <input type="text" name="reference_no" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($default_ref) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="e.g. Acme Corp" required value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Source Dispatch Location *</label>
                            <select name="source_location_id" class="form-select" required>
                                <option value="">Select Source...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['wh_code']) ?> &rarr; <?= htmlspecialchars($loc['loc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-boxes-stacked me-1 text-danger"></i> Demanded Products
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="addRowBtn">
                                <i class="fa-solid fa-plus me-1"></i> Add Item Line
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light small text-muted">
                                    <tr>
                                        <th style="width: 50%;">Product *</th>
                                        <th style="width: 25%;">Demanded Quantity *</th>
                                        <th style="width: 20%;">Unit Price (₹)</th>
                                        <th style="width: 5%;" class="text-center"><i class="fa-solid fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr class="item-row">
                                        <td>
                                            <select name="product_id[]" class="form-select product-select" required>
                                                <option value="">Select Product...</option>
                                                <?php foreach ($products as $p): ?>
                                                    <option value="<?= $p['id'] ?>" data-uom="<?= htmlspecialchars($p['unit_of_measure']) ?>" data-price="<?= $p['selling_price'] ?>">
                                                        <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.01" name="quantity[]" class="form-control" placeholder="0.00" required>
                                                <span class="input-group-text small uom-badge">Units</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="unit_price[]" class="form-control" placeholder="0.00">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger remove-row" disabled>
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Customer Shipping Address &amp; Instructions</label>
                        <textarea name="shipping_address" class="form-control" rows="2" placeholder="Destination shipping address, dock instructions..."></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="deliveries.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="fa-solid fa-dolly me-1"></i> Create Order &amp; Proceed to Pick
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsBody = document.getElementById('itemsBody');
    const addRowBtn = document.getElementById('addRowBtn');

    function attachRowListeners(row) {
        const prodSelect = row.querySelector('.product-select');
        const uomBadge = row.querySelector('.uom-badge');
        const priceInput = row.querySelector('input[name="unit_price[]"]');
        const removeBtn = row.querySelector('.remove-row');

        prodSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const uom = selected.getAttribute('data-uom') || 'Units';
            const price = selected.getAttribute('data-price') || '0.00';
            uomBadge.innerText = uom;
            if (priceInput && (!priceInput.value || priceInput.value === '0.00')) {
                priceInput.value = price;
            }
        });

        removeBtn.addEventListener('click', function () {
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
            }
        });
    }

    attachRowListeners(document.querySelector('.item-row'));

    addRowBtn.addEventListener('click', function () {
        const firstRow = document.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelector('select').value = '';
        newRow.querySelector('input[name="quantity[]"]').value = '';
        newRow.querySelector('input[name="unit_price[]"]').value = '';
        newRow.querySelector('.uom-badge').innerText = 'Units';
        newRow.querySelector('.remove-row').removeAttribute('disabled');

        itemsBody.appendChild(newRow);
        attachRowListeners(newRow);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
