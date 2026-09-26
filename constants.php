<?php
/**
 * StockSense - Application Constants
 */

// Base URL - update if deployed in a subfolder under htdocs
define('BASE_URL', '/stocksense/');

define('APP_NAME', 'StockSense');
define('APP_TAGLINE', 'Real-Time Inventory Management System');

// Roles
define('ROLE_MANAGER', 1);
define('ROLE_STAFF', 2);

// Operation types
define('OP_RECEIPT', 'receipt');
define('OP_DELIVERY', 'delivery');
define('OP_INTERNAL', 'internal');
define('OP_ADJUSTMENT', 'adjustment');

// Operation statuses (workflow order)
define('STATUS_DRAFT', 'draft');
define('STATUS_WAITING', 'waiting');
define('STATUS_READY', 'ready');
define('STATUS_DONE', 'done');
define('STATUS_CANCELED', 'canceled');

// Upload constraints
define('UPLOAD_DIR', __DIR__ . '/../uploads/product_images/');
define('UPLOAD_URL', BASE_URL . 'uploads/product_images/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// OTP
define('OTP_LENGTH', 6);
define('OTP_VALID_MINUTES', 10);

// Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
