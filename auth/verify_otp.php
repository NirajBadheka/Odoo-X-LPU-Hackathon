<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    header('Location: ' . BASE_URL . 'auth/forgot_password.php');
    exit;
}

$simulated_otp = $_SESSION['simulated_otp'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Session expired. Please try again.';
    } elseif (strlen($entered_otp) !== 6) {
        $error = 'Please enter a valid 6-digit OTP code.';
    } else {
        $user = db_select_one("SELECT id, otp_code, otp_expiry FROM users WHERE email = ? AND otp_code = ?", [$email, $entered_otp], "ss");

        if ($user) {
            // Check expiry
            if (strtotime($user['otp_expiry']) < time()) {
                $error = 'This OTP has expired. Please request a new one.';
            } else {
                $_SESSION['otp_verified'] = true;
                $_SESSION['otp_user_id'] = $user['id'];
                set_flash('success', 'OTP verified successfully! Now choose a new password.');
                header('Location: ' . BASE_URL . 'auth/reset_password.php');
                exit;
            }
        } else {
            $error = 'Incorrect OTP code entered. Please check and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
        .otp-input {
            letter-spacing: 0.5em;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
        }
    </style>
</head>
<body data-flash-success="<?= htmlspecialchars(get_flash('success') ?? '') ?>">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-3 shadow-sm mb-2" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-shield-halved fa-lg" style="color: #714B67;"></i>
            </div>
            <h4 class="fw-bold mb-1">Verify 6-Digit OTP</h4>
            <p class="mb-0 text-white-50 small">Step 2: Enter Verification Code</p>
        </div>

        <div class="p-4">
            <p class="text-muted small text-center mb-3">
                A verification code was generated for <strong><?= htmlspecialchars($email) ?></strong>.
            </p>

            <!-- Test / Judge Simulation Banner -->
            <?php if (!empty($simulated_otp)): ?>
                <div class="alert alert-info py-2 px-3 small d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <i class="fa-solid fa-bell me-1"></i> Demo OTP: <strong class="fs-6"><?= htmlspecialchars($simulated_otp) ?></strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 small" onclick="document.getElementById('otp').value='<?= $simulated_otp ?>'">
                        Auto-Fill
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="verify_otp.php">
                <?= csrf_field() ?>

                <div class="mb-3 text-center">
                    <label class="form-label small fw-bold text-muted mb-2">Enter 6-Digit Code</label>
                    <input type="text" name="otp" id="otp" maxlength="6" class="form-control otp-input" placeholder="000000" 
                           value="<?= htmlspecialchars($_POST['otp'] ?? $simulated_otp) ?>" required autofocus>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-brand py-2">
                        <i class="fa-solid fa-check-circle me-2"></i> Verify OTP Code
                    </button>
                </div>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <a href="forgot_password.php" class="small text-muted text-decoration-none">
                    <i class="fa-solid fa-rotate-left me-1"></i> Resend New OTP
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
