<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$prefill_email = '';
$prefill_pass = '';

// Check if quick demo requested via URL
if (isset($_GET['quick_demo'])) {
    if ($_GET['quick_demo'] === 'manager') {
        $prefill_email = 'manager@stocksense.com';
        $prefill_pass = 'manager123';
    } elseif ($_GET['quick_demo'] === 'staff') {
        $prefill_email = 'staff@stocksense.com';
        $prefill_pass = 'staff123';
    }
}

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } else {
        // Query user using prepared statements
        $user = db_select_one("SELECT * FROM users WHERE email = ?", [$email], "s");

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];

            set_flash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '! You are logged in as ' . strtoupper($user['role']) . '.');
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please verify credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; <?= APP_NAME ?></title>
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
        .auth-container {
            max-width: 460px;
            width: 100%;
        }
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
            padding: 32px 28px;
            text-align: center;
        }
        .demo-card {
            background-color: #F8F9FA;
            border: 1px dashed #714B67;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- Brand / Return Link -->
    <div class="text-center mb-3">
        <a href="<?= BASE_URL ?>index.php" class="text-decoration-none small text-muted">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Public Landing Page
        </a>
    </div>

    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-3 shadow-sm mb-2" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-cubes-stacked fa-lg" style="color: #714B67;"></i>
            </div>
            <h4 class="fw-bold mb-1">StockSense <span class="badge bg-white text-dark small" style="font-size: 0.65rem;">PRO</span></h4>
            <p class="mb-0 text-white-50 small">Odoo Hackathon Inventory Portal</p>
        </div>

        <div class="p-4 p-md-4">
            
            <!-- 1-Click Demo Credentials Autofill Banner (Judge Friendly) -->
            <div class="demo-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge" style="background-color: #714B67; font-size: 0.65rem;">1-CLICK DEMO AUTO-FILL:</span>
                    <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-hand-pointer me-1"></i>Click to auto-populate</small>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 text-truncate py-1 px-2 demo-badge-btn" 
                                onclick="fillDemoCredentials('manager@stocksense.com', 'manager123', 'Manager')">
                            <i class="fa-solid fa-user-tie text-primary me-1"></i> Manager
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 text-truncate py-1 px-2 demo-badge-btn" 
                                onclick="fillDemoCredentials('staff@stocksense.com', 'staff123', 'Staff')">
                            <i class="fa-solid fa-boxes-packing text-warning me-1"></i> Staff
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                        <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="name@company.com" 
                               value="<?= htmlspecialchars($prefill_email) ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label small fw-bold text-muted">Password</label>
                        <a href="forgot_password.php" class="small text-decoration-none" style="color: var(--odoo-accent);">Forgot Password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" 
                               placeholder="••••••••" value="<?= htmlspecialchars($prefill_pass) ?>" required>
                        <button class="btn btn-light border border-start-0" type="button" id="togglePassword">
                            <i class="fa-regular fa-eye text-muted" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-brand py-2 fs-6">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to StockSense
                    </button>
                </div>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <p class="small text-muted mb-0">Don't have an account? <a href="register.php" class="fw-semibold text-decoration-none" style="color: #714B67;">Register Here</a></p>
            </div>

            <!-- XAMPP First Time DB Warning Helper -->
            <?php if (!$db_connected): ?>
                <div class="alert alert-warning mt-3 p-2 small text-center mb-0">
                    <i class="fa-solid fa-database me-1"></i> Database not initialized?
                    <a href="<?= BASE_URL ?>setup.php" class="fw-bold alert-link">Click here to run Auto-Installer</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
    // Show/Hide password toggle
    const toggleBtn = document.getElementById('togglePassword');
    const passInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (toggleBtn && passInput) {
        toggleBtn.addEventListener('click', function () {
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passInput.type = 'password';
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    }
</script>
</body>
</html>
