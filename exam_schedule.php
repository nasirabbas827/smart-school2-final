<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

/* ========== Get Student Class ========== */
$student_sql = "SELECT class_id FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_data = $stmt->get_result()->fetch_assoc();
$class_id = $student_data['class_id'] ?? null;

/* ========== Fetch Exam Schedule for Class ========== */
$exams = [];
if ($class_id) {
    $exam_stmt = $conn->prepare("
        SELECT e.exam_id, e.title, e.subject_id, e.exam_date, e.total_marks, s.subject_name
        FROM exams_schedule e
        LEFT JOIN subjects s ON e.subject_id = s.subject_id
        WHERE e.class_id = ?
        ORDER BY e.exam_date ASC
    ");
    $exam_stmt->bind_param("i", $class_id);
    $exam_stmt->execute();
    $exams = $exam_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Exam Schedule</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<h2>Exam Schedule</h2>

<?php if ($exams && $exams->num_rows > 0): ?>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Title</th>
        <th>Subject</th>
        <th>Exam Date</th>
        <th>Total Marks</th>
    </tr>
    <?php while ($row = $exams->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['subject_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
            <td><?php echo htmlspecialchars($row['total_marks']); ?></td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
    <p>No exam schedule found for your class.</p>
<?php endif; ?>

</body>
</html>
