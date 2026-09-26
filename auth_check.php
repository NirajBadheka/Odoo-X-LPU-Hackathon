<?php
/**
 * StockSense - Route Guard
 * Include AFTER session.php/functions.php at the top of every protected page.
 * Usage:
 *   require_once '../includes/auth_check.php';                 // any logged-in user
 *   require_once '../includes/auth_check.php'; requireRole(ROLE_MANAGER); // manager-only page
 */

if (!isLoggedIn()) {
    setFlash('warning', 'Please log in to continue.');
    redirect('auth/login.php');
}

// Re-validate the user is still active on every request (in case an admin disabled them mid-session)
$__guardConn = db();
$__guardStmt = $__guardConn->prepare("SELECT is_active, role_id FROM users WHERE user_id = ?");
$__guardStmt->bind_param('i', $_SESSION['user_id']);
$__guardStmt->execute();
$__guardUser = $__guardStmt->get_result()->fetch_assoc();
$__guardStmt->close();

if (!$__guardUser || (int)$__guardUser['is_active'] !== 1) {
    session_unset();
    session_destroy();
    setFlash('danger', 'Your account has been deactivated. Contact your administrator.');
    redirect('auth/login.php');
}

function requireRole(int ...$allowedRoleIds): void
{
    if (!in_array(currentRoleId(), $allowedRoleIds, true)) {
        http_response_code(403);
        setFlash('danger', 'You do not have permission to access that page.');
        redirect('403.php');
    }
}
