<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];

// Get teacher class
$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

// Handle Delete
if (isset($_GET['delete'])) {
    $timetable_id = intval($_GET['delete']);
    $del_sql = "DELETE FROM Timetables WHERE timetable_id = ? AND uploaded_by = ?";
    $stmt_del = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt_del, "ii", $timetable_id, $teacher_id);
    mysqli_stmt_execute($stmt_del);
}

$timetables = [];
if ($class_id) {
    $query = "
        SELECT t.timetable_id, t.day_of_week, t.start_time, t.end_time,
               s.subject_name
        FROM Timetables t
        JOIN subjects s ON t.subject_id = s.subject_id
        WHERE t.class_id = ?
        ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                 t.start_time ASC
    ";
    $stmt2 = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt2, "i", $class_id);
    mysqli_stmt_execute($stmt2);
    $timetables = mysqli_stmt_get_result($stmt2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Timetables</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Class Timetable</h2>

<?php if ($class_id): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Day</th>
        <th>Start</th>
        <th>End</th>
        <th>Subject</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($timetables)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['day_of_week']); ?></td>
            <td><?php echo htmlspecialchars($row['start_time']); ?></td>
            <td><?php echo htmlspecialchars($row['end_time']); ?></td>
            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
            <td>
                <a href="edit_timetable.php?id=<?php echo $row['timetable_id']; ?>">Edit</a> |
                <a href="?delete=<?php echo $row['timetable_id']; ?>" onclick="return confirm('Delete this entry?');">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>You are not assigned to any class.</p>
<?php endif; ?>
</body>
</html>
