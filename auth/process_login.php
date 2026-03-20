<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/auth/login.php');
}

// CSRF protection
verify_csrf(APP_URL . '/auth/login.php');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Save old input using helper
set_old_input([
    'email' => $email
]);

// Validation
if ($email === '' || $password === '') {
    set_flash('danger', 'Email and password are required.');
    redirect(APP_URL . '/auth/login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/auth/login.php');
}

try {
    $stmt = $pdo->prepare("
        SELECT user_id, full_name, role, password_hash
        FROM users
        WHERE email = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        clear_old_input();

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            redirect(APP_URL . '/admin/dashboard.php');
        } else {
            redirect(APP_URL . '/customer/dashboard.php');
        }
    } else {
        set_flash('danger', 'Invalid email or password.');
        redirect(APP_URL . '/auth/login.php');
    }

} catch (PDOException $e) {
    set_flash('danger', 'Database error.');
    redirect(APP_URL . '/auth/login.php');
}