<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$id = intval($_GET['id'] ?? 0);
$delivery = db_select_one("
    SELECT d.*, loc.name as location_name, w.name as wh_name, w.code as wh_code,
           u1.full_name as creator_name, u2.full_name as validator_name
    FROM delivery_orders d
    JOIN locations loc ON d.source_location_id = loc.id
    JOIN warehouses w ON loc.warehouse_id = w.id
    JOIN users u1 ON d.created_by = u1.id
    LEFT JOIN users u2 ON d.validated_by = u2.id
    WHERE d.id = ?
", [$id], "i");

if (!$delivery) {
    set_flash('error', 'Delivery order not found.');
    header('Location: ' . BASE_URL . 'operations/deliveries.php');
    exit;
}

$page_title = 'Delivery: ' . $delivery['reference_no'];
$page_subtitle = 'Pick, pack, and validate customer dispatch shipment';

// Fetch Delivery Items
$items = db_select("
    SELECT di.*, p.name as product_name, p.sku, p.unit_of_measure,
           COALESCE(sl.quantity, 0) as available_stock
    FROM delivery_items di
    JOIN products p ON di.product_id = p.id
    LEFT JOIN stock_levels sl ON (p.id = sl.product_id AND sl.location_id = ?)
    WHERE di.delivery_id = ?
", [$delivery['source_location_id'], $id], "ii");

// Workflow Action Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        set_flash('error', 'Security token expired.');
    } elseif ($action === 'pick_items') {
        // Step 1: Pick Items
        foreach ($items as $item) {
            db_query("UPDATE delivery_items SET quantity_picked = quantity_demanded WHERE id = ?", [$item['id']], "i");
        }
        set_flash('success', 'All items marked as PICKED from warehouse storage bays.');
        header('Location: ' . BASE_URL . 'operations/delivery_view.php?id=' . $id);
        exit;
    } elseif ($action === 'pack_items') {
        // Step 2: Pack Items & Mark Ready
        foreach ($items as $item) {
            db_query("UPDATE delivery_items SET quantity_packed = quantity_picked WHERE id = ?", [$item['id']], "i");
        }
        db_query("UPDATE delivery_orders SET status = 'ready' WHERE id = ?", [$id], "i");
        set_flash('success', 'Items packed into shipping parcels. Order marked as READY to Ship.');
        header('Location: ' . BASE_URL . 'operations/delivery_view.php?id=' . $id);
        exit;
    } elseif ($action === 'validate_shipment') {
        // Step 3: Validate & Ship (Deduct Stock & Write to Ledger)
        if (!is_manager()) {
            set_flash('error', 'Access restricted: only Inventory Managers can validate and ship outgoing deliveries.');
            header('Location: ' . BASE_URL . 'operations/delivery_view.php?id=' . $id);
            exit;
        } elseif ($delivery['status'] === STATUS_DONE) {
            set_flash('error', 'Delivery is already validated.');
        } else {
            foreach ($items as $item) {
                $qty = $item['quantity_packed'] > 0 ? $item['quantity_packed'] : $item['quantity_demanded'];
                
                // Deduct stock from source location
                update_location_stock($item['product_id'], $delivery['source_location_id'], -$qty);

                // Record in Stock Moves Ledger
                record_stock_move(
                    MOVE_DELIVERY,
                    $delivery['reference_no'],
                    $item['product_id'],
                    $delivery['source_location_id'],
                    null, // Destination: external customer
                    -$qty,
                    $current_user['id'],
                    "Dispatched to " . $delivery['customer_name']
                );
            }

            db_query("UPDATE delivery_orders SET status = 'done', validated_by = ?, validated_at = NOW() WHERE id = ?", [$current_user['id'], $id], "ii");

            set_flash('success', "Delivery {$delivery['reference_no']} validated! Stock reduced automatically in ledger.");
            header('Location: ' . BASE_URL . 'operations/delivery_view.php?id=' . $id);
            exit;
        }
    }
}

