<?php
/**
 * StockSense Pro - Authentication & Role-Based Access Control (RBAC) Guard
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_current_user_data() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? ROLE_STAFF,
        'avatar' => $_SESSION['user_avatar'] ?? ''
    ];
}

function is_manager() {
    return is_logged_in() && (($_SESSION['user_role'] ?? '') === ROLE_MANAGER);
}

function is_staff() {
    return is_logged_in() && (($_SESSION['user_role'] ?? '') === ROLE_STAFF);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

function require_manager() {
    require_login();
    if (!is_manager()) {
        $_SESSION['flash_error'] = 'Access restricted: Inventory Manager permissions required.';
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

// CSRF Protection Helpers
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash Message Helpers
function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}
