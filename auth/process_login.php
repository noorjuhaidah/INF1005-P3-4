<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    set_flash('danger', 'Email and password are required.');
    redirect(APP_URL . '/auth/login.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
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