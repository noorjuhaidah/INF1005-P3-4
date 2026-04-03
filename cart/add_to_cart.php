<?php

// Handles add-to-cart requests from the menu page.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Return a JSON response for AJAX requests.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        json_response(false, 'Invalid request method.');
    }
    redirect(APP_URL . '/menu.php');
}

if (!is_logged_in()) {
    if ($is_ajax) {
        json_response(false, 'Please log in to add items to your cart.');
    }
    set_flash('warning', 'Please log in to add items to your cart.');
    redirect(APP_URL . '/auth/login.php');
}

// Check the CSRF token before changing the cart.
$submitted_token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    if ($is_ajax) {
        json_response(false, 'Invalid request. Please refresh and try again.');
    }
    set_flash('danger', 'Invalid request. Please try again.');
    redirect(APP_URL . '/menu.php');
}

// Validate the selected item and quantity.
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

if (!$item_id || !$qty || $qty < 1 || $qty > 10) {
    if ($is_ajax) {
        json_response(false, 'Invalid item data. Please try again.');
    }
    set_flash('danger', 'Invalid item data.');
    redirect(APP_URL . '/menu.php');
}

// Always fetch the item again so the cart uses the current database price.
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

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$id = (int) $db_item['item_id'];

if (isset($_SESSION['cart'][$id])) {
    // Keep the quantity within the menu limit.
    $new_qty = min($_SESSION['cart'][$id]['qty'] + $qty, 10);
    $_SESSION['cart'][$id]['qty'] = $new_qty;
} else {
    $_SESSION['cart'][$id] = [
        'name' => $db_item['item_name'],
        'price' => (float) $db_item['price'],
        'qty' => $qty,
    ];
}

$cart_count = cart_count();

if ($is_ajax) {
    json_response(true, e($db_item['item_name']) . ' added to your cart!', [
        'cart_count' => $cart_count,
    ]);
}

// Fallback for normal form submissions.
set_flash('success', e($db_item['item_name']) . ' added to your cart!');
redirect(APP_URL . '/menu.php');
