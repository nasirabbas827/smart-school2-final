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

// Get student class ID
$stmt = $conn->prepare("SELECT class_id FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$class_id = $student ? $student['class_id'] : 0;

// Get subject name
$stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$subject_name = $sub ? $sub['subject_name'] : "Unknown Subject";

// Fetch materials for this subject
$materials_sql = "
    SELECT * FROM materials
    WHERE subject_id = ?
    ORDER BY uploaded_at DESC
";
$mat_stmt = $conn->prepare($materials_sql);
$mat_stmt->bind_param("i", $subject_id);
$mat_stmt->execute();
$materials = $mat_stmt->get_result();

// Fetch timetable entries for this subject & class
$timetable_sql = "
    SELECT * FROM timetables
    WHERE subject_id = ? AND class_id = ?
    ORDER BY day_of_week, start_time
";
$tt_stmt = $conn->prepare($timetable_sql);
$tt_stmt->bind_param("ii", $subject_id, $class_id);
$tt_stmt->execute();
$timetables = $tt_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Subject Details</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container">

    <h2><?php echo htmlspecialchars($subject_name); ?> - Details</h2>

    <!-- Section: Materials -->
    <h3>Materials</h3>
    <?php if ($materials->num_rows > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>File</th>
                <th>Uploaded By</th>
                <th>Uploaded At</th>
            </tr>
            <?php while ($row = $materials->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>
                        <?php if (!empty($row['file_path'])): ?>
                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">Download</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['uploaded_by']); ?></td>
                    <td><?php echo htmlspecialchars($row['uploaded_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No materials found for this subject.</p>
    <?php endif; ?>

    <!-- Section: Timetables -->
    <h3>Timetable</h3>
    <?php if ($timetables->num_rows > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th>Day</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Uploaded By</th>
            </tr>
            <?php while ($row = $timetables->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['day_of_week']); ?></td>
                    <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['uploaded_by']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No timetable entries for this subject.</p>
    <?php endif; ?>

    <!-- Section: Actions -->
    <h3>Actions</h3>
    <div style="margin: 30px;">
        <a href="upload_assignment.php?subject_id=<?php echo $subject_id; ?>" 
           style=" margin-left: 400px; display:inline-block; padding:10px 18px; background-color:#007bff; color:white; text-decoration:none; border-radius:5px; margin-right:10px;">
           Upload Assignment
        </a>

        <a href="attempt_quiz.php?subject_id=<?php echo $subject_id; ?>" 
           style="display:inline-block; padding:10px 18px; background-color:#28a745; color:white; text-decoration:none; border-radius:5px;">
           Attempt Quiz
        </a>
    </div>

</div>

</body>
</html>
