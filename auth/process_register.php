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

// Reset field-level errors for this request cycle.
$_SESSION['field_errors'] = [];

if ($full_name === '' || $email === '' || $password === '') {
    if ($full_name === '') {
        $_SESSION['field_errors']['full_name'] = 'Please enter your full name.';
    }
    if ($email === '') {
        $_SESSION['field_errors']['email'] = 'Please enter a valid email.';
    }
    if ($password === '') {
        $_SESSION['field_errors']['password'] = 'Please enter a password.';
    }
    set_flash('danger', 'Name, email and password are required.');
    redirect(APP_URL . '/auth/register.php');
}

if (mb_strlen($full_name) > 120) {
    $_SESSION['field_errors']['full_name'] = 'Full name must be 120 characters or fewer.';
    set_flash('danger', 'Please shorten your full name and try again.');
    redirect(APP_URL . '/auth/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['field_errors']['email'] = 'Please enter a valid email.';
    set_flash('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/auth/register.php');
}

if (strlen($password) < 8) {
    $_SESSION['field_errors']['password'] = 'Password must be at least 8 characters.';
    set_flash('danger', 'Password must be at least 8 characters.');
    redirect(APP_URL . '/auth/register.php');
}

if ($password !== $confirm_password) {
    $_SESSION['field_errors']['confirm_password'] = 'Passwords do not match.';
    set_flash('danger', 'Passwords do not match.');
    redirect(APP_URL . '/auth/register.php');
}

if ($phone !== '' && !preg_match('/^\+?[0-9\s\-()]{8,20}$/', $phone)) {
    $_SESSION['field_errors']['phone'] = 'Please enter a valid phone number.';
    set_flash('danger', 'Please enter a valid phone number.');
    redirect(APP_URL . '/auth/register.php');
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['field_errors']['email'] = 'Unable to complete registration. Please check your details or sign in.';
        set_flash('danger', 'Unable to complete registration. Please check your details or sign in.');
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
    unset($_SESSION['field_errors']);

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
    $_SESSION['field_errors']['email'] = 'We could not complete registration right now. Please try again.';
    set_flash('danger', 'Database error. Please try again.');
    redirect(APP_URL . '/auth/register.php');
}