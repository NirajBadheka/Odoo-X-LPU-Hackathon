<?php
/**
 * StockSense Pro - High-Impact Public Landing Page & Common Dashboard
 * Problem Statement: StockSense (Inventory Management System)
 * Odoo Hackathon Virtual Round
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth_check.php';

// Fetch Live KPIs for Common Dashboard Snapshot
$total_products = 0;
$low_stock_count = 0;
$pending_receipts = 0;
$pending_deliveries = 0;
$scheduled_transfers = 0;
$recent_moves = [];

if ($db_connected) {
    // 1. Total Products
    $res = db_select_one("SELECT COUNT(*) as cnt FROM products");
    $total_products = $res['cnt'] ?? 0;

    // 2. Low Stock Items (Total quantity across locations <= reorder_min_level)
    $res = db_select_one("
        SELECT COUNT(DISTINCT p.id) as cnt 
        FROM products p
        LEFT JOIN stock_levels sl ON p.id = sl.product_id
        GROUP BY p.id
        HAVING COALESCE(SUM(sl.quantity), 0) <= p.reorder_min_level
    ");
    $low_stock_count = $res['cnt'] ?? 0;

    // 3. Pending Receipts
    $res = db_select_one("SELECT COUNT(*) as cnt FROM receipts WHERE status IN ('waiting', 'ready')");
    $pending_receipts = $res['cnt'] ?? 0;

    // 4. Pending Deliveries
    $res = db_select_one("SELECT COUNT(*) as cnt FROM delivery_orders WHERE status IN ('waiting', 'ready')");
    $pending_deliveries = $res['cnt'] ?? 0;

    // 5. Internal Transfers Scheduled
    $res = db_select_one("SELECT COUNT(*) as cnt FROM internal_transfers WHERE status IN ('draft', 'ready')");
    $scheduled_transfers = $res['cnt'] ?? 0;

    // Recent Operations Snapshot
    $recent_moves = db_select("
        SELECT sm.*, p.name as product_name, p.sku, p.unit_of_measure,
               loc_from.name as source_name, loc_to.name as dest_name,
               u.full_name as user_name
        FROM stock_moves sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN locations loc_from ON sm.source_location_id = loc_from.id
        LEFT JOIN locations loc_to ON sm.destination_location_id = loc_to.id
        LEFT JOIN users u ON sm.user_id = u.id
        ORDER BY sm.created_at DESC
        LIMIT 6
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> &mdash; Modern Inventory &amp; Stock Movement Portal</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --odoo-primary: #714B67;
            --odoo-dark: #5B3A53;
            --odoo-accent: #00A09D;
            --odoo-accent-hover: #008784;
            --odoo-bg: #F8F9FA;
            --odoo-surface: #FFFFFF;
            --odoo-border: #E9ECEF;
        }

        body {
            background-color: var(--odoo-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
            overflow-x: hidden;
        }

        /* Hero Navbar */
        .landing-navbar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--odoo-border);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #5B3A53 0%, #714B67 60%, #462A40 100%);
            color: #FFFFFF;
            padding: 80px 0 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(0, 160, 157, 0.25) 0%, transparent 70%);
            border-radius: 50%;
        }

        .btn-brand {
            background-color: var(--odoo-accent);
            color: #FFFFFF;
            font-weight: 600;
            border: none;
            padding: 12px 26px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .btn-brand:hover {
            background-color: var(--odoo-accent-hover);
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 160, 157, 0.35);
        }

        .btn-outline-odoo {
            border: 2px solid rgba(255,255,255,0.7);
            color: #FFFFFF;
            font-weight: 600;
            padding: 11px 24px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .btn-outline-odoo:hover {
            background: #FFFFFF;
            color: var(--odoo-primary);
            border-color: #FFFFFF;
        }

        /* KPI Card */
        .kpi-card {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(113, 75, 103, 0.1);
        }

        /* Flow Step Cards */
        .flow-step {
            background: #FFFFFF;
            border-radius: 14px;
            padding: 24px;
            border: 1px solid var(--odoo-border);
            position: relative;
            height: 100%;
            transition: all 0.2s ease;
        }
        .flow-step:hover {
            border-color: var(--odoo-accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 160, 157, 0.1);
        }

        .step-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--odoo-accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 14px;
        }

        /* Feature Box */
        .feature-box {
            padding: 24px;
            border-radius: 14px;
            background: #FFFFFF;
            border: 1px solid var(--odoo-border);
            height: 100%;
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<nav class="landing-navbar">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px; background: #714B67;">
                <i class="fa-solid fa-cubes-stacked"></i>
            </div>
            <div>
                <span class="fw-bold fs-5" style="color: #714B67;">StockSense</span>
                <span class="badge bg-secondary-subtle text-dark ms-1" style="font-size: 0.65rem;">PRO</span>
            </div>
        </a>

        <div class="d-none d-lg-flex align-items-center gap-4 text-muted small fw-medium">
            <a href="#snapshot" class="text-decoration-none text-dark">Dashboard Snapshot</a>
            <a href="#workflow" class="text-decoration-none text-dark">Inventory Flow</a>
            <a href="#features" class="text-decoration-none text-dark">Core Architecture</a>
            <a href="#credentials" class="text-decoration-none text-dark">Demo Accounts</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="btn btn-sm btn-brand">
                    <i class="fa-solid fa-chart-pie me-1"></i> Open Dashboard
                </a>
                <a href="auth/logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-sm btn-outline-dark px-3">Sign In</a>
                <a href="auth/register.php" class="btn btn-sm btn-brand px-3">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section text-center text-md-start">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-15 text-white small mb-3 border border-white border-opacity-25">
                    <i class="fa-solid fa-award text-warning"></i>
                    <span>Odoo Hackathon Virtual Round Solution</span>
                </div>
                <h1 class="display-5 fw-extrabold text-white mb-3" style="line-height: 1.15;">
                    Centralized, Real-Time <br>
                    <span style="color: #48E5C2;">Inventory Management</span>
                </h1>
                <p class="lead text-white-50 mb-4" style="max-width: 600px; font-size: 1.05rem;">
                    Replaces manual registers and scattered Excel spreadsheets with an enterprise double-entry stock movement ledger, multi-warehouse routing, and automated reorder triggers.
                </p>

                <!-- Hero Action Buttons -->
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start mb-4">
                    <a href="dashboard.php" class="btn btn-brand">
                        <i class="fa-solid fa-gauge-high me-2"></i> Enter Live System
                    </a>
                    <a href="#snapshot" class="btn btn-outline-odoo">
                        <i class="fa-solid fa-eye me-2"></i> View Live Snapshot
                    </a>
                </div>

                <!-- 1-Click Demo Credentials Trigger -->
                <div class="pt-3 border-top border-white border-opacity-20 d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-white-50 me-2"><i class="fa-solid fa-bolt text-warning me-1"></i> Quick Test:</span>
                    <a href="auth/login.php?quick_demo=manager" class="btn btn-sm btn-light text-dark rounded-pill px-3 py-1 shadow-sm fw-semibold" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-user-tie text-primary me-1"></i> Inventory Manager Demo
                    </a>
                    <a href="auth/login.php?quick_demo=staff" class="btn btn-sm btn-light text-dark rounded-pill px-3 py-1 shadow-sm fw-semibold" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-boxes-packing text-warning me-1"></i> Warehouse Staff Demo
                    </a>
                </div>
            </div>

            <!-- Hero Mockup Card -->
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-lg overflow-hidden" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
                    <div class="p-4 border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="fw-bold text-dark">Live Operational Health</div>
                            <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-signal me-1"></i> Connected</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="list-group list-group-flush small">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Master Products</span>
                                <span class="fw-bold fs-6"><?= $total_products ?> SKUs</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Low Stock Alerts</span>
                                <span class="badge bg-danger text-white rounded-pill"><?= $low_stock_count ?> Items</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fa-solid fa-arrow-down-left-and-arrow-up-right-to-center text-success me-2"></i> Incoming Receipts</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold"><?= $pending_receipts ?> Pending</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fa-solid fa-truck-ramp-box text-info me-2"></i> Delivery Orders</span>
                                <span class="badge bg-info-subtle text-info-emphasis fw-bold"><?= $pending_deliveries ?> In Queue</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fa-solid fa-arrow-right-arrow-left text-secondary me-2"></i> Internal Transfers</span>
                                <span class="badge bg-secondary-subtle text-secondary fw-bold"><?= $scheduled_transfers ?> Scheduled</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-sm btn-odoo w-100 py-2">
                                Launch Full Inventory Console &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Snapshot / Common Dashboard Section (Problem Statement Requirement) -->
<section id="snapshot" class="py-5">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-purple text-white px-3 py-1 mb-2" style="background-color: #714B67;">Dashboard Snapshot</span>
            <h2 class="fw-bold">Real-Time Inventory Key Metrics</h2>
            <p class="text-muted">Live snapshot of inventory operations, physical count adjustments, and warehouse stock balances.</p>
        </div>

        <!-- 5 KPI Cards -->
        <div class="row g-3 mb-5">
            <!-- Total Products -->
            <div class="col-md-6 col-lg">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small fw-bold text-muted">Total Products</span>
                        <div class="rounded-3 p-2 bg-light text-primary">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="h2 fw-bold text-dark mb-1"><?= $total_products ?></div>
                    <small class="text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>Active in Catalog</small>
                </div>
            </div>

            <!-- Low / Out of Stock -->
            <div class="col-md-6 col-lg">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small fw-bold text-muted">Low / Out of Stock</span>
                        <div class="rounded-3 p-2 bg-danger-subtle text-danger">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="h2 fw-bold text-danger mb-1"><?= $low_stock_count ?></div>
                    <small class="text-danger"><i class="fa-solid fa-bell me-1"></i>Reorder Required</small>
                </div>
            </div>

            <!-- Pending Receipts -->
            <div class="col-md-6 col-lg">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small fw-bold text-muted">Pending Receipts</span>
                        <div class="rounded-3 p-2 bg-warning-subtle text-warning">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                    </div>
                    <div class="h2 fw-bold text-dark mb-1"><?= $pending_receipts ?></div>
                    <small class="text-warning-emphasis"><i class="fa-solid fa-truck-ramp-box me-1"></i>Incoming Vendors</small>
                </div>
            </div>

            <!-- Pending Deliveries -->
            <div class="col-md-6 col-lg">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small fw-bold text-muted">Pending Deliveries</span>
                        <div class="rounded-3 p-2 bg-info-subtle text-info">
                            <i class="fa-solid fa-truck-fast"></i>
                        </div>
                    </div>
                    <div class="h2 fw-bold text-dark mb-1"><?= $pending_deliveries ?></div>
                    <small class="text-info-emphasis"><i class="fa-solid fa-box-open me-1"></i>Ready to Pick/Pack</small>
                </div>
            </div>

            <!-- Internal Transfers -->
            <div class="col-md-6 col-lg">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small fw-bold text-muted">Internal Transfers</span>
                        <div class="rounded-3 p-2 bg-secondary-subtle text-secondary">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </div>
                    </div>
                    <div class="h2 fw-bold text-dark mb-1"><?= $scheduled_transfers ?></div>
                    <small class="text-secondary"><i class="fa-solid fa-route me-1"></i>Scheduled Racks</small>
                </div>
            </div>
        </div>

        <!-- Recent Stock Moves Ledger Preview -->
        <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
            <div class="p-3 bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-0" style="color: #714B67;">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>Stock Movement Ledger (Audit Log)
                    </h5>
                    <small class="text-muted">Live double-entry records tracking every item movement</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="operations/move_history.php" class="btn btn-sm btn-outline-secondary">
                        View Full Move History &rarr;
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Reference Doc</th>
                            <th>Move Type</th>
                            <th>Product &amp; SKU</th>
                            <th>Source &rarr; Destination</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-center">Recorded By</th>
                            <th class="pe-4 text-end">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_moves)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No stock moves recorded yet. <a href="setup.php">Click here to seed demo data.</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_moves as $move): ?>
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold" style="color: #714B67;">
                                        <?= htmlspecialchars($move['reference_doc']) ?>
                                    </td>
                                    <td>
                                        <?php if ($move['move_type'] === 'receipt'): ?>
                                            <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-arrow-down me-1"></i>Receipt</span>
                                        <?php elseif ($move['move_type'] === 'delivery'): ?>
                                            <span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-up me-1"></i>Delivery</span>
                                        <?php elseif ($move['move_type'] === 'internal'): ?>
                                            <span class="badge bg-info-subtle text-info"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Internal</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-sliders me-1"></i>Adjustment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($move['product_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($move['sku']) ?></small>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars($move['source_name'] ?? 'Vendor / External') ?></span>
                                        <i class="fa-solid fa-arrow-right mx-1 text-muted small"></i>
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($move['dest_name'] ?? 'Customer / Scrap') ?></span>
                                    </td>
                                    <td class="text-end fw-bold <?= ($move['quantity'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                        <?= ($move['quantity'] > 0 ? '+' : '') . number_format($move['quantity'], 2) ?> <?= htmlspecialchars($move['unit_of_measure']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($move['user_name'] ?? 'System') ?></span>
                                    </td>
                                    <td class="pe-4 text-end text-muted small">
                                        <?= date('M d, H:i', strtotime($move['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Interactive 4-Step Inventory Flow (PDF Page 3 & 4 Specification) -->
<section id="workflow" class="py-5 bg-white border-top border-bottom">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-info-subtle text-info-emphasis px-3 py-1 mb-2">Odoo-Inspired Supply Chain</span>
            <h2 class="fw-bold">How Stock Flows Through StockSense</h2>
            <p class="text-muted">A streamlined 4-step workflow directly illustrating the problem statement specification.</p>
        </div>

        <div class="row g-4">
            <!-- Step 1: Receive Goods -->
            <div class="col-md-6 col-lg-3">
                <div class="flow-step">
                    <div class="step-badge">1</div>
                    <h5 class="fw-bold mb-2">Receive Goods</h5>
                    <p class="text-muted small mb-3">Items arrive from vendor. Quantities inspected and validated.</p>
                    <div class="p-3 bg-light rounded-3 font-monospace small">
                        <strong>Example:</strong><br>
                        Receive 100 kg Steel<br>
                        <span class="text-success fw-bold">&rarr; Stock: +100 kg</span>
                    </div>
                </div>
            </div>

            <!-- Step 2: Internal Transfer -->
            <div class="col-md-6 col-lg-3">
                <div class="flow-step">
                    <div class="step-badge">2</div>
                    <h5 class="fw-bold mb-2">Internal Transfer</h5>
                    <p class="text-muted small mb-3">Move stock inside company across warehouses and racks.</p>
                    <div class="p-3 bg-light rounded-3 font-monospace small">
                        <strong>Example:</strong><br>
                        Main Store &rarr; Production<br>
                        <span class="text-primary fw-bold">&rarr; Total Unchanged</span>
                    </div>
                </div>
            </div>

            <!-- Step 3: Deliver Goods -->
            <div class="col-md-6 col-lg-3">
                <div class="flow-step">
                    <div class="step-badge">3</div>
                    <h5 class="fw-bold mb-2">Deliver Finished Goods</h5>
                    <p class="text-muted small mb-3">Pick, pack, and validate customer dispatch shipments.</p>
                    <div class="p-3 bg-light rounded-3 font-monospace small">
                        <strong>Example:</strong><br>
                        Deliver 20 steel frames<br>
                        <span class="text-danger fw-bold">&rarr; Stock: -20 units</span>
                    </div>
                </div>
            </div>

            <!-- Step 4: Adjust Damaged Items -->
            <div class="col-md-6 col-lg-3">
                <div class="flow-step">
                    <div class="step-badge">4</div>
                    <h5 class="fw-bold mb-2">Stock Adjustments</h5>
                    <p class="text-muted small mb-3">Reconcile theoretical counts with physical counts &amp; damage.</p>
                    <div class="p-3 bg-light rounded-3 font-monospace small">
                        <strong>Example:</strong><br>
                        3 kg steel damaged<br>
                        <span class="text-danger fw-bold">&rarr; Stock: -3 kg</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <div class="p-3 bg-purple-subtle rounded-3 d-inline-block border" style="background-color: #F3E8F0; border-color: #E2D1DF;">
                <i class="fa-solid fa-circle-check text-success me-2"></i>
                <span class="fw-semibold">Every transaction is automatically logged in the immutable Double-Entry Stock Ledger.</span>
            </div>
        </div>
    </div>
</section>

<!-- Demo Credentials Section (Judge Ready) -->
<section id="credentials" class="py-5">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1 mb-2">Evaluation Credentials</span>
            <h2 class="fw-bold">Ready-to-Test Demo Accounts</h2>
            <p class="text-muted">Click any demo credential below to auto-fill the login form instantly.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Manager Account -->
            <div class="col-md-5">
                <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 text-white" style="background: #714B67;">
                                <i class="fa-solid fa-user-tie fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Inventory Manager</h5>
                                <small class="text-muted">Full System Oversight &amp; Approvals</small>
                            </div>
                        </div>
                        <span class="badge bg-purple text-white" style="background:#714B67;">Manager</span>
                    </div>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li><i class="fa-solid fa-check text-success me-2"></i>Manage incoming &amp; outgoing orders</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i>Validate receipts and deliveries</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i>Configure reordering rules &amp; warehouses</li>
                    </ul>
                    <div class="p-3 bg-light rounded-3 mb-3 font-monospace small">
                        <div><strong>Email:</strong> manager@stocksense.com</div>
                        <div><strong>Password:</strong> manager123</div>
                    </div>
                    <a href="auth/login.php?quick_demo=manager" class="btn btn-brand w-100">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> 1-Click Login as Manager
                    </a>
                </div>
            </div>

            <!-- Staff Account -->
            <div class="col-md-5">
                <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 text-white bg-warning">
                                <i class="fa-solid fa-boxes-packing fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Warehouse Staff</h5>
                                <small class="text-muted">Operations, Picking &amp; Physical Counting</small>
                            </div>
                        </div>
                        <span class="badge bg-secondary">Staff</span>
                    </div>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li><i class="fa-solid fa-check text-success me-2"></i>Perform transfers, picking &amp; shelving</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i>Execute physical count adjustments</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i>Inspect vendor shipments</li>
                    </ul>
                    <div class="p-3 bg-light rounded-3 mb-3 font-monospace small">
                        <div><strong>Email:</strong> staff@stocksense.com</div>
                        <div><strong>Password:</strong> staff123</div>
                    </div>
                    <a href="auth/login.php?quick_demo=staff" class="btn btn-outline-dark w-100">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> 1-Click Login as Staff
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-5 bg-dark text-white-50 border-top border-secondary">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-between">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-3 p-1 text-white" style="background:#00A09D;">
                        <i class="fa-solid fa-cubes-stacked"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-0">StockSense PRO</h5>
                </div>
                <p class="small text-white-50 mb-0">
                    Odoo Hackathon Virtual Round Submission &bull; Problem Statement: StockSense (IMS).
                </p>
            </div>
            
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
