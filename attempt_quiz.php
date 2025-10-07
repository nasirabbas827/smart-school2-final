<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['subject_id'])) {
    die("Subject not specified.");
}

$student_id = $_SESSION['id'];
$subject_id = intval($_GET['subject_id']);

// Get student class
$stmt = $conn->prepare("SELECT class_id FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$class_data = $stmt->get_result()->fetch_assoc();
$class_id = $class_data ? $class_data['class_id'] : 0;

// Fetch subject name
$stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$subject_name = $sub ? $sub['subject_name'] : "Unknown Subject";

// Handle quiz submission
$result_data = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_submit'])) {
    $score = 0;
    $total = 0;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $quiz_id = intval(str_replace('question_', '', $key));
            $selected_option = $value; // option_one, option_two, etc.

            // Check correct answer
            $stmt = $conn->prepare("
                SELECT correct_option 
                FROM quizzes 
                WHERE quiz_id = ? AND subject_id = ? AND class_id = ?
            ");
            $stmt->bind_param("iii", $quiz_id, $subject_id, $class_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if ($res && $res['correct_option'] === $selected_option) {
                $score++;
            }
            $total++;
        }
    }

    $result_data = [
        'score' => $score,
        'total' => $total
    ];
}

// Fetch quizzes
$stmt = $conn->prepare("
    SELECT quiz_id, question, option_one, option_two, option_three, option_four, correct_option, total_marks
    FROM quizzes
    WHERE subject_id = ? AND class_id = ?
    ORDER BY created_at ASC
");
$stmt->bind_param("ii", $subject_id, $class_id);
$stmt->execute();
$quizzes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Attempt Quiz - <?php echo htmlspecialchars($subject_name); ?></title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <h2><?php echo htmlspecialchars($subject_name); ?> - Quiz</h2>

    <?php if ($result_data): ?>
        <h3>Your Result</h3>
        <p>Score: <?php echo $result_data['score']; ?> / <?php echo $result_data['total']; ?></p>
        <hr>
    <?php endif; ?>

    <?php if ($quizzes->num_rows > 0): ?>
        <form method="POST">
            <?php while ($q = $quizzes->fetch_assoc()): ?>
                <div style="margin-bottom:15px;">
                    <strong><?php echo htmlspecialchars($q['question']); ?></strong> 

                    <label>
                        <input type="radio" name="question_<?php echo $q['quiz_id']; ?>" value="option_one" required>
                        <?php echo htmlspecialchars($q['option_one']); ?>
                    </label> 

                    <label>
                        <input type="radio" name="question_<?php echo $q['quiz_id']; ?>" value="option_two" required>
                        <?php echo htmlspecialchars($q['option_two']); ?>
                    </label> 

                    <label>
                        <input type="radio" name="question_<?php echo $q['quiz_id']; ?>" value="option_three" required>
                        <?php echo htmlspecialchars($q['option_three']); ?>
                    </label> 

                    <label>
                        <input type="radio" name="question_<?php echo $q['quiz_id']; ?>" value="option_four" required>
                        <?php echo htmlspecialchars($q['option_four']); ?>
                    </label> 
                </div>
                <hr>
            <?php endwhile; ?>

            <button type="submit" name="quiz_submit">Submit Quiz</button>
        </form>
    <?php else: ?>
        <p>No quizzes available for this subject.</p>
    <?php endif; ?>
</div>

</body>
</html>
