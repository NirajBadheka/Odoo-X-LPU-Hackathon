<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Schedule Internal Transfer';
$page_subtitle = 'Move inventory between warehouses and storage locations';

$last_int = db_select_one("SELECT id FROM internal_transfers ORDER BY id DESC LIMIT 1");
$next_id = ($last_int['id'] ?? 0) + 1;
$default_ref = 'INT-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

$products = db_select("SELECT id, name, sku, unit_of_measure FROM products ORDER BY name ASC");
$locations = db_select("
    SELECT l.id, l.name as loc_name, w.name as wh_name, w.code as wh_code
    FROM locations l
    JOIN warehouses w ON l.warehouse_id = w.id
    ORDER BY w.name ASC, l.name ASC
");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_no = trim($_POST['reference_no'] ?? $default_ref);
    $product_id = intval($_POST['product_id'] ?? 0);
    $source_location_id = intval($_POST['source_location_id'] ?? 0);
    $destination_location_id = intval($_POST['destination_location_id'] ?? 0);
    $quantity = floatval($_POST['quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $auto_validate = isset($_POST['auto_validate']);
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh.';
    } elseif ($product_id <= 0 || $source_location_id <= 0 || $destination_location_id <= 0 || $quantity <= 0) {
        $error = 'Please fill out all required fields with a positive quantity.';
    } elseif ($source_location_id === $destination_location_id) {
        $error = 'Source and destination locations must be different.';
    } else {
        // Verify source stock
        $src_stock = db_select_one("SELECT quantity FROM stock_levels WHERE product_id = ? AND location_id = ?", [$product_id, $source_location_id], "ii");
        $avail = $src_stock['quantity'] ?? 0;

        if ($auto_validate && $avail < $quantity) {
            $error = "Insufficient stock at source location. Available: {$avail} units.";
        } else {
            $initial_status = $auto_validate ? STATUS_DONE : STATUS_READY;
            $val_at = $auto_validate ? date('Y-m-d H:i:s') : null;

            $res = db_query("
                INSERT INTO internal_transfers (reference_no, source_location_id, destination_location_id, product_id, quantity, status, notes, created_by, created_at, validated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ", [$reference_no, $source_location_id, $destination_location_id, $product_id, $quantity, $initial_status, $notes, $current_user['id'], $val_at], "siiidssis");

            if ($res['success']) {
                if ($auto_validate) {
                    update_location_stock($product_id, $source_location_id, -$quantity);
                    update_location_stock($product_id, $destination_location_id, $quantity);

                    record_stock_move(
                        MOVE_INTERNAL,
                        $reference_no,
                        $product_id,
                        $source_location_id,
                        $destination_location_id,
                        $quantity,
                        $current_user['id'],
                        $notes ?: "Internal move"
                    );
                }

                set_flash('success', "Internal Transfer {$reference_no} processed successfully!");
                header('Location: ' . BASE_URL . 'operations/transfers.php');
                exit;
            } else {
                $error = 'Failed to create transfer: ' . $res['error'];
            }
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
                    <i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Schedule Internal Transfer
                </h4>
                <p class="text-muted small mb-0">Move stock from Main Store to Production Rack or between warehouses.</p>
            </div>
            <a href="transfers.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Transfers
            </a>
        </div>

        <div class="card card-odoo shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="transfer_create.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Transfer Reference #</label>
                        <input type="text" name="reference_no" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($default_ref) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Select Product to Move *</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Choose Product...</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>) - <?= htmlspecialchars($p['unit_of_measure']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Source Location (From) *</label>
                            <select name="source_location_id" class="form-select" required>
                                <option value="">Select Origin...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['wh_code']) ?> &rarr; <?= htmlspecialchars($loc['loc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Destination Location (To) *</label>
                            <select name="destination_location_id" class="form-select" required>
                                <option value="">Select Target...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['wh_code']) ?> &rarr; <?= htmlspecialchars($loc['loc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Quantity to Relocate *</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" placeholder="e.g. 50.00" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Transfer Reason / Work Order Ref</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Moved from Main Store to Production Rack for chair frame assembly..."></textarea>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_validate" id="autoValidate" checked>
                            <label class="form-check-label fw-bold text-dark small" for="autoValidate">
                                Execute transfer immediately upon saving (Update location balances now)
                            </label>
                            <div class="text-muted smaller">
                                Deducts from origin rack, credits target rack, and writes double-entry move record in ledger.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="transfers.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Confirm Internal Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
