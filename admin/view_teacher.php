<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $teacher_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM Teachers WHERE teacher_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: view_teacher.php");
    exit();
}

// Fetch all teachers
$result = mysqli_query($conn, "SELECT * FROM Teachers ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Teachers</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Teachers List</h2>
    <a href="add_teacher.php">+ Add New Teacher</a>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Name</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['teacher_id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <a href="edit_teacher.php?id=<?php echo $row['teacher_id']; ?>">Edit</a> | 
                    <a href="view_teacher.php?delete=<?php echo $row['teacher_id']; ?>" onclick="return confirm('Delete this teacher?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
