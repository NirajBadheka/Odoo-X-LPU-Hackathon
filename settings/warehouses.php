<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_manager(); // Restricted to Inventory Managers
$current_user = get_current_user_data();

$page_title = 'Warehouses & Locations';
$page_subtitle = 'Configure physical storage facilities and multi-zone rack layouts';

$error = '';

// Handle New Warehouse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_warehouse') {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $address = trim($_POST['address'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Session expired.';
    } elseif (empty($name) || empty($code)) {
        $error = 'Warehouse name and code are required.';
    } else {
        $res = db_query("INSERT INTO warehouses (name, code, address, is_active, created_at) VALUES (?, ?, ?, 1, NOW())", [$name, $code, $address], "sss");
        if ($res['success']) {
            // Also create a default "Main Store" location for this warehouse
            $wh_id = $res['insert_id'];
            db_query("INSERT INTO locations (warehouse_id, name, barcode, location_type, created_at) VALUES (?, 'Main Receiving Dock', ?, 'internal', NOW())", [$wh_id, "LOC-{$code}-01"], "is");

            set_flash('success', "Warehouse '{$name}' ({$code}) created with default receiving location.");
            header('Location: ' . BASE_URL . 'settings/warehouses.php');
            exit;
        } else {
            $error = 'Failed to create warehouse: ' . $res['error'];
        }
    }
}

// Handle New Location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_location') {
    $warehouse_id = intval($_POST['warehouse_id'] ?? 0);
    $loc_name = trim($_POST['loc_name'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Session expired.';
    } elseif (empty($loc_name) || $warehouse_id <= 0) {
        $error = 'Location name and warehouse selection are required.';
    } else {
        $res = db_query("INSERT INTO locations (warehouse_id, name, barcode, location_type, created_at) VALUES (?, ?, ?, 'internal', NOW())", [
            $warehouse_id, $loc_name, $barcode
        ], "iss");

        if ($res['success']) {
            set_flash('success', "Storage location '{$loc_name}' added successfully.");
            header('Location: ' . BASE_URL . 'settings/warehouses.php');
            exit;
        } else {
            $error = 'Failed to add location: ' . $res['error'];
        }
    }
}

// Fetch warehouses with their locations
$warehouses = db_select("
    SELECT w.*, COUNT(l.id) as location_count
    FROM warehouses w
    LEFT JOIN locations l ON w.id = l.warehouse_id
    GROUP BY w.id
    ORDER BY w.id ASC
");

$locations_by_wh = [];
$all_locs = db_select("SELECT * FROM locations ORDER BY name ASC");
foreach ($all_locs as $l) {
    $locations_by_wh[$l['warehouse_id']][] = $l;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #714B67;">
            <i class="fa-solid fa-warehouse me-2"></i>Multi-Warehouse &amp; Location Architecture
        </h4>
        <p class="text-muted small mb-0">Configure facilities, production floors, and individual storage rack zones.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newLocationModal">
            <i class="fa-solid fa-layer-group me-1"></i> Add Storage Rack
        </button>
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#newWarehouseModal">
            <i class="fa-solid fa-plus me-1"></i> Add Warehouse
        </button>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($warehouses as $wh): ?>
        <div class="col-lg-6">
            <div class="card card-odoo h-100 shadow-sm overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 text-white" style="background: #714B67;">
                            <i class="fa-solid fa-warehouse"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($wh['name']) ?></h6>
                            <span class="badge bg-purple text-white font-monospace" style="background:#714B67; font-size: 0.68rem;">
                                <?= htmlspecialchars($wh['code']) ?>
                            </span>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success">
                        <i class="fa-solid fa-circle-check me-1"></i>Active Facility
                    </span>
                </div>

                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($wh['address'] ?: 'No address specified.') ?>
                    </p>

                    <h6 class="small fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">
                        Configured Storage Zones / Racks (<?= count($locations_by_wh[$wh['id']] ?? []) ?>)
                    </h6>

                    <div class="list-group list-group-flush small">
                        <?php if (empty($locations_by_wh[$wh['id']])): ?>
                            <div class="text-muted fst-italic">No specific storage racks configured yet.</div>
                        <?php else: ?>
                            <?php foreach ($locations_by_wh[$wh['id']] as $loc): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-cube text-primary"></i>
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($loc['name']) ?></span>
                                    </div>
                                    <span class="badge bg-light text-muted border font-monospace">
                                        <?= htmlspecialchars($loc['barcode'] ?: 'NO-BARCODE') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer bg-light border-top text-end py-2 px-4">
                    <a href="../products/stock_by_location.php" class="small fw-semibold text-decoration-none" style="color: #00A09D;">
                        View Stock In This Warehouse &rarr;
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: New Warehouse -->
<div class="modal fade" id="newWarehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="warehouses.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_warehouse">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="color: #714B67;">
                        <i class="fa-solid fa-warehouse me-2"></i>Add Warehouse Facility
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Warehouse Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. West Coast Distribution Depot" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Warehouse Code *</label>
                        <input type="text" name="code" class="form-control font-monospace text-uppercase" placeholder="e.g. WH-WEST" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Physical Facility Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street, City, State, ZIP..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand">Create Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: New Location / Rack -->
<div class="modal fade" id="newLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="warehouses.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_location">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="color: #714B67;">
                        <i class="fa-solid fa-layer-group me-2"></i>Add Storage Bay / Rack
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Belongs to Warehouse *</label>
                        <select name="warehouse_id" class="form-select" required>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?> (<?= htmlspecialchars($wh['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Rack / Zone Name *</label>
                        <input type="text" name="loc_name" class="form-control" placeholder="e.g. Rack C (Heavy Parts)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Location Barcode / Identifier</label>
                        <input type="text" name="barcode" class="form-control font-monospace" placeholder="e.g. LOC-RC-01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand">Add Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
