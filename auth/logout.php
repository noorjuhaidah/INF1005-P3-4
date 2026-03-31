<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Remove session cookie if cookies are used
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session to log the user out
session_destroy();

// Start a new session to store flash message
session_start();

// Set logout success message
set_flash('success', 'You have been logged out. See you next time!');

// Redirect user to homepage
redirect(APP_URL . '/index.php');