<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce secure HTTPS connection
enforce_https();

// Allow only POST requests for form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/auth/register.php');
}

// Verify CSRF token to prevent request forgery
verify_csrf(APP_URL . '/auth/register.php');

// Retrieve and sanitise user input
$full_name        = clean_input(trim($_POST['full_name'] ?? ''));
$email            = clean_input(trim($_POST['email'] ?? ''));
$phone            = clean_input(trim($_POST['phone'] ?? ''));
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Store previous input for user convenience
set_old_input([
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone
]);

// Reset field level validation errors
$_SESSION['field_errors'] = [];

// Validate required fields
if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
    if ($full_name === '') {
        $_SESSION['field_errors']['full_name'] = 'Please enter your full name.';
    }
    if ($email === '') {
        $_SESSION['field_errors']['email'] = 'Please enter a valid email.';
    }
    if ($password === '') {
        $_SESSION['field_errors']['password'] = 'Please enter a password.';
    }
    if ($confirm_password === '') {
        $_SESSION['field_errors']['confirm_password'] = 'Please confirm your password.';
    }
    set_flash('danger', 'Please complete all required fields.');
    redirect(APP_URL . '/auth/register.php');
}

// Validate full name format (letters, spaces, hyphens, apostrophes)
if (!preg_match("/^[A-Za-z]+([ '-][A-Za-z]+)*$/", $full_name)) {
    $_SESSION['field_errors']['full_name'] = "Name can only contain letters, spaces, hyphens (-) and apostrophes (').";
    set_flash('danger', 'Invalid name format.');
    redirect(APP_URL . '/auth/register.php');
}

// Validate full name length
if (mb_strlen($full_name) > 120) {
    $_SESSION['field_errors']['full_name'] = 'Full name must be 120 characters or fewer.';
    set_flash('danger', 'Please shorten your full name and try again.');
    redirect(APP_URL . '/auth/register.php');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['field_errors']['email'] = 'Please enter a valid email.';
    set_flash('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/auth/register.php');
}

// Validate password length
if (strlen($password) < 8) {
    $_SESSION['field_errors']['password'] = 'Password must be at least 8 characters.';
    set_flash('danger', 'Password must be at least 8 characters.');
    redirect(APP_URL . '/auth/register.php');
}

// Check whether both passwords match
if ($password !== $confirm_password) {
    $_SESSION['field_errors']['confirm_password'] = 'Passwords do not match.';
    set_flash('danger', 'Passwords do not match.');
    redirect(APP_URL . '/auth/register.php');
}

// Validate optional phone number format
if ($phone !== '' && !preg_match('/^\+?[0-9\s\-()]{8,20}$/', $phone)) {
    $_SESSION['field_errors']['phone'] = 'Please enter a valid phone number.';
    set_flash('danger', 'Please enter a valid phone number.');
    redirect(APP_URL . '/auth/register.php');
}

try {
    // Check whether email already exists in database
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['field_errors']['email'] = 'Unable to complete registration. Please check your details or sign in.';
        set_flash('danger', 'Unable to complete registration. Please check your details or sign in.');
        redirect(APP_URL . '/auth/register.php');
    }

    // Begin transaction to keep related database changes consistent
    $pdo->beginTransaction();

    // Hash password before storing in database
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user record
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

    // Retrieve newly created user id
    $new_user_id = $pdo->lastInsertId();

    // Record welcome bonus points transaction
    $txn = $pdo->prepare(
        "INSERT INTO points_transactions (user_id, order_id, txn_type, points_delta, note)
         VALUES (?, NULL, 'bonus', ?, 'Welcome bonus for creating an account')"
    );
    $txn->execute([$new_user_id, POINTS_SIGNUP_BONUS]);

    // Commit transaction after all queries succeed
    $pdo->commit();

    // Clear stored input and validation errors
    clear_old_input();
    unset($_SESSION['field_errors']);

    // Regenerate session id for security
    session_regenerate_id(true);

    // Store new user information in session
    $_SESSION['user_id']   = $new_user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['role']      = 'customer';
    $_SESSION['points']    = POINTS_SIGNUP_BONUS;

    // Show success message and redirect to customer dashboard
    set_flash('success', 'Welcome to LazyDrip, ' . $full_name . '! You\'ve earned ' . POINTS_SIGNUP_BONUS . ' bonus points.');
    redirect(APP_URL . '/customer/dashboard.php');

} catch (PDOException $e) {
    // Roll back transaction if database operation fails
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error and return user to register page
    error_log('Register error: ' . $e->getMessage());
    $_SESSION['field_errors']['email'] = 'We could not complete registration right now. Please try again.';
    set_flash('danger', 'Database error. Please try again.');
    redirect(APP_URL . '/auth/register.php');
}