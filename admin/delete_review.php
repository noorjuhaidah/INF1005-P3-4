<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('warning', 'Invalid request method.');
    redirect(APP_URL . '/admin/reviews.php');
}

verify_csrf(APP_URL . '/admin/reviews.php');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('danger', 'Invalid review ID.');
    redirect(APP_URL . '/admin/reviews.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Review deleted successfully.');
    } else {
        set_flash('warning', 'Review not found or already deleted.');
    }
} catch (PDOException $e) {
    error_log('Admin review delete error: ' . $e->getMessage());
    set_flash('danger', 'Failed to delete review.');
}

redirect(APP_URL . '/admin/reviews.php');
