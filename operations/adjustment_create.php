<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'New Stock Adjustment';
$page_subtitle = 'Reconcile physical inventory count against book stock';

$last_adj = db_select_one("SELECT id FROM stock_adjustments ORDER BY id DESC LIMIT 1");
$next_id = ($last_adj['id'] ?? 0) + 1;
$default_ref = 'ADJ-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

$products = db_select("SELECT id, name, sku, unit_of_measure FROM products ORDER BY name ASC");
$locations = db_select("
    SELECT l.id, l.name as loc_name, w.name as wh_name, w.code as wh_code
    FROM locations l
    JOIN warehouses w ON l.warehouse_id = w.id
    ORDER BY w.name ASC, l.name ASC
");

// Pre-load current stocks for instant client-side lookup: [product_id_location_id => qty]
$raw_stocks = db_select("SELECT product_id, location_id, quantity FROM stock_levels");
$stock_lookup = [];
foreach ($raw_stocks as $s) {
    $stock_lookup[$s['product_id'] . '_' . $s['location_id']] = floatval($s['quantity']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_no = trim($_POST['reference_no'] ?? $default_ref);
    $product_id = intval($_POST['product_id'] ?? 0);
    $location_id = intval($_POST['location_id'] ?? 0);
    $recorded_quantity = floatval($_POST['recorded_quantity'] ?? 0.00);
    $counted_quantity = floatval($_POST['counted_quantity'] ?? 0.00);
    $difference = $counted_quantity - $recorded_quantity;
    $reason = $_POST['reason'] ?? 'discrepancy';
    $notes = trim($_POST['notes'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh.';
    } elseif ($product_id <= 0 || $location_id <= 0) {
        $error = 'Please select both a product and location.';
    } else {
        // Insert Adjustment
        $res = db_query("
            INSERT INTO stock_adjustments (reference_no, product_id, location_id, recorded_quantity, counted_quantity, difference_quantity, reason, status, notes, created_by, created_at, validated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'done', ?, ?, NOW(), NOW())
        ", [$reference_no, $product_id, $location_id, $recorded_quantity, $counted_quantity, $difference, $reason, $notes, $current_user['id']], "siidddssi");

        if ($res['success']) {
            // Update physical location stock to exact counted quantity
            $existing = db_select_one("SELECT id FROM stock_levels WHERE product_id = ? AND location_id = ?", [$product_id, $location_id], "ii");
            if ($existing) {
                db_query("UPDATE stock_levels SET quantity = ?, last_updated = NOW() WHERE id = ?", [$counted_quantity, $existing['id']], "di");
            } else {
                db_query("INSERT INTO stock_levels (product_id, location_id, quantity, last_updated) VALUES (?, ?, ?, NOW())", [$product_id, $location_id, $counted_quantity], "iid");
            }

            // Record to Stock Moves Ledger
            record_stock_move(
                MOVE_ADJUSTMENT,
                $reference_no,
                $product_id,
                ($difference < 0) ? $location_id : null,
                ($difference > 0) ? $location_id : null,
                $difference,
                $current_user['id'],
                "Adjustment reason: {$reason}. Notes: {$notes}"
            );

            set_flash('success', "Adjustment {$reference_no} executed! Stock updated to {$counted_quantity} units.");
            header('Location: ' . BASE_URL . 'operations/adjustments.php');
            exit;
        } else {
            $error = 'Failed to record adjustment: ' . $res['error'];
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
                    <i class="fa-solid fa-sliders me-2"></i>Record Physical Inventory Adjustment
                </h4>
                <p class="text-muted small mb-0">Correct stock mismatches, enter physical counts, and write off damages.</p>
            </div>
            <a href="adjustments.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Adjustments
            </a>
        </div>

        <div class="card card-odoo shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="adjustment_create.php" id="adjustmentForm">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Adjustment Reference #</label>
                        <input type="text" name="reference_no" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($default_ref) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Select Product *</label>
                            <select name="product_id" id="prodSelect" class="form-select" required>
                                <option value="">Choose Product...</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Warehouse Storage Location *</label>
                            <select name="location_id" id="locSelect" class="form-select" required>
                                <option value="">Choose Location...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['wh_code']) ?> &rarr; <?= htmlspecialchars($loc['loc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Comparison Grid -->
                    <div class="row g-3 p-3 bg-light rounded-3 border mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Recorded Stock</label>
                            <input type="number" step="0.01" name="recorded_quantity" id="recordedQty" class="form-control bg-white fw-bold" value="0.00" readonly>
                            <div class="form-text smaller">Theoretical balance in ledger</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Actual Counted Physical Stock *</label>
                            <input type="number" step="0.01" name="counted_quantity" id="countedQty" class="form-control border-primary fw-bold fs-6" placeholder="0.00" required>
                            <div class="form-text smaller">Enter verified physical count</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Difference (Variance)</label>
                            <input type="text" id="diffDisplay" class="form-control font-monospace fw-extrabold" value="0.00" readonly>
                            <div class="form-text smaller">Auto-calculated delta</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Reason for Discrepancy *</label>
                        <select name="reason" class="form-select" required>
                            <option value="damage">Damaged Goods / Scrap Write-Off (e.g. 3kg bent rods)</option>
                            <option value="discrepancy" selected>Physical Count Mismatch (Human counting error)</option>
                            <option value="expiry">Expired / Spoiled Goods</option>
                            <option value="theft">Shrinkage / Lost / Stolen Inventory</option>
                            <option value="other">Other Audit Correction</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Adjustment Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Forklift damaged 3 units during transit; discarded with supervisor approval."></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="adjustments.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="fa-solid fa-circle-check me-1"></i> Apply &amp; Post Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const stockMap = <?= json_encode($stock_lookup) ?>;
    const prodSelect = document.getElementById('prodSelect');
    const locSelect = document.getElementById('locSelect');
    const recordedInput = document.getElementById('recordedQty');
    const countedInput = document.getElementById('countedQty');
    const diffDisplay = document.getElementById('diffDisplay');

    function updateCalculations() {
        const pid = prodSelect.value;
        const lid = locSelect.value;

        if (pid && lid) {
            const key = pid + '_' + lid;
            const currentStock = stockMap[key] !== undefined ? stockMap[key] : 0.00;
            recordedInput.value = currentStock.toFixed(2);
        } else {
            recordedInput.value = '0.00';
        }

        const rec = parseFloat(recordedInput.value) || 0;
        const cnt = parseFloat(countedInput.value) || 0;
        const diff = cnt - rec;

        diffDisplay.value = (diff > 0 ? '+' : '') + diff.toFixed(2);
        if (diff < 0) {
            diffDisplay.className = 'form-control font-monospace fw-extrabold text-danger bg-danger-subtle';
        } else if (diff > 0) {
            diffDisplay.className = 'form-control font-monospace fw-extrabold text-success bg-success-subtle';
        } else {
            diffDisplay.className = 'form-control font-monospace fw-extrabold text-dark';
        }
    }

    prodSelect.addEventListener('change', updateCalculations);
    locSelect.addEventListener('change', updateCalculations);
    countedInput.addEventListener('input', updateCalculations);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
