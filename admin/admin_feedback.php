<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}


/* Reply to feedback */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['reply'])) {
    $fid = intval($_POST['feedback_id']);
    $reply = trim($_POST['reply']);

    $stmt = $conn->prepare("UPDATE feedback SET reply = ? WHERE feedback_id = ?");
    $stmt->bind_param("si", $reply, $fid);
    $stmt->execute();

    header("Location: admin_feedback.php");
    exit();
}

/* Fetch all feedback */
$query = "
    SELECT f.feedback_id, f.message, f.reply, f.created_at, 
           s.first_name, s.last_name
    FROM feedback f
    LEFT JOIN students s ON f.student_id = s.student_id
    ORDER BY f.created_at DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Feedback</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<h2>Student Feedback</h2>

<?php if ($result && $result->num_rows > 0): ?>
<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Student</th>
        <th>Message</th>
        <th>Reply</th>
        <th>Created At</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
        <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
        <td><?php echo $row['reply'] ? nl2br(htmlspecialchars($row['reply'])) : '<em>No reply yet</em>'; ?></td>
        <td><?php echo $row['created_at']; ?></td>
        <td>
            <form method="post" action="">
                <input type="hidden" name="feedback_id" value="<?php echo $row['feedback_id']; ?>">
                <textarea name="reply" rows="2" cols="30" placeholder="Write reply..."><?php echo htmlspecialchars($row['reply']); ?></textarea> 
                <button type="submit">Submit Reply</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No feedback available.</p>
<?php endif; ?>

</body>
</html>
