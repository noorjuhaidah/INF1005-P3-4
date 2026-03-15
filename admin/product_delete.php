<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$id = $_GET['id'] ?? '';

if ($id !== '' && is_numeric($id)) {

    try {
        $stmt = $pdo->prepare("
            DELETE FROM menu_items
            WHERE item_id = ?
        ");

        $stmt->execute([$id]);

    } catch (PDOException $e) {
        die("Delete failed: " . $e->getMessage());
    }

}

header('Location: ' . APP_URL . '/admin/products.php');
exit;