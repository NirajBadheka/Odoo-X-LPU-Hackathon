<?php
/**
 * StockSense Pro - 1-Click Database Setup & Auto-Installer
 * Runs seamlessly on XAMPP (Apache + MySQL)
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'pro_stocksense';

$status_log = [];
$error_log = [];
$installed = false;

if (isset($_POST['run_install'])) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';

    // Step 1: Connect to MySQL Server
    $mysqli = @new mysqli($host, $user, $pass);
    if ($mysqli->connect_error) {
        $error_log[] = "Failed to connect to MySQL: " . $mysqli->connect_error;
    } else {
        $status_log[] = "Connected to MySQL Server successfully.";

        // Step 2: Create Database
        if ($mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            $status_log[] = "Database `$dbname` created or verified.";
            $mysqli->select_db($dbname);

            // Step 3: Read SQL File
            $sql_file = __DIR__ . '/database/pro_stocksense.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                
                // Execute multi-query
                if ($mysqli->multi_query($sql_content)) {
                    do {
                        // Flush multi-query buffers
                        if ($result = $mysqli->store_result()) {
                            $result->free();
                        }
                    } while ($mysqli->more_results() && $mysqli->next_result());
                    
                    $status_log[] = "Database schema and initial tables executed successfully.";

                    // Step 4: Ensure dynamic bcrypt password hash is updated for manager & staff
                    $manager_hash = password_hash('manager123', PASSWORD_BCRYPT);
                    $staff_hash = password_hash('staff123', PASSWORD_BCRYPT);

                    $mysqli->query("UPDATE `users` SET `password` = '$manager_hash' WHERE `email` = 'manager@stocksense.com'");
                    $mysqli->query("UPDATE `users` SET `password` = '$staff_hash' WHERE `email` = 'staff@stocksense.com'");

                    $status_log[] = "Demo users seeded with verified bcrypt passwords!";
                    $installed = true;
                } else {
                    $error_log[] = "SQL Execution Error: " . $mysqli->error;
                }
            } else {
                $error_log[] = "SQL dump file not found at: $sql_file";
            }
        } else {
            $error_log[] = "Could not create database: " . $mysqli->error;
        }
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockSense Pro - Database Auto-Installer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-primary: #714B67;
            --brand-dark: #5B3A53;
            --brand-accent: #00A09D;
            --brand-accent-hover: #008784;
        }
        body {
            background-color: #F8F9FA;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #212529;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(113, 75, 103, 0.08);
            border: 1px solid #E9ECEF;
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .setup-header {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-primary));
            color: #FFFFFF;
            padding: 30px;
            text-align: center;
        }
        .btn-brand {
            background-color: var(--brand-accent);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .btn-brand:hover {
            background-color: var(--brand-accent-hover);
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <div class="d-inline-flex align-items-center justify-content-center bg-white text-dark rounded-3 p-3 mb-3 shadow-sm" style="width: 56px; height: 56px;">
            <i class="fa-solid fa-cubes-stacked fa-xl" style="color: #714B67;"></i>
        </div>
        <h3 class="fw-bold mb-1">StockSense Pro</h3>
        <p class="mb-0 text-white-50">1-Click Database Setup &amp; Demo Data Seeder</p>
    </div>

    <div class="p-4 p-md-5">
        <?php if (!empty($error_log)): ?>
            <div class="alert alert-danger">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-triangle-exclamation me-2"></i>Installation Error</h6>
                <ul class="mb-0 ps-3">
                    <?php foreach ($error_log as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($installed): ?>
            <div class="alert alert-success">
                <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-check me-2"></i>Setup Complete!</h5>
                <p class="mb-2">Database <code>pro_stocksense</code> has been created and populated with demo data.</p>
                <hr>
                <div class="small mb-3">
                    <strong>Demo Credentials Ready:</strong>
                    <div class="mt-1">
                        <span class="badge bg-purple text-white me-2" style="background:#714B67;">Manager:</span>
                        <code>manager@stocksense.com</code> / <code>manager123</code>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-secondary me-2">Staff:</span>
                        <code>staff@stocksense.com</code> / <code>staff123</code>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="index.php" class="btn btn-brand">
                        <i class="fa-solid fa-rocket me-2"></i>Launch Landing Page
                    </a>
                    <a href="auth/login.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Go Directly to Login
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-4">
                This installer will create the MySQL database <strong><code>pro_stocksense</code></strong>, 
                set up all 13 relational tables with foreign keys, and seed realistic demo products, warehouses, and operations.
            </p>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-bold">MySQL Host</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                        <div class="form-text">Default for XAMPP is <code>root</code></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="db_pass" class="form-control" value="" placeholder="(empty for XAMPP)">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" name="run_install" class="btn btn-brand">
                        <i class="fa-solid fa-database me-2"></i>Create Database &amp; Seed Data
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
