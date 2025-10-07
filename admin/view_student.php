<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $student_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM Students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: view_student.php");
    exit();
}

// Fetch students with class
$query = "SELECT s.*, c.class_name 
          FROM Students s 
          LEFT JOIN Classes c ON s.class_id = c.class_id
          ORDER BY s.student_id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Students</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Students List</h2>
    <a href="add_student.php">+ Add New Student</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Email</th>
            <th>Roll Number</th>
            <th>Class</th>
            <th>DOB</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['student_id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                <td><?php echo $row['class_name'] ? htmlspecialchars($row['class_name']) : "None"; ?></td>
                <td><?php echo $row['date_of_birth']; ?></td>
                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td>
                    <a href="edit_student.php?id=<?php echo $row['student_id']; ?>">Edit</a> | 
                    <a href="view_student.php?delete=<?php echo $row['student_id']; ?>" onclick="return confirm('Delete this student?');">Delete</a> | 
                    <a href="student_report.php?id=<?php echo $row['student_id']; ?>">Generate Report</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>