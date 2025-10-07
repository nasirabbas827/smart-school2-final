<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];

// Get teacher's assigned class
$sql = "SELECT class_id FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

if (isset($_GET['delete'])) {
    $exam_id = intval($_GET['delete']);
    $del_sql = "DELETE FROM exams_schedule WHERE exam_id = ? AND class_id = ?";
    $stmt_del = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt_del, "ii", $exam_id, $class_id);
    mysqli_stmt_execute($stmt_del);
}

$exams = [];
if ($class_id) {
    $query = "
        SELECT e.exam_id, e.title, e.exam_date, e.total_marks, s.subject_name
        FROM exams_schedule e
        LEFT JOIN subjects s ON e.subject_id = s.subject_id
        WHERE e.class_id = ?
        ORDER BY e.exam_date DESC
    ";
    $stmt2 = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt2, "i", $class_id);
    mysqli_stmt_execute($stmt2);
    $exams = mysqli_stmt_get_result($stmt2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Exams</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Exam Schedule</h2>

<?php if ($class_id): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Title</th>
        <th>Subject</th>
        <th>Exam Date</th>
        <th>Total Marks</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($exams)): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
        <td><?php echo $row['exam_date']; ?></td>
        <td><?php echo $row['total_marks']; ?></td>
        <td>
            <a href="edit_exam.php?id=<?php echo $row['exam_id']; ?>">Edit</a> |
            <a href="?delete=<?php echo $row['exam_id']; ?>" onclick="return confirm('Delete this exam?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
