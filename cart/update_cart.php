<?php
// =============================================================
// cart/update_cart.php — Update or Remove Cart Items
// Accepts POST from cart.php (update qty or remove item).
// Responds with JSON (AJAX) or redirects (plain POST fallback).
// =============================================================

require_once __DIR__ . '/../includes/header.php';

function json_response(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Must be POST and logged in
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) json_response(false, 'Invalid request.');
    redirect(APP_URL . '/cart/cart.php');
}

if (!is_logged_in()) {
    if ($is_ajax) json_response(false, 'Please log in.');
    redirect(APP_URL . '/auth/login.php');
}

// CSRF check
$submitted_token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    if ($is_ajax) json_response(false, 'Invalid request. Please refresh and try again.');
    set_flash('danger', 'Invalid request.');
    redirect(APP_URL . '/cart/cart.php');
}

// Validate inputs
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$action  = clean_input($_POST['action'] ?? '');

if (!$item_id || !in_array($action, ['update', 'remove'])) {
    if ($is_ajax) json_response(false, 'Invalid request data.');
    redirect(APP_URL . '/cart/cart.php');
}

// Initialise cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// -------------------------------------------------------------
// REMOVE action
// -------------------------------------------------------------
if ($action === 'remove') {
    unset($_SESSION['cart'][$item_id]);

    $cart_count = cart_count();
    $cart_total = cart_total();

    if ($is_ajax) {
        json_response(true, 'Item removed.', [
            'cart_count' => $cart_count,
            'cart_total' => number_format($cart_total, 2),
        ]);
    }
    set_flash('success', 'Item removed from cart.');
    redirect(APP_URL . '/cart/cart.php');
}

// -------------------------------------------------------------
// UPDATE action
// -------------------------------------------------------------
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

if (!$qty || $qty < 1 || $qty > 10) {
    if ($is_ajax) json_response(false, 'Quantity must be between 1 and 10.');
    set_flash('danger', 'Invalid quantity.');
    redirect(APP_URL . '/cart/cart.php');
}

if (!isset($_SESSION['cart'][$item_id])) {
    if ($is_ajax) json_response(false, 'Item not found in cart.');
    redirect(APP_URL . '/cart/cart.php');
}

// Update qty
$_SESSION['cart'][$item_id]['qty'] = $qty;

$item_price    = $_SESSION['cart'][$item_id]['price'];
$item_subtotal = $item_price * $qty;
$cart_total    = cart_total();
$cart_count    = cart_count();

if ($is_ajax) {
    json_response(true, 'Cart updated.', [
        'cart_count'    => $cart_count,
        'cart_total'    => number_format($cart_total, 2),
        'item_subtotal' => number_format($item_subtotal, 2),
    ]);
}

set_flash('success', 'Cart updated.');
redirect(APP_URL . '/cart/cart.php');
