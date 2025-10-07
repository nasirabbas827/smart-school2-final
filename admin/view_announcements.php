<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM Announcements ORDER BY posted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Announcements</title>
    <link rel="stylesheet" href="../CSS/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<h2>All Announcements</h2>
<a href="add_announcement.php">+Add New Announcement</a>
<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Content</th>
        <th>Posted At</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?php echo $row['announcement_id']; ?></td>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars($row['content']); ?></td>
        <td><?php echo $row['posted_at']; ?></td>
        <td>
            <a href="edit_announcement.php?id=<?php echo $row['announcement_id']; ?>">Edit</a> | 
            <a href="delete_announcement.php?id=<?php echo $row['announcement_id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
    </tr>
    <?php } ?>
</table>
</body>
</html>
