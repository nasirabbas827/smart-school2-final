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
$sql = "SELECT class_id FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

// Get subjects for class
$subjects = [];
if ($class_id) {
    $sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
    $stmt_sub = mysqli_prepare($conn, $sub_sql);
    mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
    mysqli_stmt_execute($stmt_sub);
    $subjects = mysqli_stmt_get_result($stmt_sub);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $class_id) {
    $question = $_POST['question'];
    $option_one = $_POST['option_one'];
    $option_two = $_POST['option_two'];
    $option_three = $_POST['option_three'];
    $option_four = $_POST['option_four'];
    $correct_option = $_POST['correct_option'];
    $subject_id = intval($_POST['subject_id']);
    $total_marks = floatval($_POST['total_marks']);

    $insert = "INSERT INTO quizzes 
    (question, option_one, option_two, option_three, option_four, correct_option, subject_id, class_id, created_by, total_marks)
    VALUES (?,?,?,?,?,?,?,?,?,?)";

    $stmt2 = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt2, "ssssssiiid", 
        $question, $option_one, $option_two, $option_three, $option_four,
        $correct_option, $subject_id, $class_id, $teacher_id, $total_marks
    );

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Quiz added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Quiz</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add Quiz</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<?php if ($class_id): ?>
<form method="post">
    <label>Question:</label> 
    <textarea name="question" required></textarea> 

    <label>Option One:</label>
    <input type="text" name="option_one" required> 

    <label>Option Two:</label>
    <input type="text" name="option_two" required> 

    <label>Option Three:</label>
    <input type="text" name="option_three" required> 

    <label>Option Four:</label>
    <input type="text" name="option_four" required> 

    <label>Correct Option:</label>
    <select name="correct_option" required>
        <option value="option_one">Option One</option>
        <option value="option_two">Option Two</option>
        <option value="option_three">Option Three</option>
        <option value="option_four">Option Four</option>
    </select> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <option value="">Select Subject</option>
        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>">
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Total Marks:</label>
    <input type="number" step="0.01" name="total_marks" required> 

    <button type="submit">Add Quiz</button>
    <a href="view_quizzes.php">View Quizzes</a>
</form>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
