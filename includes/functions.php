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
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];   // 'success' | 'danger' | 'warning' | 'info'
        $msg  = e($_SESSION['flash']['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
                {$msg}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
              </div>";
        unset($_SESSION['flash']);
    }
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
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        set_flash('warning', 'Please log in to continue.');
        redirect(APP_URL . '/auth/login.php');
    }
}

function require_admin(): void {
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

        // Handle values like "$6.00" safely
        if (is_string($rawPrice)) {
            $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
        }

        $price = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
        $qty   = is_numeric($rawQty)   ? (int)$rawQty     : 0;

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
        if ($qty > 0) $count += $qty;
    }
    return $count;
}


// -------------------------------------------------------------
// FORMATTING HELPERS
// -------------------------------------------------------------
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
