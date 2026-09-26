<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}
$flash = getFlash();
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account · StockSense</title>
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
            <h2>Join your team's inventory workspace in under a minute.</h2>
            <p>Choose your role, set up your account, and start tracking stock from day one.</p>
        </div>
        <div class="auth-quote">
            <p>"Onboarding new warehouse staff used to take a day of training. Now it takes ten minutes."</p>
            <div class="who">— Operations Lead</div>
        </div>
    </div>

    <div class="auth-form-side">
        <div class="auth-form-box" style="max-width: 460px;">
            <h3>Create your account</h3>
            <p class="sub">Fill in your details to get started with StockSense.</p>

            <form action="process/register_process.php" method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label">I am a...</label>
                    <input type="hidden" name="role_id" id="role_id" value="<?= e($old['role_id'] ?? '2') ?>">
                    <div class="role-select-cards">
                        <div class="role-select-card <?= (($old['role_id'] ?? '2') == '1') ? 'selected' : '' ?>" data-role-id="1">
                            <i class="fa-solid fa-user-tie"></i>
                            <div class="name">Inventory Manager</div>
                        </div>
                        <div class="role-select-card <?= (($old['role_id'] ?? '2') == '2') ? 'selected' : '' ?>" data-role-id="2">
                            <i class="fa-solid fa-person-digging"></i>
                            <div class="name">Warehouse Staff</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-user field-icon"></i>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Aarav Shah" required minlength="3" value="<?= e($old['full_name'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-envelope field-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="you@company.com" required value="<?= e($old['email'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-phone field-icon"></i>
                        <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile number" pattern="[6-9][0-9]{9}" required value="<?= e($old['phone'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback">Please enter a valid 10-digit Indian mobile number.</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" name="password" id="registerPassword" class="form-control" placeholder="At least 8 characters" required minlength="8">
                        <button type="button" class="toggle-password" data-target="registerPassword"><i class="fa-solid fa-eye"></i></button>
                    </div>
                    <div class="password-strength"><div class="password-strength-bar"></div></div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="text-muted-ss" style="font-size:11.5px;">Use 8+ chars with upper/lowercase, a number &amp; a symbol</span>
                        <span id="strengthLabel" style="font-size:11.5px; font-weight:700;"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Re-enter your password" required>
                    </div>
                    <div class="invalid-feedback">Passwords do not match.</div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                    <label class="form-check-label small" for="terms">I agree to the Terms of Service and Privacy Policy</label>
                    <div class="invalid-feedback">You must agree before continuing.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">Create Account <i class="fa-solid fa-user-plus ms-1"></i></button>
            </form>

            <p class="text-center text-muted-ss mt-4 mb-0 small">
                Already have an account? <a href="login.php" class="fw-semibold">Sign in</a>
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
