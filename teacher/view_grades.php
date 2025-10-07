<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];

$sql = "SELECT class_id FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($res);
$class_id = $class ? $class['class_id'] : null;

if (isset($_GET['delete'])) {
    $grade_id = intval($_GET['delete']);
    $del = "DELETE FROM grades WHERE grade_id = ? AND class_id = ?";
    $stmt_del = mysqli_prepare($conn, $del);
    mysqli_stmt_bind_param($stmt_del, "ii", $grade_id, $class_id);
    mysqli_stmt_execute($stmt_del);
}

$grades = [];
if ($class_id) {
    $q = "
    SELECT g.grade_id, g.exam_type, g.term, g.marks_obtained, g.max_marks, g.grade,
           s.first_name, s.last_name, sub.subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.student_id
    JOIN subjects sub ON g.subject_id = sub.subject_id
    WHERE g.class_id = ?
    ORDER BY g.entered_at DESC
    ";
    $stmt2 = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($stmt2, "i", $class_id);
    mysqli_stmt_execute($stmt2);
    $grades = mysqli_stmt_get_result($stmt2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Grades</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Grades</h2>

<?php if ($class_id): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Student</th>
        <th>Subject</th>
        <th>Exam Type</th>
        <th>Term</th>
        <th>Marks</th>
        <th>Grade</th>
        <th>Action</th>
    </tr>
    <?php while($g = mysqli_fetch_assoc($grades)): ?>
        <tr>
            <td><?php echo htmlspecialchars($g['first_name']." ".$g['last_name']); ?></td>
            <td><?php echo htmlspecialchars($g['subject_name']); ?></td>
            <td><?php echo htmlspecialchars($g['exam_type']); ?></td>
            <td><?php echo htmlspecialchars($g['term']); ?></td>
            <td><?php echo $g['marks_obtained']." / ".$g['max_marks']; ?></td>
            <td><?php echo htmlspecialchars($g['grade']); ?></td>
            <td>
                <a href="edit_grade.php?id=<?php echo $g['grade_id']; ?>">Edit</a> |
                <a href="?delete=<?php echo $g['grade_id']; ?>" onclick="return confirm('Delete this record?');">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
