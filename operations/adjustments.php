<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'Stock Adjustments (Physical Inventory)';
$page_subtitle = 'Reconcile recorded ledger balance with physical counts & damage write-offs';

$adjustments = db_select("
    SELECT a.*, p.name as product_name, p.sku, p.unit_of_measure,
           loc.name as loc_name, w.code as wh_code,
           u.full_name as creator_name
    FROM stock_adjustments a
    JOIN products p ON a.product_id = p.id
    JOIN locations loc ON a.location_id = loc.id
    JOIN warehouses w ON loc.warehouse_id = w.id
    JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
");

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-sliders me-2"></i>Stock Adjustments &amp; Cycle Counting
        </h4>
        <p class="text-muted small mb-0">Reconcile theoretical book balances with physical counted stock, damages, and scrap write-offs.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="adjustment_create.php" class="btn btn-sm btn-brand">
            <i class="fa-solid fa-plus me-1"></i> New Physical Count Adjustment
        </a>
    </div>
</div>

<div class="card card-odoo overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-odoo align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Reference #</th>
                    <th>Product &amp; SKU</th>
                    <th>Warehouse Location</th>
                    <th class="text-end">Recorded Stock</th>
                    <th class="text-end">Counted Stock</th>
                    <th class="text-end">Difference (Adjustment)</th>
                    <th class="text-center">Reason Code</th>
                    <th class="text-center">Status</th>
                    <th class="pe-4 text-end">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-scale-balanced fa-3x mb-3 text-secondary d-block"></i>
                            No stock adjustments recorded. <a href="adjustment_create.php">Record an adjustment</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($adjustments as $a): ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold" style="color: #714B67;">
                                <?= htmlspecialchars($a['reference_no']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($a['product_name']) ?></div>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($a['sku']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($a['wh_code']) ?> &bull; <?= htmlspecialchars($a['loc_name']) ?>
                                </span>
                            </td>
                            <td class="text-end text-muted">
                                <?= number_format($a['recorded_quantity'], 2) ?> <?= htmlspecialchars($a['unit_of_measure']) ?>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                <?= number_format($a['counted_quantity'], 2) ?> <?= htmlspecialchars($a['unit_of_measure']) ?>
                            </td>
                            <td class="text-end fw-extrabold <?= ($a['difference_quantity'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                <?= ($a['difference_quantity'] > 0 ? '+' : '') . number_format($a['difference_quantity'], 2) ?> <?= htmlspecialchars($a['unit_of_measure']) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase" style="font-size: 0.65rem;">
                                    <?= htmlspecialchars($a['reason']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?= get_status_badge($a['status']) ?>
                            </td>
                            <td class="pe-4 text-end text-muted small">
                                <?= date('M d, Y H:i', strtotime($a['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
