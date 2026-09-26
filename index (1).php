<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';

// If already logged in, go straight to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockSense — Real-Time Inventory Management System</title>
    <meta name="description" content="StockSense is a modular inventory management system that replaces manual registers and spreadsheets with centralized, real-time stock control.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2222%22 fill=%22%232F6FED%22/><text x=%2250%22 y=%2268%22 font-size=%2260%22 fill=%22white%22 text-anchor=%22middle%22 font-family=%22Arial%22>S</text></svg>">
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<div id="ss-loader">
    <div class="ss-loader-mark">S</div>
    <div class="ss-loader-text">Loading StockSense…</div>
</div>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg lp-navbar">
    <div class="container">
        <a class="navbar-brand" href="#top"><div class="ss-mark">S</div>StockSense</a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#lpNav">
            <i class="fa-solid fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="lpNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#workflow">Workflow</a></li>
                <li class="nav-item"><a class="nav-link" href="#roles">For Your Team</a></li>
                <li class="nav-item"><a class="nav-link" href="#states">Live States</a></li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a href="auth/login.php" class="btn btn-outline-light btn-sm px-3 me-2">Sign In</a>
                </li>
                <li class="nav-item mt-2 mt-lg-0">
                    <a href="auth/register.php" class="btn btn-primary btn-sm px-3">Get Started</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===================== HERO ===================== -->
<header class="lp-hero" id="top">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="lp-hero-badge"><i class="fa-solid fa-bolt"></i> Built for the Odoo Hackathon</span>
                <h1>Stock control that replaces the register, the spreadsheet, and the guesswork.</h1>
                <p class="lead">StockSense centralizes receipts, deliveries, transfers and stock counts into one real-time system — so inventory managers approve with confidence and warehouse staff never lose track of a shelf.</p>
                <div class="d-flex gap-3 flex-wrap mt-4">
                    <a href="auth/register.php" class="btn btn-primary btn-lg px-4">Create Free Account</a>
                    <a href="auth/login.php" class="btn btn-lg px-4" style="background:rgba(255,255,255,.08); color:#fff; border:1px solid rgba(255,255,255,.18);">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                    </a>
                </div>
                <div class="lp-hero-stats">
                    <div><div class="num">5</div><div class="label">Operation Types</div></div>
                    <div><div class="num">Multi</div><div class="label">Warehouse Support</div></div>
                    <div><div class="num">100%</div><div class="label">Ledger Traceability</div></div>
                    <div><div class="num">Real</div><div class="label">Time Dashboard</div></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="lp-preview-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-white fw-semibold small"><i class="fa-solid fa-gauge-high me-2"></i>Inventory Dashboard</div>
                        <div><span class="lp-dot" style="background:#16A34A"></span><span class="text-white-50" style="font-size:11px;">Live</span></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="lp-mini-kpi"><div class="v">1,842</div><div class="l">Products in Stock</div></div></div>
                        <div class="col-6"><div class="lp-mini-kpi"><div class="v">7</div><div class="l">Low Stock Alerts</div></div></div>
                        <div class="col-6"><div class="lp-mini-kpi"><div class="v">4</div><div class="l">Pending Receipts</div></div></div>
                        <div class="col-6"><div class="lp-mini-kpi"><div class="v">3</div><div class="l">Pending Deliveries</div></div></div>
                    </div>
                    <div style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.06); border-radius:12px; padding:14px;">
                        <div class="d-flex justify-content-between text-white-50" style="font-size:11px;"><span>RCPT-1002 · Bajaj Electronics</span><span>Waiting</span></div>
                        <div class="d-flex justify-content-between text-white-50 mt-2" style="font-size:11px;"><span>DEL-1002 · Cotton Fabric</span><span>Ready</span></div>
                        <div class="d-flex justify-content-between text-white-50 mt-2" style="font-size:11px;"><span>INT-1002 · Ahmedabad → Surat</span><span>Ready</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ===================== TRUSTED / LOGOS STRIP (states) ===================== -->
