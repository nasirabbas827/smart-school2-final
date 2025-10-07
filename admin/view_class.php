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
    $class_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM Classes WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: view_class.php");
    exit();
}

// Fetch all classes with teacher name
$query = "SELECT c.class_id, c.class_name, t.first_name, t.last_name 
          FROM Classes c 
          LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id
          ORDER BY c.class_id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Classes</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Classes List</h2>
    <a href="add_class.php">+ Add New Class</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Class Name</th>
            <th>Assigned Teacher</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['class_id']; ?></td>
                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                <td><?php echo $row['first_name'] ? htmlspecialchars($row['first_name'] . " " . $row['last_name']) : "None"; ?></td>
                <td>
                    <a href="edit_class.php?id=<?php echo $row['class_id']; ?>">Edit</a> | 
                    <a href="view_class.php?delete=<?php echo $row['class_id']; ?>" onclick="return confirm('Delete this class?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
