<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch student details
$student_query = "SELECT s.*, c.class_name 
                 FROM Students s 
                 LEFT JOIN Classes c ON s.class_id = c.class_id 
                 WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student_result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($student_result);
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: view_student.php");
    exit();
}

// Fetch performance (grades)
$grades_query = "SELECT g.*, s.subject_name 
                 FROM grades g 
                 LEFT JOIN subjects s ON g.subject_id = s.subject_id 
                 WHERE g.student_id = ? 
                 ORDER BY g.entered_at DESC";
$stmt = mysqli_prepare($conn, $grades_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$grades_result = mysqli_stmt_get_result($stmt);
$grades = mysqli_fetch_all($grades_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Fetch attendance
$attendance_query = "SELECT date, status 
                    FROM attendance 
                    WHERE student_id = ? 
                    ORDER BY date DESC 
                    LIMIT 10";
$stmt = mysqli_prepare($conn, $attendance_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);
$attendance = mysqli_fetch_all($attendance_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Fetch financial records
$financial_query = "SELECT fv.*, p.payment_date, p.paid_amount, p.method 
                   FROM fee_vouchers fv 
                   LEFT JOIN payments p ON fv.voucher_id = p.voucher_id 
                   WHERE fv.student_id = ? 
                   ORDER BY fv.generated_at DESC";
$stmt = mysqli_prepare($conn, $financial_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$financial_result = mysqli_stmt_get_result($stmt);
$financials = mysqli_fetch_all($financial_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Report - <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></title>
    <link rel="stylesheet" href="../CSS/style.css">
   </head>
<body>
<?php include('navbar.php'); ?>

<div style="text-align: center;">
    <h2>Student Report: <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></h2>
    <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
    <p><strong>Class:</strong> <?php echo $student['class_name'] ? htmlspecialchars($student['class_name']) : "None"; ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>

    <div class="report-section">
        <h3>Performance (Grades)</h3>
        <?php if (count($grades) > 0): ?>
            <table>
                <tr>
                    <th>Subject</th>
                    <th>Exam Type</th>
                    <th>Term</th>
                    <th>Marks Obtained</th>
                    <th>Max Marks</th>
                    <th>Grade</th>
                    <th>Comments</th>
                </tr>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grade['subject_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($grade['exam_type']); ?></td>
                        <td><?php echo htmlspecialchars($grade['term']); ?></td>
                        <td><?php echo htmlspecialchars($grade['marks_obtained']); ?></td>
                        <td><?php echo htmlspecialchars($grade['max_marks']); ?></td>
                        <td><?php echo htmlspecialchars($grade['grade'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($grade['comments'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No grades recorded.</p>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <h3>Attendance (Last 10 Records)</h3>
        <?php if (count($attendance) > 0): ?>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['date']); ?></td>
                        <td><?php echo htmlspecialchars($record['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No attendance records found.</p>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <h3>Financial Records</h3>
        <?php if (count($financials) > 0): ?>
            <table>
                <tr>
                    <th>Description</th>
                    <th>Month/Year</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Paid Amount</th>
                    <th>Payment Method</th>
                </tr>
                <?php foreach ($financials as $financial): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($financial['description']); ?></td>
                        <td><?php echo htmlspecialchars($financial['month_year']); ?></td>
                        <td><?php echo htmlspecialchars($financial['amount']); ?></td>
                        <td><?php echo htmlspecialchars($financial['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($financial['status']); ?></td>
                        <td><?php echo $financial['payment_date'] ? htmlspecialchars($financial['payment_date']) : 'N/A'; ?></td>
                        <td><?php echo $financial['paid_amount'] ? htmlspecialchars($financial['paid_amount']) : 'N/A'; ?></td>
                        <td><?php echo $financial['method'] ? htmlspecialchars($financial['method']) : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No financial records found.</p>
        <?php endif; ?>
    </div>

    <a href="view_student.php">Back to Students List</a>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>