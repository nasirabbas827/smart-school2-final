<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

// Fetch student grades with subject names
$sql = "
    SELECT g.grade_id, g.exam_type, g.term, g.marks_obtained, g.max_marks, 
           g.grade, g.comments, g.entered_at,
           s.subject_name
    FROM grades g
    LEFT JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.student_id = ?
    ORDER BY g.entered_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Grades</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="container">
        <h2>Your Grades</h2>
        <a href="#" onclick="window.print();" class="print-btn">Print Grades</a>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Subject</th>
                    <th>Exam Type</th>
                    <th>Term</th>
                    <th>Marks Obtained</th>
                    <th>Max Marks</th>
                    <th>Grade</th>
                    <th>Comments</th>
                    <th>Date</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['subject_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['term']); ?></td>
                        <td><?php echo htmlspecialchars($row['marks_obtained']); ?></td>
                        <td><?php echo htmlspecialchars($row['max_marks']); ?></td>
                        <td><?php echo htmlspecialchars($row['grade']); ?></td>
                        <td><?php echo htmlspecialchars($row['comments']); ?></td>
                        <td><?php echo htmlspecialchars($row['entered_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="no-data">No grades found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
