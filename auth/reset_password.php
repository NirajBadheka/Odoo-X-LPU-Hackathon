<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

$email = $_SESSION['reset_email'] ?? '';
$user_id = $_SESSION['otp_user_id'] ?? null;
$verified = $_SESSION['otp_verified'] ?? false;

if (!$verified || !$user_id) {
    header('Location: ' . BASE_URL . 'auth/forgot_password.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please try again.';
    } elseif (strlen($password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        db_query("UPDATE users SET password = ?, otp_code = NULL, otp_expiry = NULL WHERE id = ?", [$hashed, $user_id], "si");

        // Clear reset session variables
        unset($_SESSION['reset_email']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_user_id']);
        unset($_SESSION['simulated_otp']);

        set_flash('success', 'Your password has been reset successfully! Please sign in with your new password.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password &mdash; <?= APP_NAME ?></title>
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
            padding: 20px;
        }
        .auth-container { max-width: 440px; width: 100%; }
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
    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-3 shadow-sm mb-2" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-lock-open fa-lg" style="color: #714B67;"></i>
            </div>
            <h4 class="fw-bold mb-1">Set New Password</h4>
            <p class="mb-0 text-white-50 small">Step 3: Create Secure Password</p>
        </div>

        <div class="p-4">
            <p class="text-muted small mb-3">
                Resetting credentials for <strong><?= htmlspecialchars($email) ?></strong>.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">New Password (Min. 6 chars)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-brand py-2">
                        <i class="fa-solid fa-key me-2"></i> Save New Password &amp; Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
