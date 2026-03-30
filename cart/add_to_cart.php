<?php

// =============================================================
// cart/add_to_cart.php — Add Item to Cart Handler
// Accepts POST from menu.php add-to-cart forms.
// Responds with JSON (AJAX) or redirects (plain POST fallback).
//
// DB columns used:
//   menu_items : item_id, item_name, price, is_available
// =============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------
// Helper: send JSON response (used by AJAX calls from menu.php)
// -------------------------------------------------------------
function json_response(bool $success, string $message, array $extra = []): void
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// -------------------------------------------------------------
// Must be POST
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        json_response(false, 'Invalid request method.');
    }
    redirect(APP_URL . '/menu.php');
}

// -------------------------------------------------------------
// Must be logged in
// -------------------------------------------------------------
if (!is_logged_in()) {
    if ($is_ajax) {
        json_response(false, 'Please log in to add items to your cart.');
    }
    set_flash('warning', 'Please log in to add items to your cart.');
    redirect(APP_URL . '/auth/login.php');
}

// -------------------------------------------------------------
// CSRF validation — prevents cross-site request forgery
// -------------------------------------------------------------
$submitted_token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    if ($is_ajax) {
        json_response(false, 'Invalid request. Please refresh and try again.');
    }
    set_flash('danger', 'Invalid request. Please try again.');
    redirect(APP_URL . '/menu.php');
}

// -------------------------------------------------------------
// Sanitize & validate inputs
// -------------------------------------------------------------
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

if (!$item_id || !$qty || $qty < 1 || $qty > 10) {
    if ($is_ajax) {
        json_response(false, 'Invalid item data. Please try again.');
    }
    set_flash('danger', 'Invalid item data.');
    redirect(APP_URL . '/menu.php');
}

// -------------------------------------------------------------
// Verify item exists in DB and re-fetch the real price.
// NEVER trust the price posted from the form — always use DB.
// -------------------------------------------------------------
try {
    $stmt = $pdo->prepare(
        "SELECT item_id, item_name, price, is_available
           FROM menu_items
          WHERE item_id = ?
          LIMIT 1"
    );
    $stmt->execute([$item_id]);
    $db_item = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Cart DB error: ' . $e->getMessage());
    if ($is_ajax) {
        json_response(false, 'Could not add item right now. Please try again.');
    }
    set_flash('danger', 'Could not add item right now. Please try again.');
    redirect(APP_URL . '/menu.php');
}

if (!$db_item || !$db_item['is_available']) {
    if ($is_ajax) {
        json_response(false, 'This item is no longer available.');
    }
    set_flash('warning', 'Sorry, that item is no longer available.');
    redirect(APP_URL . '/menu.php');
}

// -------------------------------------------------------------
// Add to session cart (or increase qty if already present).
// Cart structure:
//   $_SESSION['cart'][ item_id ] = [
//       'name'  => string,
//       'price' => float,
//       'qty'   => int
//   ]
// -------------------------------------------------------------
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$id = (int) $db_item['item_id'];

if (isset($_SESSION['cart'][$id])) {
    // Already in cart — increase qty, capped at 10
    $new_qty = min($_SESSION['cart'][$id]['qty'] + $qty, 10);
    $_SESSION['cart'][$id]['qty'] = $new_qty;
} else {
    // New item — add to cart
    $_SESSION['cart'][$id] = [
        'name' => $db_item['item_name'],
        'price' => (float) $db_item['price'],
        'qty' => $qty,
    ];
}

// -------------------------------------------------------------
// Respond
// -------------------------------------------------------------
$cart_count = cart_count();

if ($is_ajax) {
    json_response(true, e($db_item['item_name']) . ' added to your cart!', [
        'cart_count' => $cart_count,
    ]);
}

// Non-JS fallback
set_flash('success', e($db_item['item_name']) . ' added to your cart!');
redirect(APP_URL . '/menu.php');