<section id="states" class="py-4" style="background:#fff; border-bottom:1px solid var(--ss-border);">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-2"><span class="badge bg-secondary status-badge px-3 py-2">Draft</span></div>
            <div class="col-6 col-md-2"><span class="badge bg-warning text-dark status-badge px-3 py-2">Waiting</span></div>
            <div class="col-6 col-md-2"><span class="badge bg-info text-dark status-badge px-3 py-2">Ready</span></div>
            <div class="col-6 col-md-2"><span class="badge bg-success status-badge px-3 py-2">Done</span></div>
            <div class="col-6 col-md-2"><span class="badge bg-danger status-badge px-3 py-2">Canceled</span></div>
            <div class="col-md-2 text-muted-ss small d-flex align-items-center justify-content-center">Every operation, one workflow</div>
        </div>
    </div>
</section>

<!-- ===================== FEATURES ===================== -->
<section id="features" class="lp-section">
    <div class="container">
        <div class="lp-section-tag">CORE FEATURES</div>
        <h2 class="lp-section-title">Everything your warehouse floor and back office both need.</h2>
        <p class="lp-section-sub">One system, two very different jobs — designed around how managers and staff actually work.</p>

        <div class="row g-4 mt-3">
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:var(--ss-primary-soft); color:var(--ss-primary);"><i class="fa-solid fa-box"></i></div>
                    <h5>Product Management</h5>
                    <p>Create products with SKU, category, unit of measure, reorder rules and opening stock in seconds.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:var(--ss-success-soft); color:var(--ss-success);"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    <h5>Receipts (Incoming)</h5>
                    <p>Log supplier deliveries, validate against expected quantities, and stock increases automatically.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:var(--ss-danger-soft); color:var(--ss-danger);"><i class="fa-solid fa-dolly"></i></div>
                    <h5>Delivery Orders</h5>
                    <p>Pick, pack, and validate outgoing shipments — with stock decreasing the moment it ships.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:var(--ss-info-soft); color:var(--ss-info);"><i class="fa-solid fa-right-left"></i></div>
                    <h5>Internal Transfers</h5>
                    <p>Move stock between racks, floors, or entire warehouses — every movement logged to the ledger.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:var(--ss-warning-soft); color:var(--ss-warning);"><i class="fa-solid fa-sliders"></i></div>
                    <h5>Stock Adjustments</h5>
                    <p>Reconcile physical counts against system records, with every correction logged and auditable.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:var(--ss-primary-soft); color:var(--ss-primary);"><i class="fa-solid fa-bell"></i></div>
                    <h5>Smart Alerts</h5>
                    <p>Automatic low-stock and out-of-stock alerts the moment a product crosses its reorder point.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== WORKFLOW ===================== -->
<section id="workflow" class="lp-section" style="background:#fff;">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="lp-section-tag">HOW STOCK FLOWS</div>
                <h2 class="lp-section-title" style="font-size:28px;">From vendor to shelf to customer — nothing gets lost.</h2>
                <p class="lp-section-sub">A simplified example showing exactly how one batch of stock moves through StockSense.</p>
            </div>
            <div class="col-lg-7">
                <div class="lp-flow-step">
                    <div class="lp-flow-num">1</div>
                    <div><h6>Receive goods from vendor</h6><p>Receive 100 kg Steel → Stock: <strong class="text-success">+100</strong></p></div>
                </div>
                <div class="lp-flow-step">
                    <div class="lp-flow-num">2</div>
                    <div><h6>Move to production rack</h6><p>Internal transfer: Main Store → Production Rack — total stock unchanged, location updated.</p></div>
                </div>
                <div class="lp-flow-step">
                    <div class="lp-flow-num">3</div>
                    <div><h6>Deliver finished goods</h6><p>Deliver 20 steel → Stock: <strong class="text-danger">−20</strong></p></div>
                </div>
                <div class="lp-flow-step">
                    <div class="lp-flow-num">4</div>
                    <div><h6>Adjust damaged items</h6><p>3 kg steel damaged → Stock: <strong class="text-danger">−3</strong>. Everything logged in the Stock Ledger.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== ROLES ===================== -->
