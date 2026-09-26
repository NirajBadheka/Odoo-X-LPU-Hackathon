<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$id = intval($_GET['id'] ?? 0);
$receipt = db_select_one("
    SELECT r.*, loc.name as location_name, w.name as wh_name, w.code as wh_code,
           u1.full_name as creator_name, u2.full_name as validator_name
    FROM receipts r
    JOIN locations loc ON r.destination_location_id = loc.id
    JOIN warehouses w ON loc.warehouse_id = w.id
    JOIN users u1 ON r.created_by = u1.id
    LEFT JOIN users u2 ON r.validated_by = u2.id
    WHERE r.id = ?
", [$id], "i");

if (!$receipt) {
    set_flash('error', 'Receipt record not found.');
    header('Location: ' . BASE_URL . 'operations/receipts.php');
    exit;
}

$page_title = 'Receipt: ' . $receipt['reference_no'];
$page_subtitle = 'Incoming stock inspection & validation';

// Fetch Receipt Items
$items = db_select("
    SELECT ri.*, p.name as product_name, p.sku, p.unit_of_measure
    FROM receipt_items ri
    JOIN products p ON ri.product_id = p.id
    WHERE ri.receipt_id = ?
", [$id], "i");

// Handle Validation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_receipt'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        set_flash('error', 'Security token expired.');
    } elseif (!is_manager()) {
        set_flash('error', 'Access restricted: only Inventory Managers can validate incoming receipts.');
    } elseif ($receipt['status'] === STATUS_DONE) {
        set_flash('error', 'This receipt is already validated.');
    } else {
        // Validate each item, update stock levels & record stock moves
        foreach ($items as $item) {
            $qty_to_receive = floatval($_POST['qty_received'][$item['id']] ?? $item['quantity_expected']);
            if ($qty_to_receive <= 0) $qty_to_receive = $item['quantity_expected'];

            // Update item record
            db_query("UPDATE receipt_items SET quantity_received = ? WHERE id = ?", [$qty_to_receive, $item['id']], "di");

            // Update physical stock level at location
            update_location_stock($item['product_id'], $receipt['destination_location_id'], $qty_to_receive);

            // Record to immutable Stock Moves Ledger
            record_stock_move(
                MOVE_RECEIPT,
                $receipt['reference_no'],
                $item['product_id'],
                null, // Source: external vendor
                $receipt['destination_location_id'],
                $qty_to_receive,
                $current_user['id'],
                "Receipt validated from " . $receipt['supplier_name']
            );
        }

        // Mark receipt as DONE
        db_query("UPDATE receipts SET status = 'done', validated_by = ?, validated_at = NOW() WHERE id = ?", [$current_user['id'], $id], "ii");

        set_flash('success', "Receipt {$receipt['reference_no']} validated successfully! Stock increased automatically.");
        header('Location: ' . BASE_URL . 'operations/receipt_view.php?id=' . $id);
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <!-- Header & Breadcrumb -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="fw-bold mb-0 font-monospace" style="color: #714B67;"><?= htmlspecialchars($receipt['reference_no']) ?></h4>
                    <?= get_status_badge($receipt['status']) ?>
                </div>
                <p class="text-muted small mb-0">Destination: <strong><?= htmlspecialchars($receipt['wh_code']) ?> &rarr; <?= htmlspecialchars($receipt['location_name']) ?></strong></p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="receipts.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Receipts
                </a>
                <?php if ($receipt['status'] !== STATUS_DONE): ?>
                    <form method="POST" action="receipt_view.php?id=<?= $id ?>" onsubmit="return confirmAction(event, 'Are you sure you want to validate this receipt and increase stock?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="validate_receipt" value="1">
                        <button type="submit" class="btn btn-sm btn-brand">
                            <i class="fa-solid fa-circle-check me-1"></i> Validate &amp; Increase Stock
                        </button>
                    </form>
                <?php else: ?>
                    <a href="move_history.php?search=<?= urlencode($receipt['reference_no']) ?>" class="btn btn-sm btn-outline-success">
                        <i class="fa-solid fa-receipt me-1"></i> View in Stock Ledger
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Odoo-Style Status Progress Bar -->
        <div class="card card-odoo mb-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-around text-center small">
                <div class="<?= in_array($receipt['status'], ['draft', 'waiting', 'ready', 'done']) ? 'text-primary fw-bold' : 'text-muted' ?>">
                    <i class="fa-solid fa-file-pen fa-lg mb-1 d-block"></i> 1. Draft
                </div>
                <div class="text-muted">&rarr;</div>
                <div class="<?= in_array($receipt['status'], ['waiting', 'ready', 'done']) ? 'text-warning fw-bold' : 'text-muted' ?>">
                    <i class="fa-solid fa-clock fa-lg mb-1 d-block"></i> 2. Waiting
                </div>
                <div class="text-muted">&rarr;</div>
                <div class="<?= in_array($receipt['status'], ['ready', 'done']) ? 'text-info fw-bold' : 'text-muted' ?>">
                    <i class="fa-solid fa-box-open fa-lg mb-1 d-block"></i> 3. Ready to Shelve
                </div>
                <div class="text-muted">&rarr;</div>
                <div class="<?= ($receipt['status'] === 'done') ? 'text-success fw-bold' : 'text-muted' ?>">
                    <i class="fa-solid fa-circle-check fa-lg mb-1 d-block"></i> 4. Done (Stock Increased)
                </div>
            </div>
        </div>

        <!-- Receipt Metadata Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-uppercase small fw-bold text-muted d-block">Vendor / Supplier</span>
                    <strong class="fs-6 text-dark"><?= htmlspecialchars($receipt['supplier_name']) ?></strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-uppercase small fw-bold text-muted d-block">Receiving Warehouse</span>
                    <strong class="fs-6 text-dark"><?= htmlspecialchars($receipt['wh_name']) ?> (<?= htmlspecialchars($receipt['location_name']) ?>)</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-uppercase small fw-bold text-muted d-block">Audit Status</span>
                    <?php if ($receipt['status'] === 'done'): ?>
                        <div class="text-success small fw-bold">
                            <i class="fa-solid fa-check-circle me-1"></i>Validated by <?= htmlspecialchars($receipt['validator_name'] ?? 'Manager') ?>
                        </div>
                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($receipt['validated_at'])) ?></small>
                    <?php else: ?>
                        <div class="text-warning-emphasis small fw-bold">
                            <i class="fa-solid fa-hourglass-half me-1"></i>Awaiting Physical Verification
                        </div>
                        <small class="text-muted">Created by <?= htmlspecialchars($receipt['creator_name']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="card card-odoo overflow-hidden shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Received Products Breakdown
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-odoo align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Product Name</th>
                            <th>SKU</th>
                            <th class="text-end">Expected Qty</th>
                            <th class="text-end">Validated Qty Received</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="pe-4 text-end">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $grand_total = 0;
                            foreach ($items as $item): 
                                $qty = ($receipt['status'] === 'done') ? $item['quantity_received'] : $item['quantity_expected'];
                                $line_total = $qty * $item['unit_cost'];
                                $grand_total += $line_total;
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($item['product_name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['sku']) ?></span>
                                </td>
                                <td class="text-end">
                                    <?= number_format($item['quantity_expected'], 2) ?> <?= htmlspecialchars($item['unit_of_measure']) ?>
                                </td>
                                <td class="text-end fw-bold <?= ($receipt['status'] === 'done') ? 'text-success' : 'text-dark' ?>">
                                    <?= number_format($item['quantity_received'] > 0 ? $item['quantity_received'] : $item['quantity_expected'], 2) ?> <?= htmlspecialchars($item['unit_of_measure']) ?>
                                </td>
                                <td class="text-end">
                                    ₹<?= number_format($item['unit_cost'], 2) ?>
                                </td>
                                <td class="pe-4 text-end fw-bold">
                                    ₹<?= number_format($line_total, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold ps-4">Estimated Total Value:</td>
                            <td class="pe-4 text-end fw-extrabold fs-6" style="color: #714B67;">
                                ₹<?= number_format($grand_total, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($receipt['notes'])): ?>
            <div class="card card-odoo p-3 mb-4 bg-light">
                <span class="small fw-bold text-muted d-block mb-1">Carrier / Warehouse Notes:</span>
                <p class="small text-dark mb-0"><?= nl2br(htmlspecialchars($receipt['notes'])) ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
