<?php
/**
 * Shared sidebar. Expects $activePage to be set by the including page
 * (e.g. 'dashboard', 'products', 'receipts', 'delivery', 'transfers',
 * 'adjustments', 'move_history', 'warehouse', 'categories', 'profile').
 */
$activePage = $activePage ?? '';
$pendingReceiptsCount = $pendingReceiptsCount ?? 0;
$pendingDeliveriesCount = $pendingDeliveriesCount ?? 0;
?>
<div class="ss-sidebar-backdrop"></div>
<aside class="ss-sidebar">
    <div class="ss-sidebar-brand">
        <div class="ss-mark">S</div>
        <div>
            <div class="ss-brand-text">StockSense</div>
            <div class="ss-brand-sub">Inventory OS</div>
        </div>
    </div>

    <nav class="ss-nav">
        <div class="ss-nav-label">Overview</div>
        <a href="<?= e(BASE_URL) ?>dashboard/index.php" class="ss-nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
        </a>

        <div class="ss-nav-label">Inventory</div>
        <a href="<?= e(BASE_URL) ?>modules/products/index.php" class="ss-nav-link <?= $activePage === 'products' ? 'active' : '' ?>">
            <i class="fa-solid fa-box"></i><span>Products</span>
        </a>

        <div class="ss-nav-label">Operations</div>
        <a href="<?= e(BASE_URL) ?>modules/receipts/index.php" class="ss-nav-link <?= $activePage === 'receipts' ? 'active' : '' ?>">
            <i class="fa-solid fa-truck-ramp-box"></i><span>Receipts</span>
            <?php if ($pendingReceiptsCount > 0): ?><span class="ss-badge-count"><?= (int)$pendingReceiptsCount ?></span><?php endif; ?>
        </a>
        <a href="<?= e(BASE_URL) ?>modules/delivery/index.php" class="ss-nav-link <?= $activePage === 'delivery' ? 'active' : '' ?>">
            <i class="fa-solid fa-dolly"></i><span>Delivery Orders</span>
            <?php if ($pendingDeliveriesCount > 0): ?><span class="ss-badge-count"><?= (int)$pendingDeliveriesCount ?></span><?php endif; ?>
        </a>
        <a href="<?= e(BASE_URL) ?>modules/transfers/index.php" class="ss-nav-link <?= $activePage === 'transfers' ? 'active' : '' ?>">
            <i class="fa-solid fa-right-left"></i><span>Internal Transfers</span>
        </a>
        <a href="<?= e(BASE_URL) ?>modules/adjustments/index.php" class="ss-nav-link <?= $activePage === 'adjustments' ? 'active' : '' ?>">
            <i class="fa-solid fa-sliders"></i><span>Stock Adjustments</span>
        </a>
        <a href="<?= e(BASE_URL) ?>modules/move_history/index.php" class="ss-nav-link <?= $activePage === 'move_history' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Move History</span>
        </a>

        <?php if (isManager()): ?>
        <div class="ss-nav-label">Settings</div>
        <a href="<?= e(BASE_URL) ?>modules/warehouse/index.php" class="ss-nav-link <?= $activePage === 'warehouse' ? 'active' : '' ?>">
            <i class="fa-solid fa-warehouse"></i><span>Warehouses</span>
        </a>
        <a href="<?= e(BASE_URL) ?>modules/categories/index.php" class="ss-nav-link <?= $activePage === 'categories' ? 'active' : '' ?>">
            <i class="fa-solid fa-tags"></i><span>Categories</span>
        </a>
        <?php endif; ?>

        <div class="ss-nav-label">Account</div>
        <a href="<?= e(BASE_URL) ?>profile/my_profile.php" class="ss-nav-link <?= $activePage === 'profile' ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i><span>My Profile</span>
        </a>
        <a href="<?= e(BASE_URL) ?>auth/logout.php" class="ss-nav-link">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </nav>

    <div class="ss-sidebar-footer">
        <a href="#" data-bs-toggle="tooltip" title="StockSense v1.0"><i class="fa-solid fa-circle-info me-2"></i>StockSense v1.0</a>
    </div>
</aside>