<section id="roles" class="lp-section">
    <div class="container">
        <div class="lp-section-tag">BUILT FOR YOUR TEAM</div>
        <h2 class="lp-section-title">Two roles. Two very different daily views.</h2>
        <div class="row g-4 mt-3">
            <div class="col-lg-6">
                <div class="lp-role-card">
                    <div class="lp-feature-icon" style="background:var(--ss-primary-soft); color:var(--ss-primary);"><i class="fa-solid fa-user-tie"></i></div>
                    <h5 class="mt-3 mb-0">Inventory Manager</h5>
                    <p class="text-muted-ss small mb-0">Owns approvals, master data & reporting</p>
                    <ul>
                        <li><i class="fa-solid fa-circle-check"></i>Approve and validate receipts &amp; deliveries</li>
                        <li><i class="fa-solid fa-circle-check"></i>Manage products, categories &amp; warehouses</li>
                        <li><i class="fa-solid fa-circle-check"></i>Full visibility across all locations</li>
                        <li><i class="fa-solid fa-circle-check"></i>Reorder point &amp; low-stock oversight</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="lp-role-card">
                    <div class="lp-feature-icon" style="background:var(--ss-warning-soft); color:var(--ss-warning);"><i class="fa-solid fa-person-digging"></i></div>
                    <h5 class="mt-3 mb-0">Warehouse Staff</h5>
                    <p class="text-muted-ss small mb-0">Owns the physical floor operations</p>
                    <ul>
                        <li><i class="fa-solid fa-circle-check"></i>Pick, pack &amp; process delivery orders</li>
                        <li><i class="fa-solid fa-circle-check"></i>Perform internal transfers between racks</li>
                        <li><i class="fa-solid fa-circle-check"></i>Run physical stock counts &amp; adjustments</li>
                        <li><i class="fa-solid fa-circle-check"></i>Real-time view of assigned tasks</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== CTA ===================== -->
<section class="lp-section">
    <div class="container">
        <div class="lp-cta">
            <h2>Ready to digitize your stockroom?</h2>
            <p class="mb-4">Sign up in under a minute — demo credentials included, no setup required.</p>
            <a href="auth/register.php" class="btn btn-primary btn-lg px-4 me-2">Create Free Account</a>
            <a href="auth/login.php" class="btn btn-lg px-4" style="background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2);">Sign In</a>
        </div>
    </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer class="lp-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <a class="navbar-brand mb-3 d-inline-flex" href="#top"><div class="ss-mark me-2">S</div>StockSense</a>
                <p style="font-size:13.5px; max-width:280px;">A modular Inventory Management System built to digitize stock operations end-to-end.</p>
                <div class="d-flex mt-3">
                    <a href="#" class="lp-social"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" class="lp-social"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="lp-social"><i class="fa-brands fa-x-twitter"></i></a>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <h6>Product</h6>
                <a href="#features">Features</a>
                <a href="#workflow">Workflow</a>
                <a href="#roles">For Your Team</a>
            </div>
            <div class="col-6 col-md-2">
                <h6>Account</h6>
                <a href="auth/login.php">Sign In</a>
                <a href="auth/register.php">Register</a>
                <a href="auth/forgot_password.php">Forgot Password</a>
            </div>
            <div class="col-6 col-md-2">
                <h6>Modules</h6>
                <a href="auth/login.php">Receipts</a>
                <a href="auth/login.php">Delivery Orders</a>
                <a href="auth/login.php">Move History</a>
            </div>
            <div class="col-6 col-md-2">
                <h6>Support</h6>
                <a href="mailto:support@stocksense.in">support@stocksense.in</a>
                <a href="#top">Back to top</a>
            </div>
        </div>
        <div class="lp-footer-bottom d-flex justify-content-between flex-wrap gap-2">
            <span>&copy; <?= date('Y') ?> StockSense. Built for the Odoo Hackathon — Virtual Round.</span>
            <span>Made with PHP · MySQL · Bootstrap 5</span>
        </div>
    </div>
</footer>

<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
