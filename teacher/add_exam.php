<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

// Get teacher class
$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

// Fetch subjects for this class
$subjects = [];
if ($class_id) {
    $sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
    $stmt_sub = mysqli_prepare($conn, $sub_sql);
    mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
    mysqli_stmt_execute($stmt_sub);
    $subjects = mysqli_stmt_get_result($stmt_sub);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $class_id) {
    $title = $_POST['title'];
    $subject_id = intval($_POST['subject_id']);
    $exam_date = $_POST['exam_date'];
    $total_marks = intval($_POST['total_marks']);

    $insert = "INSERT INTO exams_schedule (title, class_id, subject_id, exam_date, total_marks)
               VALUES (?,?,?,?,?)";
    $stmt2 = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt2, "siisi", $title, $class_id, $subject_id, $exam_date, $total_marks);

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Exam added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Exam</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add Exam</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<?php if ($class_id): ?>
<form method="post">
    <label>Title:</label>
    <input type="text" name="title" required> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <option value="">Select Subject</option>
        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>">
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Exam Date:</label>
    <input type="date" name="exam_date" required> 

    <label>Total Marks:</label>
    <input type="number" name="total_marks" required> 

    <button type="submit">Add Exam</button>
    <a href="view_exams.php">View Exams</a>
</form>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
