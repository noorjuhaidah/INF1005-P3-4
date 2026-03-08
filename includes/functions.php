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
// Usage (show):   show_flash();   ← call inside the page body
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
        $total += $item['price'] * $item['qty'];
    }
    return $total;
}

function cart_count(): int {
    $count = 0;
    foreach (get_cart() as $item) {
        $count += $item['qty'];
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
