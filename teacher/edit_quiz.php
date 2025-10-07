<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid quiz ID.");
}
$quiz_id = intval($_GET['id']);

$sql = "SELECT * FROM quizzes WHERE quiz_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);

if (!$record) {
    die("Quiz not found.");
}

$class_id = $record['class_id'];

$subjects = [];
$sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
$stmt_sub = mysqli_prepare($conn, $sub_sql);
mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
mysqli_stmt_execute($stmt_sub);
$subjects = mysqli_stmt_get_result($stmt_sub);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $question = $_POST['question'];
    $option_one = $_POST['option_one'];
    $option_two = $_POST['option_two'];
    $option_three = $_POST['option_three'];
    $option_four = $_POST['option_four'];
    $correct_option = $_POST['correct_option'];
    $subject_id = intval($_POST['subject_id']);
    $total_marks = floatval($_POST['total_marks']);

    $update = "UPDATE quizzes SET 
                question=?, option_one=?, option_two=?, option_three=?, option_four=?, 
                correct_option=?, subject_id=?, total_marks=?
               WHERE quiz_id=?";

    $stmt2 = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt2, "ssssssidi",
        $question, $option_one, $option_two, $option_three, $option_four,
        $correct_option, $subject_id, $total_marks, $quiz_id
    );

    if (mysqli_stmt_execute($stmt2)) {
        header("Location: view_quizzes.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Quiz</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Quiz</h2>

<form method="post">
    <label>Question:</label> 
    <textarea name="question" required><?php echo htmlspecialchars($record['question']); ?></textarea> 

    <label>Option One:</label>
    <input type="text" name="option_one" value="<?php echo htmlspecialchars($record['option_one']); ?>" required> 

    <label>Option Two:</label>
    <input type="text" name="option_two" value="<?php echo htmlspecialchars($record['option_two']); ?>" required> 

    <label>Option Three:</label>
    <input type="text" name="option_three" value="<?php echo htmlspecialchars($record['option_three']); ?>" required> 

    <label>Option Four:</label>
    <input type="text" name="option_four" value="<?php echo htmlspecialchars($record['option_four']); ?>" required> 

    <label>Correct Option:</label>
    <select name="correct_option" required>
        <option value="option_one" <?php if($record['correct_option']=='option_one') echo 'selected'; ?>>Option One</option>
        <option value="option_two" <?php if($record['correct_option']=='option_two') echo 'selected'; ?>>Option Two</option>
        <option value="option_three" <?php if($record['correct_option']=='option_three') echo 'selected'; ?>>Option Three</option>
        <option value="option_four" <?php if($record['correct_option']=='option_four') echo 'selected'; ?>>Option Four</option>
    </select> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>"
                <?php if ($sub['subject_id'] == $record['subject_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Total Marks:</label>
    <input type="number" step="0.01" name="total_marks" value="<?php echo $record['total_marks']; ?>" required> 

    <button type="submit">Update Quiz</button>
</form>
</body>
</html>
