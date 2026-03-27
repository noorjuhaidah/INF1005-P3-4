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
    $reviewColumns = [];
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM reviews");
    foreach ($columnsStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $reviewColumns[] = $column['Field'];
        }
    }

    $reviewIdColumn = '';
    if (in_array('id', $reviewColumns, true)) {
        $reviewIdColumn = 'id';
    } elseif (in_array('review_id', $reviewColumns, true)) {
        $reviewIdColumn = 'review_id';
    }

    if ($reviewIdColumn === '') {
        throw new RuntimeException('No supported review ID column found in reviews table.');
    }

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE {$reviewIdColumn} = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Review deleted successfully.');
    } else {
        set_flash('warning', 'Review not found or already deleted.');
    }
} catch (Throwable $e) {
    error_log('Admin review delete error: ' . $e->getMessage());
    set_flash('danger', 'Failed to delete review.');
}

redirect(APP_URL . '/admin/reviews.php');
