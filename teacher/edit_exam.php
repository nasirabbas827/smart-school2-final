<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

if (!isset($_GET['id'])) {
    die("Invalid exam ID.");
}
$exam_id = intval($_GET['id']);

$sql = "SELECT * FROM exams_schedule WHERE exam_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $exam_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);

if (!$record) {
    die("Exam not found.");
}

$class_id = $record['class_id'];

$subjects = [];
$sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
$stmt_sub = mysqli_prepare($conn, $sub_sql);
mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
mysqli_stmt_execute($stmt_sub);
$subjects = mysqli_stmt_get_result($stmt_sub);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $subject_id = intval($_POST['subject_id']);
    $exam_date = $_POST['exam_date'];
    $total_marks = intval($_POST['total_marks']);

    $update = "UPDATE exams_schedule
               SET title=?, subject_id=?, exam_date=?, total_marks=?
               WHERE exam_id=?";
    $stmt2 = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt2, "sisii", $title, $subject_id, $exam_date, $total_marks, $exam_id);

    if (mysqli_stmt_execute($stmt2)) {
        header("Location: view_exams.php");
        exit();
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Exam</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Exam</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<form method="post">
    <label>Title:</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($record['title']); ?>" required> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>"
                <?php if ($sub['subject_id'] == $record['subject_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Exam Date:</label>
    <input type="date" name="exam_date" value="<?php echo $record['exam_date']; ?>" required> 

    <label>Total Marks:</label>
    <input type="number" name="total_marks" value="<?php echo $record['total_marks']; ?>" required> 

    <button type="submit">Update Exam</button>
</form>
</body>
</html>
