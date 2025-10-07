<?php
include('../config.php');
session_start();

// Allow only teachers
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

// Get teacher's class
$sql = "SELECT class_id, class_name FROM classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && $class_id) {
    $subject_name = $_POST['subject_name'];

    $insert = "INSERT INTO subjects (subject_name, class_id, teacher_id) 
               VALUES (?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt2, "sii", $subject_name, $class_id, $teacher_id);

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Subject added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Subject</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add Subject</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<?php if ($class_id): ?>
<form method="post">
    <label>Subject Name:</label>
    <input type="text" name="subject_name" required> 

    <button type="submit">Add Subject</button>
    <a href="view_subjects.php">View Subjects</a>
</form>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
