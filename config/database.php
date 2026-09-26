<?php
/**
 * StockSense Pro - Database Configuration & Connection
 * Uses MySQLi with Prepared Statements for high security & performance.
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pro_stocksense');
define('DB_PORT', 3306);

// Establish MySQLi Connection
try {
    // Suppress default fatal error reporting to handle gracefully
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        // If database does not exist, provide auto-redirect or clean error
        $db_connected = false;
        $db_error = $conn->connect_error;
    } else {
        $db_connected = true;
        $conn->set_charset("utf8mb4");
    }
} catch (Exception $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}

/**
 * Helper function to safely execute prepared SELECT queries
 * Returns array of associative rows
 */
function db_select($sql, $params = [], $types = "") {
    global $conn, $db_connected;
    if (!$db_connected || !$conn) return [];

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $stmt->close();
    return $data;
}

/**
 * Helper function to safely fetch a single row
 */
function db_select_one($sql, $params = [], $types = "") {
    $rows = db_select($sql, $params, $types);
    return !empty($rows) ? $rows[0] : null;
}

/**
 * Helper function to execute prepared INSERT/UPDATE/DELETE queries
 * Returns [bool success, int insert_id, int affected_rows, string error]
 */
function db_query($sql, $params = [], $types = "") {
    global $conn, $db_connected;
    if (!$db_connected || !$conn) {
        return ['success' => false, 'insert_id' => 0, 'affected_rows' => 0, 'error' => 'Database not connected'];
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'insert_id' => 0, 'affected_rows' => 0, 'error' => $conn->error];
    }

    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        $stmt->bind_param($types, ...$params);
    }

    $success = $stmt->execute();
    $insert_id = $stmt->insert_id;
    $affected_rows = $stmt->affected_rows;
    $error = $stmt->error;
    $stmt->close();

    return [
        'success' => $success,
        'insert_id' => $insert_id,
        'affected_rows' => $affected_rows,
        'error' => $error
    ];
}

/**
 * Log an immutable inventory move into the Stock Ledger
 */
function record_stock_move($move_type, $reference_doc, $product_id, $source_location_id, $destination_location_id, $quantity, $user_id, $notes = "") {
    $sql = "INSERT INTO stock_moves (move_type, reference_doc, product_id, source_location_id, destination_location_id, quantity, user_id, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    return db_query($sql, [
        $move_type,
        $reference_doc,
        $product_id,
        $source_location_id,
        $destination_location_id,
        $quantity,
        $user_id,
        $notes
    ], "ssiiidis");
}

/**
 * Adjust stock level in a specific location (increments or decrements)
 */
function update_location_stock($product_id, $location_id, $quantity_delta) {
    global $conn;
    // Check if record exists
    $existing = db_select_one("SELECT id, quantity FROM stock_levels WHERE product_id = ? AND location_id = ?", [$product_id, $location_id], "ii");

    if ($existing) {
        $new_qty = $existing['quantity'] + $quantity_delta;
        if ($new_qty < 0) $new_qty = 0; // Safeguard against negative balances
        return db_query("UPDATE stock_levels SET quantity = ?, last_updated = NOW() WHERE id = ?", [$new_qty, $existing['id']], "di");
    } else {
        $initial_qty = max(0, $quantity_delta);
        return db_query("INSERT INTO stock_levels (product_id, location_id, quantity, last_updated) VALUES (?, ?, ?, NOW())", [$product_id, $location_id, $initial_qty], "iid");
    }
}
