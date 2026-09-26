<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/register.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('auth/register.php');
}

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$roleId = (int)($_POST['role_id'] ?? 0);
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']);

$_SESSION['old_input'] = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone, 'role_id' => $roleId];

$errors = [];

if (strlen($fullName) < 3) $errors[] = 'Full name must be at least 3 characters.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) $errors[] = 'Please enter a valid 10-digit Indian mobile number.';
if (!in_array($roleId, [ROLE_MANAGER, ROLE_STAFF], true)) $errors[] = 'Please select a valid role.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) $errors[] = 'Password must include at least one uppercase letter and one number.';
if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
if (!$terms) $errors[] = 'You must agree to the Terms of Service.';

if (!empty($errors)) {
    setFlash('danger', implode(' ', $errors));
    redirect('auth/register.php');
}

$conn = db();

// Check duplicate email
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    setFlash('danger', 'An account with this email already exists. Please sign in instead.');
    redirect('auth/register.php');
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone, role_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('ssssi', $fullName, $email, $passwordHash, $phone, $roleId);

if ($stmt->execute()) {
    $newUserId = $stmt->insert_id;
    $stmt->close();
    unset($_SESSION['old_input']);

    logActivity($conn, $newUserId, 'New account registered');

    // Auto-login after successful registration
    session_regenerate_id(true);
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['last_regen'] = time();

    setFlash('success', 'Account created successfully! Welcome to StockSense, ' . explode(' ', $fullName)[0] . '.');
    redirect('dashboard/index.php');
} else {
    $stmt->close();
    error_log('Registration insert failed: ' . $conn->error);
    setFlash('danger', 'Something went wrong while creating your account. Please try again.');
    redirect('auth/register.php');
}
