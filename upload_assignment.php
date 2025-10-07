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

$subject_id = intval($_GET['subject_id']);
$student_id = $_SESSION['id'];

// Get class ID of student
$stmt = $conn->prepare("SELECT class_id FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$class_id = $student ? $student['class_id'] : 0;

// Fetch assignments for this subject & class
$sql = "
    SELECT * 
    FROM assignments
    WHERE subject_id = ? AND class_id = ?
    ORDER BY due_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $subject_id, $class_id);
$stmt->execute();
$assignments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assignments</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container">
    <h2>Assignments</h2>

    <?php if ($assignments->num_rows > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Due Date</th>
                <th>File</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $assignments->fetch_assoc()): ?>
                <?php
                $assignment_id = $row['assignment_id'];

                // Check if student already submitted this assignment
                $sub_sql = "
                    SELECT * FROM assignment_submissions
                    WHERE assignment_id = ? AND student_id = ?
                    LIMIT 1
                ";
                $sub_stmt = $conn->prepare($sub_sql);
                $sub_stmt->bind_param("ii", $assignment_id, $student_id);
                $sub_stmt->execute();
                $submission = $sub_stmt->get_result()->fetch_assoc();

                $today = date('Y-m-d');
                $is_due = ($row['due_date'] >= $today);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                    <td>
                        <?php if (!empty($row['file_path'])): ?>
                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">Download</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($submission): ?>
                            <?php if (!empty($submission['grade']) || !empty($submission['feedback'])): ?>
                                <strong>Grade:</strong> <?php echo htmlspecialchars($submission['grade']); ?> 
                                <strong>Feedback:</strong> <?php echo htmlspecialchars($submission['feedback']); ?>
                            <?php else: ?>
                                <em>Submitted. Awaiting Review.</em>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($is_due): ?>
                                <form action="submit_assignment.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                                    <input type="file" name="assignment_file" required>
                                    <button type="submit">Submit</button>
                                </form>
                            <?php else: ?>
                                <em>Due Date Passed</em>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No assignments found.</p>
    <?php endif; ?>
</div>

</body>
</html>

