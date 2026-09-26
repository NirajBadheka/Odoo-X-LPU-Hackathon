<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_manager(); // Restricted to Inventory Managers - manages incoming stock
$current_user = get_current_user_data();

$page_title = 'New Receipt (Incoming Goods)';
$page_subtitle = 'Receive shipments from vendors into warehouse inventory';

// Generate Next Reference No
$last_receipt = db_select_one("SELECT id FROM receipts ORDER BY id DESC LIMIT 1");
$next_id = ($last_receipt['id'] ?? 0) + 1;
$default_ref = 'REC-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

// Fetch Locations & Products
$locations = db_select("
    SELECT l.id, l.name as loc_name, w.name as wh_name, w.code as wh_code
    FROM locations l
    JOIN warehouses w ON l.warehouse_id = w.id
    ORDER BY w.name ASC, l.name ASC
");

$products = db_select("SELECT id, name, sku, unit_of_measure, cost_price FROM products ORDER BY name ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_no = trim($_POST['reference_no'] ?? $default_ref);
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $destination_location_id = intval($_POST['destination_location_id'] ?? 0);
    $status = $_POST['status'] ?? 'ready';
    $notes = trim($_POST['notes'] ?? '');
    $auto_validate = isset($_POST['auto_validate']);
    $token = $_POST['csrf_token'] ?? '';

    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $costs = $_POST['unit_cost'] ?? [];

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please try again.';
    } elseif (empty($supplier_name) || $destination_location_id <= 0 || empty($product_ids)) {
        $error = 'Please enter vendor name, destination location, and at least one product.';
    } else {
        // Create Receipt Record
        $initial_status = $auto_validate ? STATUS_DONE : $status;
        $val_by = $auto_validate ? $current_user['id'] : null;
        $val_at = $auto_validate ? date('Y-m-d H:i:s') : null;

        $res = db_query("
            INSERT INTO receipts (reference_no, supplier_name, destination_location_id, status, notes, created_by, validated_by, created_at, validated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ", [$reference_no, $supplier_name, $destination_location_id, $initial_status, $notes, $current_user['id'], $val_by, $val_at], "ssissiis");

        if ($res['success']) {
            $receipt_id = $res['insert_id'];

            // Insert Items
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = intval($product_ids[$i]);
                $qty = floatval($quantities[$i] ?? 0);
                $cost = floatval($costs[$i] ?? 0);

                if ($pid > 0 && $qty > 0) {
                    $qty_received = $auto_validate ? $qty : 0.00;
                    db_query("INSERT INTO receipt_items (receipt_id, product_id, quantity_expected, quantity_received, unit_cost) VALUES (?, ?, ?, ?, ?)", [
                        $receipt_id, $pid, $qty, $qty_received, $cost
                    ], "iiddi");

                    // If auto-validate is checked, update stock level & write to stock moves ledger!
                    if ($auto_validate) {
                        update_location_stock($pid, $destination_location_id, $qty);
                        record_stock_move(
                            MOVE_RECEIPT,
                            $reference_no,
                            $pid,
                            null, // External vendor
                            $destination_location_id,
                            $qty,
                            $current_user['id'],
                            "Receipt from " . $supplier_name
                        );
                    }
                }
            }

            set_flash('success', "Receipt {$reference_no} created successfully!" . ($auto_validate ? " Stock updated automatically in ledger." : ""));
            header('Location: ' . BASE_URL . 'operations/receipt_view.php?id=' . $receipt_id);
            exit;
        } else {
            $error = 'Failed to create receipt: ' . $res['error'];
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
                    <i class="fa-solid fa-file-circle-plus me-2"></i>Create Stock Receipt
                </h4>
                <p class="text-muted small mb-0">Record incoming purchase shipment from suppliers.</p>
            </div>
            <a href="receipts.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Receipts
            </a>
        </div>

        <div class="card card-odoo shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="receipt_create.php" id="receiptForm">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Receipt Reference #</label>
                            <input type="text" name="reference_no" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($default_ref) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Supplier / Vendor *</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="e.g. Tata Steel Ltd" required value="<?= htmlspecialchars($_POST['supplier_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Destination Location *</label>
                            <select name="destination_location_id" class="form-select" required>
                                <option value="">Select Destination...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['wh_code']) ?> &rarr; <?= htmlspecialchars($loc['loc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Products Section -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-boxes-stacked me-1 text-primary"></i> Incoming Products
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                                <i class="fa-solid fa-plus me-1"></i> Add Item Line
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light small text-muted">
                                    <tr>
                                        <th style="width: 50%;">Product *</th>
                                        <th style="width: 25%;">Quantity Received / Expected *</th>
                                        <th style="width: 20%;">Unit Cost (₹)</th>
                                        <th style="width: 5%;" class="text-center"><i class="fa-solid fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Row 1 Default -->
                                    <tr class="item-row">
                                        <td>
                                            <select name="product_id[]" class="form-select product-select" required>
                                                <option value="">Select Product...</option>
                                                <?php foreach ($products as $p): ?>
                                                    <option value="<?= $p['id'] ?>" data-uom="<?= htmlspecialchars($p['unit_of_measure']) ?>" data-cost="<?= $p['cost_price'] ?>">
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
                                            <input type="number" step="0.01" name="unit_cost[]" class="form-control" placeholder="0.00">
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
                        <label class="form-label small fw-bold text-muted">Shipment Notes / Carrier Details</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Delivery truck #402, bill of lading..."></textarea>
                    </div>

                    <!-- Auto Validate Option -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_validate" id="autoValidate" checked>
                            <label class="form-check-label fw-bold text-dark small" for="autoValidate">
                                Validate &amp; increase inventory stock immediately upon saving
                            </label>
                            <div class="text-muted smaller">
                                If enabled, the destination location will instantly receive these units and record an entry into the Stock Moves ledger.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="receipts.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="fa-solid fa-check me-1"></i> Confirm &amp; Save Receipt
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

    // Handle dynamic product selection UoM and cost update
    function attachRowListeners(row) {
        const prodSelect = row.querySelector('.product-select');
        const uomBadge = row.querySelector('.uom-badge');
        const costInput = row.querySelector('input[name="unit_cost[]"]');
        const removeBtn = row.querySelector('.remove-row');

        prodSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const uom = selected.getAttribute('data-uom') || 'Units';
            const cost = selected.getAttribute('data-cost') || '0.00';
            uomBadge.innerText = uom;
            if (costInput && (!costInput.value || costInput.value === '0.00')) {
                costInput.value = cost;
            }
        });

        removeBtn.addEventListener('click', function () {
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
            }
        });
    }

    // Attach to initial row
    attachRowListeners(document.querySelector('.item-row'));

    // Add new row
    addRowBtn.addEventListener('click', function () {
        const firstRow = document.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        // Reset inputs in new row
        newRow.querySelector('select').value = '';
        newRow.querySelector('input[name="quantity[]"]').value = '';
        newRow.querySelector('input[name="unit_cost[]"]').value = '';
        newRow.querySelector('.uom-badge').innerText = 'Units';
        newRow.querySelector('.remove-row').removeAttribute('disabled');

        itemsBody.appendChild(newRow);
        attachRowListeners(newRow);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
