<?php
// =============================================================
// cart/update_cart.php — Update or Remove Cart Items
// Accepts POST from cart.php (update qty or remove item).
// Responds with JSON (AJAX) or redirects (plain POST fallback).
// =============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


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
    redirect('cart.php');
}

if (!is_logged_in()) {
    if ($is_ajax) json_response(false, 'Please log in.');
    redirect('../auth/login.php');
}

// CSRF check
$submitted_token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    if ($is_ajax) json_response(false, 'Invalid request. Please refresh and try again.');
    set_flash('danger', 'Invalid request.');
    redirect('cart.php');
}

// Validate inputs.
// Use $_POST + filter_var instead of filter_input(INPUT_POST, ...)
// to avoid SAPI inconsistencies with multipart/form-data payloads.
$item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
$posted_cart_key = (string)($_POST['cart_key'] ?? '');
$action  = clean_input((string)($_POST['action'] ?? ''));

if (!in_array($action, ['update', 'remove'])) {
    if ($is_ajax) json_response(false, 'Invalid request data.');
    redirect('cart.php');
}

// Initialise cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Support both integer and string keys for legacy cart sessions.
$cart_key = null;
if ($posted_cart_key !== '' && array_key_exists($posted_cart_key, $_SESSION['cart'])) {
    $cart_key = $posted_cart_key;
} elseif ($item_id !== false && isset($_SESSION['cart'][$item_id])) {
    $cart_key = $item_id;
} elseif ($item_id !== false && isset($_SESSION['cart'][(string)$item_id])) {
    $cart_key = (string)$item_id;
}

// -------------------------------------------------------------
// REMOVE action
// -------------------------------------------------------------
if ($action === 'remove') {
    if ($cart_key === null || !isset($_SESSION['cart'][$cart_key])) {
        if ($is_ajax) json_response(false, 'Item not found in cart.');
        set_flash('warning', 'Item is no longer in your cart.');
        redirect('cart.php');
    }

    $removedName = '';
    if (isset($_SESSION['cart'][$cart_key]['name'])) {
        $removedName = (string)$_SESSION['cart'][$cart_key]['name'];
    }

    unset($_SESSION['cart'][$cart_key]);

    $cart_count = cart_count();
    $cart_total = cart_total();

    if ($is_ajax) {
        json_response(true, $removedName !== '' ? ($removedName . ' removed from cart.') : 'Item removed from cart.', [
            'cart_count' => $cart_count,
            'cart_total' => number_format($cart_total, 2),
        ]);
    }
    set_flash('success', 'Item removed from cart.');
    redirect('cart.php');
}

// -------------------------------------------------------------
// UPDATE action
// -------------------------------------------------------------
$qty = filter_var($_POST['qty'] ?? null, FILTER_VALIDATE_INT);

if (!$qty || $qty < 1 || $qty > 10) {
    if ($is_ajax) json_response(false, 'Quantity must be between 1 and 10.');
    set_flash('danger', 'Invalid quantity.');
    redirect('cart.php');
}

if ($cart_key === null || !isset($_SESSION['cart'][$cart_key])) {
    if ($is_ajax) json_response(false, 'Item not found in cart.');
    redirect('cart.php');
}

// Update qty
$_SESSION['cart'][$cart_key]['qty'] = $qty;

$item_price    = $_SESSION['cart'][$cart_key]['price'];
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
redirect('cart.php');
