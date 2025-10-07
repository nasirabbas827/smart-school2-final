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
    $subject_id = intval($_GET['delete']);
    $del_sql = "DELETE FROM subjects WHERE subject_id = ? AND teacher_id = ?";
    $stmt_del = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt_del, "ii", $subject_id, $teacher_id);
    mysqli_stmt_execute($stmt_del);
}

// Fetch subjects
$subjects = [];
if ($class_id) {
    $query = "SELECT * FROM subjects WHERE class_id = ? AND teacher_id = ? ORDER BY subject_id DESC";
    $stmt2 = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt2, "ii", $class_id, $teacher_id);
    mysqli_stmt_execute($stmt2);
    $subjects = mysqli_stmt_get_result($stmt2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Subjects</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Subjects</h2>

<?php if ($class_id): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Subject Name</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($subjects)): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
        <td>
            <a href="edit_subject.php?id=<?php echo $row['subject_id']; ?>">Edit</a> | 
            <a href="?delete=<?php echo $row['subject_id']; ?>" onclick="return confirm('Delete this subject?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<a href="add_subject.php">Add New Subject</a>
<?php else: ?>
<p>You are not assigned to any class.</p>
<?php endif; ?>
</body>
</html>
