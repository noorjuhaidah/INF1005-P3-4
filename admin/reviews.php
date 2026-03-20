<?php
include "db.php";

$sql = "SELECT * FROM reviews ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews</title>
</head>
<body>

<h2>Review Management</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Rating</th>
        <th>Comment</th>
        <th>Actions</th>
    </tr>

    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= $row['rating'] ?></td>
        <td><?= htmlspecialchars($row['comment']) ?></td>
        <td>
            <a href="edit_review.php?id=<?= $row['id'] ?>">Edit</a> |
            <a href="delete_review.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this review?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>

</table>

<br>
<a href="dashboard.php">← Back to Dashboard</a>

</body>
</html>