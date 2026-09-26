<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth_check.php';

// Unset all session variables
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start fresh session for flash message
session_start();
set_flash('success', 'You have been safely logged out. See you next time!');
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
