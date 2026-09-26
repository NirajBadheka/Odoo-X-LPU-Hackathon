<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

require_login();
$current_user = get_current_user_data();

$page_title = 'My Profile';
$page_subtitle = 'Manage account settings and security credentials';

$user = db_select_one("SELECT * FROM users WHERE id = ?", [$current_user['id']], "i");
$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Session expired.';
    } elseif (empty($full_name)) {
        $error = 'Full name cannot be empty.';
    } else {
        db_query("UPDATE users SET full_name = ?, phone = ? WHERE id = ?", [$full_name, $phone, $user['id']], "ssi");
        $_SESSION['user_name'] = $full_name;
        set_flash('success', 'Profile updated successfully.');
        header('Location: ' . BASE_URL . 'profile/index.php');
        exit;
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Session expired.';
    } elseif (!password_verify($current_pass, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_pass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_pass !== $confirm_pass) {
        $error = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        db_query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $user['id']], "si");
        set_flash('success', 'Password changed successfully!');
        header('Location: ' . BASE_URL . 'profile/index.php');
        exit;
    }
}

// User activity metrics
$user_moves = db_select_one("SELECT COUNT(*) as cnt FROM stock_moves WHERE user_id = ?", [$user['id']], "i")['cnt'] ?? 0;
$user_receipts = db_select_one("SELECT COUNT(*) as cnt FROM receipts WHERE validated_by = ?", [$user['id']], "i")['cnt'] ?? 0;
$user_deliveries = db_select_one("SELECT COUNT(*) as cnt FROM delivery_orders WHERE validated_by = ?", [$user['id']], "i")['cnt'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Profile Summary Sidebar -->
    <div class="col-lg-4">
        <div class="card card-odoo text-center p-4 shadow-sm mb-4">
            <div class="d-inline-flex align-items-center justify-content-center text-white rounded-circle shadow-sm mx-auto mb-3" 
                 style="width: 80px; height: 80px; background: #714B67; font-size: 2rem; font-weight: 700;">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
            <div class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></div>
            <div>
                <span class="badge py-1 px-3 text-uppercase fw-bold" style="background:#00A09D;">
                    <?= htmlspecialchars($user['role']) ?>
                </span>
            </div>
            <hr>
            <div class="small text-muted text-start">
                <div class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i> <?= htmlspecialchars($user['phone'] ?: 'No phone provided') ?></div>
                <div class="mb-2"><i class="fa-solid fa-calendar me-2 text-muted"></i> Joined <?= date('F Y', strtotime($user['created_at'])) ?></div>
                <div><i class="fa-solid fa-shield-halved me-2 text-success"></i> RBAC Security Active</div>
            </div>
        </div>

        <!-- Activity Stats -->
        <div class="card card-odoo p-4 shadow-sm">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-line me-2 text-primary"></i>My Ledger Contributions</h6>
            <div class="list-group list-group-flush small">
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Total Stock Movements Logged</span>
                    <strong class="text-dark"><?= $user_moves ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Receipts Validated</span>
                    <strong class="text-success"><?= $user_receipts ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Shipments Dispatched</span>
                    <strong class="text-danger"><?= $user_deliveries ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile & Password -->
    <div class="col-lg-8">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center mb-4">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Basic Details Form -->
        <div class="card card-odoo shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-user-pen me-2 text-primary"></i>Personal Information
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_info">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Email (Immutable)</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-brand btn-sm px-3">
                            <i class="fa-solid fa-save me-1"></i> Update Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="card card-odoo shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-lock me-2 text-warning"></i>Change Password
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">New Password (Min. 6 chars)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-dark btn-sm px-3">
                            <i class="fa-solid fa-key me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
