<?php
// =============================================================
// includes/functions.php
// Reusable helper functions for LazyDrip.
// Included by header.php so it is available on every page.
// =============================================================

// -------------------------------------------------------------
// OUTPUT SANITIZATION
// Always use this before echoing any user-supplied value.
// Prevents XSS (Cross-Site Scripting) attacks.
// -------------------------------------------------------------
function ensure_session_started(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// -------------------------------------------------------------
// FLASH MESSAGES
// Usage (set):    set_flash('success', 'Account created!');
// Usage (show):   show_flash();   <- call inside the page body
// Messages are stored in $_SESSION and shown once, then cleared.
// -------------------------------------------------------------
function set_flash(string $type, string $message): void {
    ensure_session_started();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];   // 'success' | 'danger' | 'warning' | 'info'
        $msg  = e($_SESSION['flash']['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\">
                {$msg}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
              </div>";
        unset($_SESSION['flash']);
    }
}

function set_old_input(array $data): void {
    ensure_session_started();
    $_SESSION['old_input'] = $data;
}

function old_input(string $key, string $default = ''): string {
    ensure_session_started();
    return $_SESSION['old_input'][$key] ?? $default;
}

function clear_old_input(): void {
    ensure_session_started();
    unset($_SESSION['old_input']);
}

// -------------------------------------------------------------
// REDIRECT HELPER
// Usage: redirect('/lazydrip/auth/login.php');
// Stops script execution after redirecting.
// -------------------------------------------------------------
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// -------------------------------------------------------------
// AUTH CHECKS
// Use these at the top of any page that requires login.
// -------------------------------------------------------------
function enforce_session_idle_timeout(): void {
    ensure_session_started();
    if (empty($_SESSION['user_id'])) {
        return;
    }

    $now = time();
    $lastActivity = (int)($_SESSION['last_activity_at'] ?? 0);
    $idleLimit = defined('SESSION_IDLE_TIMEOUT_SECONDS') ? SESSION_IDLE_TIMEOUT_SECONDS : 1800;

    if ($lastActivity > 0 && ($now - $lastActivity) > $idleLimit) {
        $_SESSION = [];
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
        session_destroy();
        session_start();
        set_flash('warning', 'Your session expired due to inactivity. Please log in again.');
        redirect(APP_URL . '/auth/login.php');
    }

    $_SESSION['last_activity_at'] = $now;
}

function require_login(): void {
    enforce_session_idle_timeout();
    if (empty($_SESSION['user_id'])) {
        set_flash('warning', 'Please log in to continue.');
        redirect(APP_URL . '/auth/login.php');
    }
}

function require_admin(): void {
    enforce_session_idle_timeout();
    if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
        set_flash('danger', 'Access denied.');
        redirect(APP_URL . '/auth/login.php');
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect_if_logged_in(): void {
    enforce_session_idle_timeout();
    if (is_logged_in()) {
        if (is_admin()) {
            redirect(APP_URL . '/admin/dashboard.php');
        }
        redirect(APP_URL . '/customer/dashboard.php');
    }
}

// -------------------------------------------------------------
// CART HELPERS (session-based cart)
// Cart structure in $_SESSION['cart']:
//   [ item_id => ['name'=>.., 'price'=>.., 'qty'=>..], ... ]
// -------------------------------------------------------------
function get_cart(): array {
    return $_SESSION['cart'] ?? [];
}

function cart_total(): float {
    $total = 0.0;

    foreach (get_cart() as $item) {
        $rawPrice = $item['price'] ?? 0;
        $rawQty   = $item['qty'] ?? 0;

        if (is_string($rawPrice)) {
            $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
        }

        $price = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
        $qty   = is_numeric($rawQty) ? (int)$rawQty : 0;

        if ($qty > 0) {
            $total += $price * $qty;
        }
    }

    return $total;
}

function cart_count(): int {
    $count = 0;

    foreach (get_cart() as $item) {
        $rawQty = $item['qty'] ?? 0;
        $qty = is_numeric($rawQty) ? (int)$rawQty : 0;

        if ($qty > 0) {
            $count += $qty;
        }
    }

    return $count;
}

// -------------------------------------------------------------
// FORMATTING HELPERS
// -------------------------------------------------------------
/**
 * Formats a price for currency display.
 * IMPORTANT: Use only in visual contexts with labels.
 * Do NOT use in alt text or form field values without context.
 * 
 * @param  float  $amount  The price to format
 * @return string  Formatted as "$XX.XX"
 */
function format_price(float $amount): string {
    return '$' . number_format($amount, 2);
}

function format_date(string $datetime): string {
    return date('d M Y, h:i A', strtotime($datetime));
}

// -------------------------------------------------------------
// INPUT SANITIZATION
// Trims and strips tags from a submitted string.
// -------------------------------------------------------------
function clean_input(string $value): string {
    return strip_tags(trim($value));
}

// =============================================================
// CSRF PROTECTION HELPERS
// =============================================================

/**
 * Returns the current session CSRF token, creating one if needed.
 * Usage:  $token = csrf_token();
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Echoes a hidden <input> containing the CSRF token.
 * Drop  <?php csrf_field(); ?>  inside any POST <form>.
 */
function csrf_field(): void {
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validates the CSRF token submitted with a POST request.
 * Call at the top of every POST handler, before touching any data.
 * On failure: sets a danger flash and redirects to $fallback_url.
 *
 * Usage: verify_csrf(APP_URL . '/contact.php');
 */
function verify_csrf(string $fallback_url = ''): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $submitted)) {
        set_flash('danger', 'Invalid request. Please refresh the page and try again.');
        $redirect = $fallback_url !== '' ? $fallback_url : (APP_URL . '/index.php');
        redirect($redirect);
    }
}

