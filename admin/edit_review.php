<?php
include "db.php";

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = intval($_GET['id']);

// Get existing review
$stmt = $conn->prepare("SELECT * FROM reviews WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$review = $result->fetch_assoc();

if (!$review) {
    die("Review not found");
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'];

    $stmt = $conn->prepare("UPDATE reviews SET name=?, rating=?, comment=? WHERE id=?");
    $stmt->bind_param("sisi", $name, $rating, $comment, $id);
    $stmt->execute();

    header("Location: reviews.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Review</title>
</head>
<body>

<h2>Edit Review</h2>

<form method="POST">
    <label>Name:</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($review['name']) ?>"><br><br>

    <label>Rating (1-5):</label><br>
    <input type="number" name="rating" min="1" max="5" value="<?= $review['rating'] ?>"><br><br>

    <label>Comment:</label><br>
    <textarea name="comment"><?= htmlspecialchars($review['comment']) ?></textarea><br><br>

    <button type="submit">Update Review</button>
</form>

<br>
<a href="reviews.php">← Back</a>

</body>
</html>