<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In · StockSense</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/fontawesome/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body data-flash-type="<?= $flash ? e($flash['type']) : '' ?>" data-flash-message="<?= $flash ? e($flash['message']) : '' ?>">

<div id="ss-loader"><div class="ss-loader-mark">S</div><div class="ss-loader-text">Loading StockSense…</div></div>

<div class="auth-wrap">
    <div class="auth-side">
        <a href="../index.php" class="auth-brand"><div class="ss-mark">S</div><span>StockSense</span></a>
        <div class="auth-side-content">
            <h2>Real-time visibility into every product, every location, every movement.</h2>
            <p>Sign in to manage receipts, deliveries, transfers and stock counts from one dashboard.</p>
        </div>
        <div class="auth-quote">
            <p>"We replaced three spreadsheets and a paper register with one screen our whole team actually uses."</p>
            <div class="who">— Inventory Manager, Ahmedabad Warehouse</div>
        </div>
    </div>

    <div class="auth-form-side">
        <div class="auth-form-box">
            <h3>Welcome back</h3>
            <p class="sub">Sign in to your StockSense account to continue.</p>

            <div class="demo-cred-box">
                <div class="title"><i class="fa-solid fa-flask"></i> Demo Credentials — click to autofill</div>
                <span class="demo-cred-chip" data-email="manager@stocksense.in" data-password="Demo@123">
                    <i class="fa-solid fa-user-tie"></i> Inventory Manager
                </span>
                <span class="demo-cred-chip" data-email="staff@stocksense.in" data-password="Demo@123">
                    <i class="fa-solid fa-person-digging"></i> Warehouse Staff
                </span>
                <div class="text-muted-ss mt-2" style="font-size:11.5px;">Password for both: <code>Demo@123</code></div>
            </div>

            <form action="process/login_process.php" method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-envelope field-icon"></i>
                        <input type="email" name="email" id="loginEmail" class="form-control" placeholder="you@company.com" required autofocus>
                    </div>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label d-flex justify-content-between">
                        Password
                        <a href="forgot_password.php" class="text-primary small fw-semibold">Forgot password?</a>
                    </label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required minlength="6">
                        <button type="button" class="toggle-password" data-target="loginPassword"><i class="fa-solid fa-eye"></i></button>
                    </div>
                    <div class="invalid-feedback">Password is required (min 6 characters).</div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                    <label class="form-check-label small" for="rememberMe">Keep me signed in on this device</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Sign In <i class="fa-solid fa-arrow-right ms-1"></i></button>
            </form>

            <p class="text-center text-muted-ss mt-4 mb-0 small">
                Don't have an account? <a href="register.php" class="fw-semibold">Create one</a>
            </p>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/sweetalert2/sweetalert2.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/auth.js"></script>
</body>
</html>
