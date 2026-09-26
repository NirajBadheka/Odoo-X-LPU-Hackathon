<?php
/**
 * StockSense - Shared Helper Functions
 */

/** Sanitize a string for safe output (defense-in-depth alongside prepared statements) */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect helper */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/** Check if user is logged in */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Get logged-in user's role id */
function currentRoleId(): ?int
{
    return $_SESSION['role_id'] ?? null;
}

function isManager(): bool
{
    return currentRoleId() === ROLE_MANAGER;
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/** CSRF token generation + validation */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Flash messages (session-based, one-time read) via SweetAlert2 on next page load */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** JSON response helper for AJAX/API endpoints */
function jsonResponse(bool $success, string $message = '', array $data = [], int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

/** Format currency as Indian Rupees */
function inr(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

/** Format a datetime nicely */
function formatDate(?string $datetime, string $format = 'd M Y, h:i A'): string
{
    if (empty($datetime)) return '—';
    return date($format, strtotime($datetime));
}

/** Generate the next reference number for an operation type e.g. RCPT-1005 */
function nextReferenceNo(mysqli $conn, string $type): string
{
    $prefixMap = [
        OP_RECEIPT => 'RCPT',
        OP_DELIVERY => 'DEL',
        OP_INTERNAL => 'INT',
        OP_ADJUSTMENT => 'ADJ',
    ];
    $prefix = $prefixMap[$type] ?? 'OP';

    $stmt = $conn->prepare("SELECT reference_no FROM stock_operations WHERE reference_no LIKE CONCAT(?, '-%') ORDER BY operation_id DESC LIMIT 1");
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $last = $result->fetch_assoc();
    $stmt->close();

    $nextNum = 1001;
    if ($last) {
        $parts = explode('-', $last['reference_no']);
        $nextNum = (int)end($parts) + 1;
    }
    return $prefix . '-' . $nextNum;
}

/** Auto-generate a SKU suggestion from category + name */
function suggestSku(string $categoryPrefix, int $seqNumber): string
{
    return strtoupper($categoryPrefix) . '-' . str_pad((string)$seqNumber, 3, '0', STR_PAD_LEFT);
}

/** Status badge HTML (Bootstrap 5 pill) */
function statusBadge(string $status): string
{
    $map = [
        'draft'     => ['secondary', 'Draft'],
        'waiting'   => ['warning text-dark', 'Waiting'],
        'ready'     => ['info text-dark', 'Ready'],
        'done'      => ['success', 'Done'],
        'canceled'  => ['danger', 'Canceled'],
    ];
    [$class, $label] = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge bg-' . $class . ' status-badge">' . e($label) . '</span>';
}

function operationTypeLabel(string $type): string
{
    $map = [
        'receipt' => 'Receipt',
        'delivery' => 'Delivery Order',
        'internal' => 'Internal Transfer',
        'adjustment' => 'Stock Adjustment',
    ];
    return $map[$type] ?? ucfirst($type);
}

/** Log an activity for audit trail */
function logActivity(mysqli $conn, ?int $userId, string $action): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $action, $ip);
    $stmt->execute();
    $stmt->close();
}

/** Push a notification to a user */
function pushNotification(mysqli $conn, int $userId, string $message, string $type = 'general'): void
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $message, $type);
    $stmt->execute();
    $stmt->close();
}

/** Notify all managers (e.g. low stock, pending approvals) */
function notifyAllManagers(mysqli $conn, string $message, string $type = 'general'): void
{
    $result = $conn->query("SELECT user_id FROM users WHERE role_id = " . ROLE_MANAGER . " AND is_active = 1");
    while ($row = $result->fetch_assoc()) {
        pushNotification($conn, (int)$row['user_id'], $message, $type);
    }
}

/** Get current total stock quantity for a product across all locations */
function getProductTotalStock(mysqli $conn, int $productId): float
{
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM product_stock WHERE product_id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return (float)$total;
}

/**
 * Adjust stock at a location by a delta (positive or negative), creating the
 * product_stock row if it doesn't exist yet. Returns the new quantity.
 */
function adjustLocationStock(mysqli $conn, int $productId, int $locationId, float $delta): float
{
    $stmt = $conn->prepare("SELECT stock_id, quantity FROM product_stock WHERE product_id = ? AND location_id = ? FOR UPDATE");
    $stmt->bind_param('ii', $productId, $locationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $newQty = max(0, (float)$row['quantity'] + $delta);
        $upd = $conn->prepare("UPDATE product_stock SET quantity = ? WHERE stock_id = ?");
        $upd->bind_param('di', $newQty, $row['stock_id']);
        $upd->execute();
        $upd->close();
    } else {
        $newQty = max(0, $delta);
        $ins = $conn->prepare("INSERT INTO product_stock (product_id, location_id, quantity) VALUES (?, ?, ?)");
        $ins->bind_param('iid', $productId, $locationId, $newQty);
        $ins->execute();
        $ins->close();
    }
    return $newQty;
}

/** Time-ago style helper for activity feeds */
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
