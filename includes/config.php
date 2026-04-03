<?php
// includes/config.php
// Central configuration file for LazyDrip.
// Include this file first in every page via db.php (which
// already requires it), so constants are always available.

// Database credentials 
define('DB_HOST', 'localhost');
define('DB_NAME', 'lazydrip');
define('DB_USER', 'lazydrip-sqldev');      
define('DB_PASS', 'Admin@123!');          
define('DB_CHARSET', 'utf8mb4');

// Application constants 
define('APP_NAME', 'LazyDrip');
$appScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$appHost   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
define('APP_URL', $appScheme . '://' . $appHost); // No trailing slash
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('DEFAULT_IMG', APP_URL . '/assets/images/placeholder.png');

// Loyalty points rules
define('POINTS_SIGNUP_BONUS', 10);   // Points awarded on registration
define('POINTS_PER_DOLLAR', 1);   // 1 point per $1 spent
define('POINTS_REDEEM_AMOUNT', 50);   // Points needed to redeem
define('POINTS_REDEEM_VALUE', 5.00);// Dollar value of redemption

// Authentication security rules
define('MAX_LOGIN_ATTEMPTS', 5);   // Failed attempts before temporary lockout
define('LOGIN_LOCKOUT_MINUTES', 15);   // Account+IP lockout duration
define('FORCE_HTTPS', filter_var(getenv('FORCE_HTTPS') ?: '0', FILTER_VALIDATE_BOOLEAN));

// Session cookie settings (more secure)
// Respect reverse proxies that terminate TLS.
$httpsDetected = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
);

ini_set('session.cookie_secure', $httpsDetected ? '1' : '0');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