// Re-fetch items to get latest picked/packed quantities
$items = db_select("
    SELECT di.*, p.name as product_name, p.sku, p.unit_of_measure,
           COALESCE(sl.quantity, 0) as available_stock
    FROM delivery_items di
    JOIN products p ON di.product_id = p.id
    LEFT JOIN stock_levels sl ON (p.id = sl.product_id AND sl.location_id = ?)
    WHERE di.delivery_id = ?
", [$delivery['source_location_id'], $id], "ii");

$all_picked = true;
$all_packed = true;
foreach ($items as $item) {
    if ($item['quantity_picked'] < $item['quantity_demanded']) $all_picked = false;
    if ($item['quantity_packed'] < $item['quantity_demanded']) $all_packed = false;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="fw-bold mb-0 font-monospace" style="color: #714B67;"><?= htmlspecialchars($delivery['reference_no']) ?></h4>
                    <?= get_status_badge($delivery['status']) ?>
                </div>
                <p class="text-muted small mb-0">Dispatched from: <strong><?= htmlspecialchars($delivery['wh_code']) ?> &rarr; <?= htmlspecialchars($delivery['location_name']) ?></strong></p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="deliveries.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>

                <?php if ($delivery['status'] !== STATUS_DONE): ?>
                    <!-- Step 1: Pick -->
                    <?php if (!$all_picked): ?>
                        <form method="POST" action="delivery_view.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="pick_items">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-dolly me-1"></i> Step 1: Pick Items
                            </button>
                        </form>
                    <?php elseif (!$all_packed): ?>
                        <!-- Step 2: Pack -->
                        <form method="POST" action="delivery_view.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="pack_items">
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="fa-solid fa-box me-1"></i> Step 2: Pack Items
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Step 3: Validate & Deduct -->
                        <form method="POST" action="delivery_view.php?id=<?= $id ?>" onsubmit="return confirmAction(event, 'Validate shipment? Stock will be automatically deducted.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="validate_shipment">
                            <button type="submit" class="btn btn-sm btn-brand">
                                <i class="fa-solid fa-truck-fast me-1"></i> Step 3: Validate &amp; Ship
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="move_history.php?search=<?= urlencode($delivery['reference_no']) ?>" class="btn btn-sm btn-outline-success">
                        <i class="fa-solid fa-receipt me-1"></i> View in Stock Ledger
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3-Step Odoo Visual Stepper (Pick -> Pack -> Validate) -->
        <div class="card card-odoo mb-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-around text-center small">
                <div class="<?= ($all_picked || $delivery['status'] === 'done') ? 'text-success fw-bold' : 'text-primary fw-bold' ?>">
                    <i class="fa-solid fa-cart-flatbed fa-lg mb-1 d-block"></i> 1. Pick Items
                    <span class="d-block smaller text-muted"><?= $all_picked ? 'Completed' : 'Pending' ?></span>
                </div>
                <div class="text-muted">&rarr;</div>
                <div class="<?= ($all_packed || $delivery['status'] === 'done') ? 'text-success fw-bold' : ($all_picked ? 'text-warning fw-bold' : 'text-muted') ?>">
                    <i class="fa-solid fa-boxes-packing fa-lg mb-1 d-block"></i> 2. Pack Items
                    <span class="d-block smaller text-muted"><?= $all_packed ? 'Completed' : 'Pending' ?></span>
                </div>
                <div class="text-muted">&rarr;</div>
                <div class="<?= ($delivery['status'] === 'done') ? 'text-success fw-bold' : 'text-muted' ?>">
                    <i class="fa-solid fa-circle-check fa-lg mb-1 d-block"></i> 3. Validate &amp; Deduct Stock
                    <span class="d-block smaller text-muted"><?= ($delivery['status'] === 'done') ? 'Dispatched' : 'Pending' ?></span>
                </div>
            </div>
        </div>

        <!-- Metadata Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-uppercase small fw-bold text-muted d-block">Customer</span>
                    <strong class="fs-6 text-dark"><?= htmlspecialchars($delivery['customer_name']) ?></strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-uppercase small fw-bold text-muted d-block">Source Warehouse</span>
                    <strong class="fs-6 text-dark"><?= htmlspecialchars($delivery['wh_name']) ?> (<?= htmlspecialchars($delivery['location_name']) ?>)</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-uppercase small fw-bold text-muted d-block">Audit Status</span>
                    <?php if ($delivery['status'] === 'done'): ?>
                        <div class="text-success small fw-bold">
                            <i class="fa-solid fa-circle-check me-1"></i>Shipped by <?= htmlspecialchars($delivery['validator_name'] ?? 'Staff') ?>
                        </div>
                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($delivery['validated_at'])) ?></small>
                    <?php else: ?>
                        <div class="text-info small fw-bold">
                            <i class="fa-solid fa-dolly me-1"></i>Ready for Warehouse Pick &amp; Pack
                        </div>
                        <small class="text-muted">Created by <?= htmlspecialchars($delivery['creator_name']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="card card-odoo overflow-hidden shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-boxes-stacked me-2 text-danger"></i>Outgoing Items &amp; Stock Availability
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-odoo align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Product Name</th>
                            <th>SKU</th>
                            <th class="text-center">Current Stock Available</th>
                            <th class="text-end">Demanded Qty</th>
                            <th class="text-center">Picked</th>
                            <th class="text-center">Packed</th>
                            <th class="pe-4 text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($item['product_name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['sku']) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <?= number_format($item['available_stock'], 1) ?> <?= htmlspecialchars($item['unit_of_measure']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    -<?= number_format($item['quantity_demanded'], 2) ?> <?= htmlspecialchars($item['unit_of_measure']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['quantity_picked'] >= $item['quantity_demanded']): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i>Picked</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">0 / <?= $item['quantity_demanded'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['quantity_packed'] >= $item['quantity_demanded']): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-box me-1"></i>Packed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    ₹<?= number_format($item['unit_price'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($delivery['shipping_address'])): ?>
            <div class="card card-odoo p-3 mb-4 bg-light">
                <span class="small fw-bold text-muted d-block mb-1">Customer Shipping Address:</span>
                <p class="small text-dark mb-0"><?= nl2br(htmlspecialchars($delivery['shipping_address'])) ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
