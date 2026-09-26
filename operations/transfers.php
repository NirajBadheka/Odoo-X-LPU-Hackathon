<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Internal Stock Transfers';
$page_subtitle = 'Relocate stock between warehouses, production racks, and storage bays';

// Handle 1-Click Validation from List
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_transfer_id'])) {
    $tid = intval($_POST['validate_transfer_id']);
    $token = $_POST['csrf_token'] ?? '';

    if (validate_csrf_token($token)) {
        $t = db_select_one("SELECT * FROM internal_transfers WHERE id = ?", [$tid], "i");
        if ($t && $t['status'] !== STATUS_DONE) {
            // Check stock at source
            $src_stock = db_select_one("SELECT quantity FROM stock_levels WHERE product_id = ? AND location_id = ?", [$t['product_id'], $t['source_location_id']], "ii");
            $avail = $src_stock['quantity'] ?? 0;

            if ($avail < $t['quantity']) {
                set_flash('error', "Insufficient stock at source location. Available: {$avail}, Required: {$t['quantity']}.");
            } else {
                // Deduct source & add destination
                update_location_stock($t['product_id'], $t['source_location_id'], -$t['quantity']);
                update_location_stock($t['product_id'], $t['destination_location_id'], $t['quantity']);

                // Record in Stock Moves Ledger
                record_stock_move(
                    MOVE_INTERNAL,
                    $t['reference_no'],
                    $t['product_id'],
                    $t['source_location_id'],
                    $t['destination_location_id'],
                    $t['quantity'],
                    $current_user['id'],
                    "Internal Transfer executed: " . ($t['notes'] ?: 'No notes')
                );

                db_query("UPDATE internal_transfers SET status = 'done', validated_at = NOW() WHERE id = ?", [$tid], "i");
                set_flash('success', "Transfer {$t['reference_no']} validated! Stock relocated in ledger.");
            }
        }
    }
    header('Location: ' . BASE_URL . 'operations/transfers.php');
    exit;
}

// Fetch all transfers
$transfers = db_select("
    SELECT t.*, p.name as product_name, p.sku, p.unit_of_measure,
           l1.name as src_name, w1.code as src_wh,
           l2.name as dest_name, w2.code as dest_wh,
           u.full_name as creator_name
    FROM internal_transfers t
    JOIN products p ON t.product_id = p.id
    JOIN locations l1 ON t.source_location_id = l1.id
    JOIN warehouses w1 ON l1.warehouse_id = w1.id
    JOIN locations l2 ON t.destination_location_id = l2.id
    JOIN warehouses w2 ON l2.warehouse_id = w2.id
    JOIN users u ON t.created_by = u.id
    ORDER BY t.created_at DESC
");

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Internal Stock Transfers
        </h4>
        <p class="text-muted small mb-0">Relocate stock between warehouses, production racks, and storage bays (Total stock unchanged).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="transfer_create.php" class="btn btn-sm btn-brand">
            <i class="fa-solid fa-plus me-1"></i> New Internal Transfer
        </a>
    </div>
</div>

<!-- Transfers Table -->
<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Reference #</th>
                    <th>Product &amp; SKU</th>
                    <th>Source Rack &rarr; Destination Rack</th>
                    <th class="text-end">Transfer Qty</th>
                    <th class="text-center">Status</th>
                    <th>Scheduled By</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-route fa-3x mb-3 text-secondary d-block"></i>
                            No internal transfers scheduled. <a href="transfer_create.php">Create a transfer now</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transfers as $t): ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold" style="color: #714B67;">
                                <?= htmlspecialchars($t['reference_no']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($t['product_name']) ?></div>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($t['sku']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($t['src_wh']) ?> &bull; <?= htmlspecialchars($t['src_name']) ?>
                                </span>
                                <i class="fa-solid fa-arrow-right mx-1 text-muted small"></i>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                    <?= htmlspecialchars($t['dest_wh']) ?> &bull; <?= htmlspecialchars($t['dest_name']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                <?= number_format($t['quantity'], 2) ?> <?= htmlspecialchars($t['unit_of_measure']) ?>
                            </td>
                            <td class="text-center">
                                <?= get_status_badge($t['status']) ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($t['creator_name']) ?></small>
                                <div class="smaller text-muted"><?= date('M d, H:i', strtotime($t['created_at'])) ?></div>
                            </td>
                            <td class="pe-4 text-end">
                                <?php if ($t['status'] !== STATUS_DONE): ?>
                                    <form method="POST" action="transfers.php" onsubmit="return confirmAction(event, 'Execute transfer and move stock to destination?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="validate_transfer_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-brand py-1 px-3">
                                            <i class="fa-solid fa-bolt me-1"></i> Validate Move
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-light text-success border">
                                        <i class="fa-solid fa-circle-check me-1"></i>Completed
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
