<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['assignment_id'])) {
    die("Assignment not specified.");
}

$teacher_id = $_SESSION["id"];
$assignment_id = intval($_GET['assignment_id']);

// Fetch assignment title
$assignment_query = "
    SELECT a.title, s.subject_name
    FROM assignments a
    LEFT JOIN subjects s ON a.subject_id = s.subject_id
    WHERE a.assignment_id = ?
";
$stmt = mysqli_prepare($conn, $assignment_query);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$assignment = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$assignment) {
    die("Assignment not found.");
}

// Handle grade submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade = trim($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    $update_sql = "
        UPDATE assignment_submissions
        SET grade = ?, feedback = ?
        WHERE submission_id = ?
    ";
    $stmt_update = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt_update, "ssi", $grade, $feedback, $submission_id);
    mysqli_stmt_execute($stmt_update);
}

// Fetch submissions
$submissions_sql = "
    SELECT asub.submission_id, asub.file_path, asub.submitted_at,
           asub.grade, asub.feedback,
           stu.student_id, stu.first_name AS student_name
    FROM assignment_submissions asub
    LEFT JOIN students stu ON asub.student_id = stu.student_id
    WHERE asub.assignment_id = ?
    ORDER BY asub.submitted_at DESC
";
$stmt_sub = mysqli_prepare($conn, $submissions_sql);
mysqli_stmt_bind_param($stmt_sub, "i", $assignment_id);
mysqli_stmt_execute($stmt_sub);
$submissions = mysqli_stmt_get_result($stmt_sub);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Assignment</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<h2>Mark Assignment: <?php echo htmlspecialchars($assignment['title']); ?></h2>
<p>Subject: <?php echo htmlspecialchars($assignment['subject_name']); ?></p>

<?php if ($submissions->num_rows > 0): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Student</th>
        <th>Submission File</th>
        <th>Submitted At</th>
        <th>Grade</th>
        <th>Feedback</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $submissions->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
        <td>
            <?php if (!empty($row['file_path'])): ?>
                <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">View File</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
        <td><?php echo $row['submitted_at']; ?></td>
        <td>
            <form method="post">
                <input type="hidden" name="submission_id" value="<?php echo $row['submission_id']; ?>">
                <input type="text" name="grade" value="<?php echo htmlspecialchars($row['grade']); ?>" required>
        </td>
        <td>
                <textarea name="feedback" rows="2" cols="25"><?php echo htmlspecialchars($row['feedback']); ?></textarea>
        </td>
        <td>
                <button type="submit">Save</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No submissions found for this assignment.</p>
<?php endif; ?>

</body>
</html>
