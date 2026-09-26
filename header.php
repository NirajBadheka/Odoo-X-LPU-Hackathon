<?php
/**
 * Shared header for all authenticated app pages.
 * Expects (optionally) $activePage and $pageTitle to be set before include.
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';

$conn = db();

// Current user info for topbar
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.profile_image, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

$roleLabel = ($currentUser['role_name'] ?? '') === 'inventory_manager' ? 'Inventory Manager' : 'Warehouse Staff';
$initials = '';
if (!empty($currentUser['full_name'])) {
    $parts = explode(' ', trim($currentUser['full_name']));
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

// Sidebar badge counts
$pendingReceiptsCount = (int)$conn->query("SELECT COUNT(*) c FROM stock_operations WHERE operation_type='receipt' AND status IN ('draft','waiting')")->fetch_assoc()['c'];
$pendingDeliveriesCount = (int)$conn->query("SELECT COUNT(*) c FROM stock_operations WHERE operation_type='delivery' AND status IN ('draft','waiting','ready')")->fetch_assoc()['c'];

// Notifications for topbar bell
$notifStmt = $conn->prepare("SELECT notification_id, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$notifStmt->bind_param('i', $_SESSION['user_id']);
$notifStmt->execute();
$notifications = $notifStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$notifStmt->close();
$unreadCount = 0;
foreach ($notifications as $n) { if (!$n['is_read']) $unreadCount++; }

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · StockSense</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2222%22 fill=%22%232F6FED%22/><text x=%2250%22 y=%2268%22 font-size=%2260%22 fill=%22white%22 text-anchor=%22middle%22 font-family=%22Arial%22>S</text></svg>">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/vendor/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/dashboard.css">
</head>
<body data-flash-type="<?= $flash ? e($flash['type']) : '' ?>" data-flash-message="<?= $flash ? e($flash['message']) : '' ?>">
<script>window.SS_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>

<div id="ss-loader">
    <div class="ss-loader-mark">S</div>
    <div class="ss-loader-text">Loading StockSense…</div>
</div>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="ss-main">
    <header class="ss-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="ss-icon-btn" data-toggle="sidebar" title="Toggle menu"><i class="fa-solid fa-bars"></i></button>
            <div class="ss-topbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="globalSearch" placeholder="Search products, SKU, reference no…" autocomplete="off">
                <div id="globalSearchResults" class="list-group position-absolute w-100 shadow-lg" style="z-index:1050; display:none; max-height:320px; overflow-y:auto; top: 44px;"></div>
            </div>
        </div>

        <div class="ss-topbar-actions">
            <div class="dropdown">
                <button class="ss-icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($unreadCount > 0): ?><span class="ss-dot"></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px;">
                    <div class="px-3 py-2 border-bottom fw-bold small">Notifications</div>
                    <div style="max-height: 320px; overflow-y:auto;">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center text-muted-ss py-4 small">No notifications yet</div>
                        <?php else: foreach ($notifications as $n): ?>
                            <div class="dropdown-item-text px-3 py-2 border-bottom small <?= $n['is_read'] ? '' : 'bg-light' ?>">
                                <div><?= e($n['message']) ?></div>
                                <div class="text-muted-ss" style="font-size:11px;"><?= timeAgo($n['created_at']) ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="dropdown">
                <button class="ss-profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="ss-avatar"><?= e($initials ?: 'U') ?></div>
                    <div class="d-none d-md-block text-start">
                        <div class="ss-profile-name"><?= e($currentUser['full_name'] ?? 'User') ?></div>
                        <div class="ss-profile-role"><?= e($roleLabel) ?></div>
                    </div>
                    <i class="fa-solid fa-chevron-down text-muted-ss small d-none d-md-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="<?= e(BASE_URL) ?>profile/my_profile.php"><i class="fa-solid fa-user me-2"></i>My Profile</a>
                    <a class="dropdown-item" href="<?= e(BASE_URL) ?>dashboard/index.php"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="<?= e(BASE_URL) ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="ss-content">
