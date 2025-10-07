<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch attendance records with student, class, and teacher info
$sql = "SELECT a.attendance_id, s.first_name AS student_fname, s.last_name AS student_lname, 
               c.class_name, a.date, a.status, t.first_name AS teacher_fname, t.last_name AS teacher_lname
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN classes c ON a.class_id = c.class_id
        JOIN teachers t ON a.marked_by = t.teacher_id
        ORDER BY a.date DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance - Admin</title>
    <link rel="stylesheet" href="../CSS/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <h2>Attendance Records</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Class</th>
                <th>Date</th>
                <th>Status</th>
                <th>Marked By</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['attendance_id']; ?></td>
                    <td><?php echo $row['student_fname'] . " " . $row['student_lname']; ?></td>
                    <td><?php echo $row['class_name']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td class="<?php echo 'status-' . strtolower($row['status']); ?>">
                        <?php echo $row['status']; ?>
                    </td>
                    <td><?php echo $row['teacher_fname'] . " " . $row['teacher_lname']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
