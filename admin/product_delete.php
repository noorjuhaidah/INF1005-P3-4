<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$id = $_GET['id'] ?? '';

if ($id !== '' && is_numeric($id)) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
}

header('Location: ' . APP_URL . '/admin/products.php');
exit;