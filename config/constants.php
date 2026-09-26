<?php
/**
 * StockSense Pro - Application Constants & Global Settings
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// App Identity
define('APP_NAME', 'StockSense Pro');
define('APP_TAGLINE', 'Enterprise Inventory & Stock Movement System');
define('APP_VERSION', '1.0.0 (Odoo Hackathon Edition)');

// Localization (India)
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');

// Theme Configuration (Theme 1: Odoo Enterprise Violet & Mint)
define('THEME_PRIMARY', '#714B67');         // Odoo Eggplant Violet
define('THEME_PRIMARY_DARK', '#5B3A53');    // Deep Sidebar Violet
define('THEME_ACCENT', '#00A09D');          // Fresh Mint Emerald
define('THEME_ACCENT_HOVER', '#008784');    // Darker Teal Hover
define('THEME_BG', '#F8F9FA');              // Porcelain Soft White
define('THEME_CARD_BG', '#FFFFFF');         // Pure White Cards

// Dynamic Base URL Resolution
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detect subdirectory path automatically
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$dir_parts = explode('/', trim($script_name, '/'));
$app_subfolder = 'stocksense_pro'; // Default folder name

// If running in htdocs or a folder named stocksense_pro
if (in_array('stocksense_pro', $dir_parts)) {
    $sub_index = array_search('stocksense_pro', $dir_parts);
    $base_path = '/' . implode('/', array_slice($dir_parts, 0, $sub_index + 1));
} else {
    // Top-level or root
    $base_path = '';
}
define('BASE_URL', $protocol . $host . $base_path . '/');

// User Roles
define('ROLE_MANAGER', 'manager');
define('ROLE_STAFF', 'staff');

// Operation Types
define('MOVE_RECEIPT', 'receipt');
define('MOVE_DELIVERY', 'delivery');
define('MOVE_INTERNAL', 'internal');
define('MOVE_ADJUSTMENT', 'adjustment');

// Document Statuses
define('STATUS_DRAFT', 'draft');
define('STATUS_WAITING', 'waiting');
define('STATUS_READY', 'ready');
define('STATUS_DONE', 'done');
define('STATUS_CANCELED', 'canceled');

/**
 * Helper to generate status badge HTML
 */
function get_status_badge($status) {
    $status = strtolower($status);
    switch ($status) {
        case STATUS_DRAFT:
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fa-solid fa-file-pen me-1"></i>Draft</span>';
        case STATUS_WAITING:
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Waiting</span>';
        case STATUS_READY:
            return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="fa-solid fa-box-open me-1"></i>Ready</span>';
        case STATUS_DONE:
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Done</span>';
        case STATUS_CANCELED:
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>Canceled</span>';
        default:
            return '<span class="badge bg-light text-dark px-2 py-1">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}
