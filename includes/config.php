<?php
// =============================================================
// includes/config.php
// Central configuration file for LazyDrip.
// Include this file first in every page via db.php (which
// already requires it), so constants are always available.
// =============================================================

// --- .env bootstrap (for shared-host deployments) -----------
// PHP does not automatically read .env files. This lightweight
// loader populates getenv() values if server env vars are missing.
$envPath = dirname(__DIR__) . '/.env';
if (is_readable($envPath)) {
	$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines !== false) {
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '#') {
				continue;
			}

			$parts = explode('=', $line, 2);
			if (count($parts) !== 2) {
				continue;
			}

			$key = trim($parts[0]);
			$value = trim($parts[1]);
			$value = trim($value, "\"'");

			if ($key === '' || getenv($key) !== false) {
				continue;
			}

			putenv($key . '=' . $value);
			$_ENV[$key] = $value;
			$_SERVER[$key] = $value;
		}
	}
}

// --- Database credentials -----------------------------------
// Read secrets from environment variables first.
// Local defaults keep development simple without committing passwords.
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME') ?: 'lazydrip');
define('DB_USER',    getenv('DB_USER') ?: 'root');
define('DB_PASS',    getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// --- Application constants ----------------------------------
define('APP_NAME',        'LazyDrip');
$appUrlFromEnv = trim((string)(getenv('APP_URL') ?: ''));
if ($appUrlFromEnv !== '') {
	define('APP_URL', rtrim($appUrlFromEnv, '/')); // No trailing slash
} else {
	$appScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$rawHost = (string)($_SERVER['HTTP_HOST'] ?? 'localhost:8080');
	$appHost = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $rawHost) ?: 'localhost:8080';
	define('APP_URL', $appScheme . '://' . $appHost); // No trailing slash
}
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('DEFAULT_IMG',     APP_URL . '/assets/images/placeholder.png');

// --- Loyalty points rules -----------------------------------
define('POINTS_SIGNUP_BONUS',   10);   // Points awarded on registration
define('POINTS_PER_DOLLAR',      1);   // 1 point per $1 spent
define('POINTS_REDEEM_AMOUNT',  50);   // Points needed to redeem
define('POINTS_REDEEM_VALUE',    5.00);// Dollar value of redemption

<<<<<<< Updated upstream
=======
// --- Authentication security rules ---------------------------
define('MAX_LOGIN_ATTEMPTS',     5);   // Failed attempts before temporary lockout
define('LOGIN_LOCKOUT_MINUTES', 15);   // Account+IP lockout duration
define('FORCE_HTTPS', filter_var(getenv('FORCE_HTTPS') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('SESSION_IDLE_TIMEOUT_SECONDS', (int)(getenv('SESSION_IDLE_TIMEOUT_SECONDS') ?: 1800));

>>>>>>> Stashed changes
// --- Session cookie settings (more secure) ------------------
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600);
