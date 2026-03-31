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
    redirect(APP_URL . '/auth/login.php');
}

// Verify CSRF token to prevent request forgery
verify_csrf(APP_URL . '/auth/login.php');

// Retrieve and sanitise user input
$email = clean_input(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

// Store previous input for user convenience
set_old_input([
    'email' => $email
]);

// Reset field level validation errors
$_SESSION['field_errors'] = [];

// Validate required fields
if ($email === '' || $password === '') {
    if ($email === '') {
        $_SESSION['field_errors']['email'] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $_SESSION['field_errors']['password'] = 'Please enter your password.';
    }
    set_flash('danger', 'Email and password are required.');
    redirect(APP_URL . '/auth/login.php');
}

// Validate input length
if (mb_strlen($email) > 254) {
    $_SESSION['field_errors']['email'] = 'Please enter a valid email address.';
    set_flash('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/auth/login.php');
}

if (mb_strlen($password) > 255) {
    $_SESSION['field_errors']['password'] = 'Password is too long.';
    set_flash('danger', 'Password is too long.');
    redirect(APP_URL . '/auth/login.php');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['field_errors']['email'] = 'Please enter a valid email address.';
    set_flash('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/auth/login.php');
}

// Capture user IP address for login attempt tracking
$ip_address = substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
$attempt_state = null;
$rate_limit_available = true;

try {
    // Check previous login attempts for rate limiting
    $attemptStmt = $pdo->prepare(
        "SELECT attempts, locked_until
         FROM login_attempts
         WHERE email = ? AND ip_address = ?
         LIMIT 1"
    );
    $attemptStmt->execute([$email, $ip_address]);
    $attempt_state = $attemptStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($attempt_state) {
        $lockedUntil = $attempt_state['locked_until'] ?? null;
        $lockedUntilTs = $lockedUntil ? strtotime((string) $lockedUntil) : false;

        // Block login if account is temporarily locked
        if ($lockedUntilTs !== false && $lockedUntilTs > time()) {
            $_SESSION['field_errors']['email'] = 'Too many failed login attempts. Please try again later.';
            $_SESSION['field_errors']['password'] = 'Too many failed login attempts. Please try again later.';
            set_flash('danger', 'Too many failed login attempts. Please try again later.');
            redirect(APP_URL . '/auth/login.php');
        }

        // Reset attempts if lock period has expired
        if ($lockedUntilTs !== false && $lockedUntilTs <= time()) {
            $resetStmt = $pdo->prepare(
                "UPDATE login_attempts
                 SET attempts = 0, locked_until = NULL
                 WHERE email = ? AND ip_address = ?"
            );
            $resetStmt->execute([$email, $ip_address]);
            $attempt_state['attempts'] = 0;
            $attempt_state['locked_until'] = null;
        }
    }
} catch (PDOException $e) {
    // Disable rate limiting if database lookup fails
    $rate_limit_available = false;
    error_log('Login rate limit lookup failed: ' . $e->getMessage());
}

try {
    // Retrieve user record from database
    $stmt = $pdo->prepare("
        SELECT user_id, full_name, role, password_hash
        FROM users
        WHERE email = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password and authenticate user
    if ($user && password_verify($password, $user['password_hash'])) {

        // Rehash password if needed for security upgrade
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $rehashStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ? LIMIT 1");
            $rehashStmt->execute([$new_hash, $user['user_id']]);
        }

        // Clear login attempts after successful login
        if ($rate_limit_available) {
            $clearAttemptsStmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? AND ip_address = ?");
            $clearAttemptsStmt->execute([$email, $ip_address]);
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Clear stored input and errors
        clear_old_input();
        unset($_SESSION['field_errors']);

        // Store user details in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        // Redirect user based on role
        if ($user['role'] === 'admin') {
            redirect(APP_URL . '/admin/dashboard.php');
        } else {
            redirect(APP_URL . '/customer/dashboard.php');
        }

    } else {
        // Increment failed login attempts
        if ($rate_limit_available) {
            $attempts = ((int) ($attempt_state['attempts'] ?? 0)) + 1;
            $locked_until = null;

            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $locked_until = (new DateTimeImmutable('+' . LOGIN_LOCKOUT_MINUTES . ' minutes'))->format('Y-m-d H:i:s');
            }

            $saveAttemptsStmt = $pdo->prepare(
                "INSERT INTO login_attempts (email, ip_address, attempts, last_attempt, locked_until)
                 VALUES (?, ?, ?, NOW(), ?)
                 ON DUPLICATE KEY UPDATE
                    attempts = VALUES(attempts),
                    last_attempt = VALUES(last_attempt),
                    locked_until = VALUES(locked_until)"
            );
            $saveAttemptsStmt->execute([$email, $ip_address, $attempts, $locked_until]);
        }

        // Display error message for invalid credentials
        $_SESSION['field_errors']['email'] = 'Invalid email or password.';
        $_SESSION['field_errors']['password'] = 'Invalid email or password.';
        set_flash('danger', 'Invalid email or password.');
        redirect(APP_URL . '/auth/login.php');
    }

} catch (PDOException $e) {
    // Handle database errors
    $_SESSION['field_errors']['email'] = 'We could not sign you in right now. Please try again.';
    set_flash('danger', 'Database error.');
    redirect(APP_URL . '/auth/login.php');
}