<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/auth/register.php');
}

// CSRF protection
verify_csrf(APP_URL . '/auth/register.php');

$full_name        = clean_input(trim($_POST['full_name'] ?? ''));
$email            = clean_input(trim($_POST['email'] ?? ''));
$phone            = clean_input(trim($_POST['phone'] ?? ''));
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Save old input using helper
set_old_input([
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone
]);

if ($full_name === '' || $email === '' || $password === '') {
    set_flash('danger', 'Name, email and password are required.');
    redirect(APP_URL . '/auth/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/auth/register.php');
}

if (strlen($password) < 8) {
    set_flash('danger', 'Password must be at least 8 characters.');
    redirect(APP_URL . '/auth/register.php');
}

if ($password !== $confirm_password) {
    set_flash('danger', 'Passwords do not match.');
    redirect(APP_URL . '/auth/register.php');
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        set_flash('danger', 'An account with this email already exists.');
        redirect(APP_URL . '/auth/register.php');
    }

    $pdo->beginTransaction();

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, phone, role, points, is_active)
         VALUES (?, ?, ?, ?, 'customer', ?, 1)"
    );
    $stmt->execute([
        $full_name,
        $email,
        $password_hash,
        $phone ?: null,
        POINTS_SIGNUP_BONUS
    ]);

    $new_user_id = $pdo->lastInsertId();

    $txn = $pdo->prepare(
        "INSERT INTO points_transactions (user_id, order_id, txn_type, points_delta, note)
         VALUES (?, NULL, 'bonus', ?, 'Welcome bonus for creating an account')"
    );
    $txn->execute([$new_user_id, POINTS_SIGNUP_BONUS]);

    $pdo->commit();

    clear_old_input();

    session_regenerate_id(true);
    $_SESSION['user_id']   = $new_user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['role']      = 'customer';
    $_SESSION['points']    = POINTS_SIGNUP_BONUS;

    set_flash('success', 'Welcome to LazyDrip, ' . $full_name . '! You\'ve earned ' . POINTS_SIGNUP_BONUS . ' bonus points.');
    redirect(APP_URL . '/customer/dashboard.php');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Register error: ' . $e->getMessage());
    set_flash('danger', 'Database error. Please try again.');
    redirect(APP_URL . '/auth/register.php');
}