<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

// Fetch class
$sql = "SELECT class_id FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($res);
$class_id = $class ? $class['class_id'] : null;

// Fetch students of the class
$students = [];
if ($class_id) {
    $stu_sql = "SELECT student_id, first_name, last_name FROM students WHERE class_id = ?";
    $stmt_stu = mysqli_prepare($conn, $stu_sql);
    mysqli_stmt_bind_param($stmt_stu, "i", $class_id);
    mysqli_stmt_execute($stmt_stu);
    $students = mysqli_stmt_get_result($stmt_stu);
}

// Fetch subjects for that class
$subjects = [];
if ($class_id) {
    $sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
    $stmt_sub = mysqli_prepare($conn, $sub_sql);
    mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
    mysqli_stmt_execute($stmt_sub);
    $subjects = mysqli_stmt_get_result($stmt_sub);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $class_id) {
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $exam_type = $_POST['exam_type'];
    $term = $_POST['term'];
    $marks_obtained = floatval($_POST['marks_obtained']);
    $max_marks = floatval($_POST['max_marks']);
    $grade = $_POST['grade'];
    $comments = $_POST['comments'];

    $insert = "INSERT INTO grades 
        (student_id, subject_id, class_id, exam_type, term, 
         marks_obtained, max_marks, grade, comments, entered_by)
        VALUES (?,?,?,?,?,?,?,?,?,?)";

    $stmt2 = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt2, "iiissddssi", 
        $student_id, $subject_id, $class_id, $exam_type, $term,
        $marks_obtained, $max_marks, $grade, $comments, $teacher_id
    );

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Grade added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Grade</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add Grade</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<?php if ($class_id): ?>
<form method="post">
    <label>Student:</label>
    <select name="student_id" required>
        <option value="">Select student</option>
        <?php while($stu = mysqli_fetch_assoc($students)): ?>
            <option value="<?php echo $stu['student_id']; ?>">
                <?php echo htmlspecialchars($stu['first_name']." ".$stu['last_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <option value="">Select subject</option>
        <?php while($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>">
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Exam Type:</label>
    <input type="text" name="exam_type" required> 

    <label>Term:</label>
    <input type="text" name="term" required> 

    <label>Marks Obtained:</label>
    <input type="number" step="0.01" name="marks_obtained" required> 

    <label>Max Marks:</label>
    <input type="number" step="0.01" name="max_marks" required> 

    <label>Grade:</label>
    <input type="text" name="grade"> 

    <label>Comments:</label>
    <textarea name="comments"></textarea> 

    <button type="submit">Add Grade</button>
    <a href="view_grades.php">View Grades</a>
</form>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
