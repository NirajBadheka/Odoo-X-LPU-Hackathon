<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="app-sidebar">
    <!-- Brand Logo -->
    <a href="<?= BASE_URL ?>dashboard.php" class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-cubes-stacked"></i>
        </div>
        <div>
            <div class="fw-bold fs-6 lh-1 text-white">
                StockSense <span class="badge bg-white text-dark py-0 px-1 fw-bold" style="font-size: 0.65rem;">PRO</span>
            </div>
            <small class="text-white-50" style="font-size: 0.72rem;">Inventory Intelligence</small>
        </div>
    </a>

    <!-- User Mini Badge -->
    <?php if ($current_user): ?>
        <div class="px-3 py-2 mx-3 my-3 rounded-3" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <span class="d-inline-block rounded-circle bg-success" style="width: 7px; height: 7px;"></span>
                    <span class="small fw-semibold text-white text-truncate"><?= htmlspecialchars($current_user['name']) ?></span>
                </div>
                <span class="badge py-1 px-2 text-uppercase fw-bold" style="background: var(--odoo-accent); font-size: 0.62rem;">
                    <?= htmlspecialchars($current_user['role']) ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- Main Dashboard -->
        <a href="<?= BASE_URL ?>dashboard.php" class="nav-link <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-chart-pie"></i> Dashboard</span>
        </a>

        <!-- Master Products Section -->
        <div class="nav-header">Products &amp; Master Data</div>
        <a href="<?= BASE_URL ?>products/index.php" class="nav-link <?= ($current_dir === 'products' && in_array($current_page, ['index.php', 'create.php', 'edit.php'])) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-boxes-stacked"></i> Products</span>
        </a>
        <a href="<?= BASE_URL ?>products/categories.php" class="nav-link <?= ($current_dir === 'products' && $current_page === 'categories.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-tags"></i> Product Categories</span>
        </a>
        <a href="<?= BASE_URL ?>products/stock_by_location.php" class="nav-link <?= ($current_dir === 'products' && $current_page === 'stock_by_location.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-map-location-dot"></i> Stock by Location</span>
        </a>

        <!-- Operations Section -->
        <div class="nav-header">Inventory Operations</div>
        <a href="<?= BASE_URL ?>operations/receipts.php" class="nav-link <?= ($current_dir === 'operations' && in_array($current_page, ['receipts.php', 'receipt_create.php', 'receipt_view.php'])) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrow-down-left-and-arrow-up-right-to-center"></i> Receipts (Incoming)</span>
        </a>
        <a href="<?= BASE_URL ?>operations/deliveries.php" class="nav-link <?= ($current_dir === 'operations' && in_array($current_page, ['deliveries.php', 'delivery_create.php', 'delivery_view.php'])) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-truck-ramp-box"></i> Delivery Orders</span>
        </a>
        <a href="<?= BASE_URL ?>operations/transfers.php" class="nav-link <?= ($current_dir === 'operations' && in_array($current_page, ['transfers.php', 'transfer_create.php'])) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrow-right-arrow-left"></i> Internal Transfers</span>
        </a>
        <a href="<?= BASE_URL ?>operations/adjustments.php" class="nav-link <?= ($current_dir === 'operations' && in_array($current_page, ['adjustments.php', 'adjustment_create.php'])) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-sliders"></i> Stock Adjustments</span>
        </a>
        <a href="<?= BASE_URL ?>operations/move_history.php" class="nav-link <?= ($current_dir === 'operations' && $current_page === 'move_history.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-clock-rotate-left"></i> Move History (Ledger)</span>
        </a>

        <!-- Configuration -->
        <div class="nav-header">Settings</div>
        <a href="<?= BASE_URL ?>settings/warehouses.php" class="nav-link <?= ($current_dir === 'settings' && $current_page === 'warehouses.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-warehouse"></i> Warehouses</span>
        </a>

        <!-- Profile & Account (Left Sidebar Requirement) -->
        <div class="nav-header">Account</div>
        <a href="<?= BASE_URL ?>profile/index.php" class="nav-link <?= ($current_dir === 'profile') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-user-circle"></i> My Profile</span>
        </a>
        <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link text-danger-emphasis">
            <span><i class="fa-solid fa-right-from-bracket"></i> Logout</span>
        </a>
    </nav>

    <!-- Sidebar Bottom: 1-Click Demo Credentials Quick Fill -->
    <div class="p-3 mt-auto border-top" style="border-color: rgba(255,255,255,0.08) !important;">
        <div class="small text-white-50 fw-bold mb-2 text-uppercase" style="font-size: 0.65rem;">Quick Role Switch:</div>
        <div class="d-grid gap-1">
            <a href="<?= BASE_URL ?>auth/login.php?quick_demo=manager" class="btn btn-sm text-start text-white p-2 rounded-2" style="background: rgba(255,255,255,0.08); font-size: 0.75rem;">
                <i class="fa-solid fa-user-tie text-info me-1"></i> Switch to Manager
            </a>
            <a href="<?= BASE_URL ?>auth/login.php?quick_demo=staff" class="btn btn-sm text-start text-white p-2 rounded-2" style="background: rgba(255,255,255,0.08); font-size: 0.75rem;">
                <i class="fa-solid fa-boxes-packing text-warning me-1"></i> Switch to Staff
            </a>
        </div>
    </div>
</aside>
