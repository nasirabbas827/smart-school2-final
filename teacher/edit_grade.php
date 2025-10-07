<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid grade ID.");
}
$grade_id = intval($_GET['id']);
$teacher_id = $_SESSION["id"];

$sql = "SELECT * FROM grades WHERE grade_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $grade_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($res);

if (!$record) die("Grade not found.");

$class_id = $record['class_id'];

// Fetch subjects
$subjects = [];
$sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
$stmt_sub = mysqli_prepare($conn, $sub_sql);
mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
mysqli_stmt_execute($stmt_sub);
$subjects = mysqli_stmt_get_result($stmt_sub);

// Fetch students
$students = [];
$stu_sql = "SELECT student_id, first_name, last_name FROM students WHERE class_id = ?";
$stmt_stu = mysqli_prepare($conn, $stu_sql);
mysqli_stmt_bind_param($stmt_stu, "i", $class_id);
mysqli_stmt_execute($stmt_stu);
$students = mysqli_stmt_get_result($stmt_stu);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $exam_type = $_POST['exam_type'];
    $term = $_POST['term'];
    $marks_obtained = floatval($_POST['marks_obtained']);
    $max_marks = floatval($_POST['max_marks']);
    $grade = $_POST['grade'];
    $comments = $_POST['comments'];

    $up = "UPDATE grades SET 
           student_id=?, subject_id=?, exam_type=?, term=?, 
           marks_obtained=?, max_marks=?, grade=?, comments=?
           WHERE grade_id=?";

    $stmt2 = mysqli_prepare($conn, $up);
    mysqli_stmt_bind_param($stmt2, "iissddssi",
        $student_id, $subject_id, $exam_type, $term,
        $marks_obtained, $max_marks, $grade, $comments, $grade_id
    );

    if (mysqli_stmt_execute($stmt2)) {
        header("Location: view_grades.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Grade</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Grade</h2>

<form method="post">
    <label>Student:</label>
    <select name="student_id" required>
        <?php mysqli_data_seek($students, 0);
        while($s = mysqli_fetch_assoc($students)): ?>
            <option value="<?php echo $s['student_id']; ?>"
                <?php if($s['student_id']==$record['student_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($s['first_name']." ".$s['last_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <?php while($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>"
                <?php if($sub['subject_id']==$record['subject_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Exam Type:</label>
    <input type="text" name="exam_type" value="<?php echo htmlspecialchars($record['exam_type']); ?>" required> 

    <label>Term:</label>
    <input type="text" name="term" value="<?php echo htmlspecialchars($record['term']); ?>" required> 

    <label>Marks Obtained:</label>
    <input type="number" step="0.01" name="marks_obtained" value="<?php echo $record['marks_obtained']; ?>" required> 

    <label>Max Marks:</label>
    <input type="number" step="0.01" name="max_marks" value="<?php echo $record['max_marks']; ?>" required> 

    <label>Grade:</label>
    <input type="text" name="grade" value="<?php echo htmlspecialchars($record['grade']); ?>"> 

    <label>Comments:</label>
    <textarea name="comments"><?php echo htmlspecialchars($record['comments']); ?></textarea> 

    <button type="submit">Update Grade</button>
</form>
</body>
</html>