// =============================================================
// REWARDS / POINTS HELPERS
// =============================================================

/**
 * Award earned points to a user after a successful order.
 *
 * Calculates floor(order_total * POINTS_PER_DOLLAR), adds it to
 * users.points, and logs a row in points_transactions.
 *
 * Wrapped in its own try/catch so a transaction-logging failure
 * never rolls back the order itself — the order is already
 * committed when this is called.
 *
 * Usage: award_points($pdo, $userId, $orderId, $finalAmount);
 *
 * @param PDO   $pdo         Active PDO connection
 * @param int   $userId      Authenticated user's ID
 * @param int   $orderId     Newly created order ID
 * @param float $orderTotal  Post-discount amount paid
 */
function award_points(PDO $pdo, int $userId, int $orderId, float $orderTotal): void {
    $earned = (int)floor($orderTotal * POINTS_PER_DOLLAR);
    if ($earned <= 0) {
        return; // Nothing to award (e.g. 100% discount edge case)
    }
    try {
        // Atomic increment — avoids race conditions
        $stmt = $pdo->prepare(
            "UPDATE users SET points = points + ? WHERE user_id = ?"
        );
        $stmt->execute([$earned, $userId]);

        $log = $pdo->prepare("
            INSERT INTO points_transactions
                (user_id, order_id, txn_type, points_delta, note)
            VALUES (?, ?, 'earn', ?, ?)
        ");
        $log->execute([
            $userId,
            $orderId,
            $earned,
            'Earned on order #' . $orderId,
        ]);
    } catch (PDOException $e) {
        // Non-fatal: log the error but do not surface it to the user
        error_log('award_points failed (user=' . $userId . ', order=' . $orderId . '): ' . $e->getMessage());
    }
}

/**
 * Deduct POINTS_REDEEM_AMOUNT points from a user and log the redemption.
 *
 * The UPDATE uses a WHERE clause that prevents the balance going
 * negative — if the user somehow does not have enough points
 * the update affects 0 rows and the function returns false.
 *
 * Call this INSIDE the same DB transaction as the order INSERT,
 * before commit, so it rolls back automatically on failure.
 *
 * @return bool  true on success, false if points were insufficient
 */
function redeem_points(PDO $pdo, int $userId, int $orderId): bool {
    $stmt = $pdo->prepare("
        UPDATE users
        SET    points = points - ?
        WHERE  user_id = ?
        AND    points  >= ?
    ");
    $stmt->execute([POINTS_REDEEM_AMOUNT, $userId, POINTS_REDEEM_AMOUNT]);

    if ($stmt->rowCount() === 0) {
        return false; // Not enough points — rollback will happen in caller
    }

    $log = $pdo->prepare("
        INSERT INTO points_transactions
            (user_id, order_id, txn_type, points_delta, note)
        VALUES (?, ?, 'redeem', ?, ?)
    ");
    $log->execute([
        $userId,
        $orderId,
        -POINTS_REDEEM_AMOUNT,
        'Redeemed on order #' . $orderId,
    ]);

    return true;
}
