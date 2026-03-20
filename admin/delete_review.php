<?php
include "db.php";

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM reviews WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: reviews.php");
exit();