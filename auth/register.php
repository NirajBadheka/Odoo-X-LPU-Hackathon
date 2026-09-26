<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? ROLE_STAFF;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please reload the page.';
    } elseif (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password confirmation does not match.';
    } elseif (!in_array($role, [ROLE_MANAGER, ROLE_STAFF])) {
        $error = 'Please select a valid user role.';
    } else {
        // Check if email already registered
        $existing = db_select_one("SELECT id FROM users WHERE email = ?", [$email], "s");
        if ($existing) {
            $error = 'This email address is already registered. Please log in.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $res = db_query("INSERT INTO users (full_name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [
                $full_name, $email, $hashed, $role, $phone
            ], "sssss");

            if ($res['success']) {
                set_flash('success', 'Registration successful! You can now log in with your credentials.');
                header('Location: ' . BASE_URL . 'auth/login.php');
                exit;
            } else {
                $error = 'Registration failed: ' . $res['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #F8F9FA 0%, #EDE7EB 100%);
            padding: 25px 20px;
        }
        .auth-container { max-width: 500px; width: 100%; }
        .auth-card {
            background: #FFFFFF;
            border-radius: 18px;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 12px 36px rgba(113, 75, 103, 0.08);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #5B3A53, #714B67);
            color: #FFFFFF;
            padding: 28px 24px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="text-center mb-3">
        <a href="<?= BASE_URL ?>index.php" class="text-decoration-none small text-muted">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Landing Page
        </a>
    </div>

    <div class="auth-card">
        <div class="auth-header">
            <h4 class="fw-bold mb-1">Create an Account</h4>
            <p class="mb-0 text-white-50 small">Join StockSense Pro Multi-Location Inventory</p>
        </div>

        <div class="p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">User Role *</label>
                    <select name="role" class="form-select" required>
                        <option value="<?= ROLE_MANAGER ?>" <?= (($_POST['role'] ?? '') === ROLE_MANAGER) ? 'selected' : '' ?>>Inventory Manager (Full Oversight & Approvals)</option>
                        <option value="<?= ROLE_STAFF ?>" <?= (($_POST['role'] ?? '') === ROLE_STAFF) ? 'selected' : '' ?> selected>Warehouse Staff (Transfers, Picking & Counting)</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-brand py-2">
                        <i class="fa-solid fa-user-plus me-2"></i> Register Account
                    </button>
                </div>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <p class="small text-muted mb-0">Already have an account? <a href="login.php" class="fw-semibold text-decoration-none" style="color: #714B67;">Sign In</a></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
