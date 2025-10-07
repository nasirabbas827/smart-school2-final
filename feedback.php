<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

/* Delete feedback */
if (isset($_GET['delete'])) {
    $fid = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $fid, $student_id);
    $stmt->execute();
    header("Location: feedback.php");
    exit();
}

/* Add feedback */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = trim($_POST['message']);
    if ($msg !== "") {
        $stmt = $conn->prepare("INSERT INTO feedback (student_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $msg);
        $stmt->execute();
    }
    header("Location: feedback.php");
    exit();
}

/* Fetch student's feedback */
$stmt = $conn->prepare("
    SELECT feedback_id, message, reply, created_at
    FROM feedback
    WHERE student_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$feedbacks = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<h2>Your Feedback</h2>

<form method="post">
    <textarea name="message" rows="4" cols="60" placeholder="Write your feedback..." required></textarea> 
    <button type="submit">Submit Feedback</button>
</form>

<h3>Previous Feedback</h3>
<?php if ($feedbacks->num_rows > 0): ?>
<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Message</th>
        <th>Reply</th>
        <th>Created At</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $feedbacks->fetch_assoc()): ?>
    <tr>
        <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
        <td><?php echo $row['reply'] ? nl2br(htmlspecialchars($row['reply'])) : 'No reply yet'; ?></td>
        <td><?php echo $row['created_at']; ?></td>
        <td>
            <a href="feedback.php?delete=<?php echo $row['feedback_id']; ?>" onclick="return confirm('Delete this feedback?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No feedback yet.</p>
<?php endif; ?>

</body>
</html>
