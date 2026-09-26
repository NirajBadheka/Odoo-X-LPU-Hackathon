<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

$error = '';
$otp_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid registered email address.';
    } else {
        $user = db_select_one("SELECT id, full_name, email FROM users WHERE email = ?", [$email], "s");
        
        if ($user) {
            // Generate 6-digit OTP
            $otp = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Store in database
            db_query("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?", [$otp, $expiry, $user['id']], "ssi");

            $_SESSION['reset_email'] = $email;
            $_SESSION['simulated_otp'] = $otp; // Saved for instant demo verification notification

            set_flash('success', "6-Digit OTP generated: {$otp} (Simulated for Hackathon Evaluation)");
            header('Location: ' . BASE_URL . 'auth/verify_otp.php');
            exit;
        } else {
            $error = 'No account found with this email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password &mdash; <?= APP_NAME ?></title>
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
    <div class="text-center mb-3">
        <a href="login.php" class="text-decoration-none small text-muted">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
        </a>
    </div>

    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-3 shadow-sm mb-2" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-key fa-lg" style="color: #714B67;"></i>
            </div>
            <h4 class="fw-bold mb-1">Reset Password</h4>
            <p class="mb-0 text-white-50 small">Step 1: Request 6-Digit OTP</p>
        </div>

        <div class="p-4">
            <p class="text-muted small mb-3">
                Enter your registered email address. We will generate a secure 6-digit One-Time Password (OTP) to reset your account credentials.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Registered Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-regular fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="e.g. manager@stocksense.com" required value="<?= htmlspecialchars($_POST['email'] ?? 'manager@stocksense.com') ?>">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-brand py-2">
                        <i class="fa-solid fa-paper-plane me-2"></i> Send 6-Digit OTP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
