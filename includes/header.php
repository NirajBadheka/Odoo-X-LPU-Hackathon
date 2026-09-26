<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

$current_user = get_current_user_data();
$flash_success = get_flash('success');
$flash_error = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> &mdash; <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom Theme 1 (Odoo Enterprise Violet & Mint) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body data-flash-success="<?= htmlspecialchars($flash_success ?? '') ?>" data-flash-error="<?= htmlspecialchars($flash_error ?? '') ?>">

<!-- Preloader / Loading Screen -->
<div id="page-preloader">
    <div class="spinner-odoo mb-3"></div>
    <div class="d-flex align-items-center gap-2">
        <span class="fw-bold" style="color: #714B67; font-size: 1.1rem;"><?= APP_NAME ?></span>
        <span class="badge bg-secondary-subtle text-secondary small">Loading...</span>
    </div>
</div>

<div class="app-wrapper">
    <!-- Left Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebar-toggle" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars fa-lg text-secondary"></i>
                </button>
                <div class="d-none d-sm-block">
                    <h5 class="mb-0 fw-bold" style="color: #212529;"><?= htmlspecialchars($page_title ?? 'Inventory Telemetry') ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($page_subtitle ?? 'Real-time multi-location stock controls') ?></small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- System Status Indicator -->
                <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border">
                    <span class="d-inline-block rounded-circle bg-success" style="width: 8px; height: 8px;"></span>
                    <span class="small fw-semibold text-secondary">System Online</span>
                </div>

                <!-- Quick Action Button -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-brand dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-plus me-1"></i> New
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><h6 class="dropdown-header text-uppercase small">Create Operation</h6></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operations/receipt_create.php"><i class="fa-solid fa-arrow-down-left-and-arrow-up-right-to-center text-primary me-2"></i>Receipt (Incoming)</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operations/delivery_create.php"><i class="fa-solid fa-truck-ramp-box text-danger me-2"></i>Delivery Order (Outgoing)</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operations/transfer_create.php"><i class="fa-solid fa-arrow-right-arrow-left text-info me-2"></i>Internal Transfer</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operations/adjustment_create.php"><i class="fa-solid fa-sliders text-warning me-2"></i>Stock Adjustment</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>products/create.php"><i class="fa-solid fa-box text-success me-2"></i>New Product</a></li>
                    </ul>
                </div>

                <!-- User Profile Dropdown -->
                <?php if ($current_user): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border d-flex align-items-center gap-2 rounded-pill px-2 py-1" type="button" data-bs-toggle="dropdown">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 28px; height: 28px; background: #714B67; font-size: 0.8rem;">
                                <?= strtoupper(substr($current_user['name'], 0, 1)) ?>
                            </div>
                            <span class="d-none d-sm-inline fw-semibold small pe-1"><?= htmlspecialchars(explode(' ', $current_user['name'])[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold small"><?= htmlspecialchars($current_user['name']) ?></div>
                                <div class="text-muted smaller"><?= htmlspecialchars($current_user['email']) ?></div>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase mt-1" style="font-size: 0.65rem;">
                                    <?= htmlspecialchars($current_user['role']) ?>
                                </span>
                            </li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>profile/index.php"><i class="fa-solid fa-user-gear me-2 text-muted"></i>My Profile</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Log Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-sm btn-outline-secondary">Sign In</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="content-container">
