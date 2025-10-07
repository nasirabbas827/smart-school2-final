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
    $quiz_id = intval($_GET['delete']);
    $del_sql = "DELETE FROM quizzes WHERE quiz_id = ? AND class_id = ?";
    $stmt_del = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt_del, "ii", $quiz_id, $class_id);
    mysqli_stmt_execute($stmt_del);
}

$quizzes = [];
if ($class_id) {
    $query = "
        SELECT q.quiz_id, q.question, q.total_marks, s.subject_name
        FROM quizzes q
        LEFT JOIN subjects s ON q.subject_id = s.subject_id
        WHERE q.class_id = ?
        ORDER BY q.created_at DESC
    ";
    $stmt2 = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt2, "i", $class_id);
    mysqli_stmt_execute($stmt2);
    $quizzes = mysqli_stmt_get_result($stmt2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Quizzes</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Online Quizzes</h2>

<?php if ($class_id): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Question</th>
        <th>Subject</th>
        <th>Total Marks</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($quizzes)): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['question']); ?></td>
        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
        <td><?php echo $row['total_marks']; ?></td>
        <td>
            <a href="edit_quiz.php?id=<?php echo $row['quiz_id']; ?>">Edit</a> |
            <a href="?delete=<?php echo $row['quiz_id']; ?>" onclick="return confirm('Delete this quiz?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>You are not assigned to any class.</p>
<?php endif; ?>
</body>
</html>
