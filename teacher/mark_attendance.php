<?php
// mark_attendance.php
include('../config.php');
session_start();

// Allow only teachers
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

// Fetch teacher's class
$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && $class_id) {
    $date = date("Y-m-d");

    foreach ($_POST['attendance'] as $student_id => $status) {
        // Check if attendance already exists for this student and date
        $check_sql = "SELECT * FROM Attendance WHERE student_id = ? AND class_id = ? AND date = ?";
        $stmt_check = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt_check, "iis", $student_id, $class_id, $date);
        mysqli_stmt_execute($stmt_check);
        $check_result = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($check_result) == 0) {
            // Insert new attendance record
            $insert_sql = "INSERT INTO Attendance (student_id, class_id, date, status, marked_by) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt_insert, "iissi", $student_id, $class_id, $date, $status, $teacher_id);
            mysqli_stmt_execute($stmt_insert);
        }
    }
    $message = "Attendance marked successfully for " . $date;
}

// Fetch students of teacher's class
$students = [];
if ($class_id) {
    $student_sql = "SELECT student_id, first_name, last_name, roll_number 
                    FROM Students WHERE class_id = ?";
    $stmt2 = mysqli_prepare($conn, $student_sql);
    mysqli_stmt_bind_param($stmt2, "i", $class_id);
    mysqli_stmt_execute($stmt2);
    $students = mysqli_stmt_get_result($stmt2);
}

// Fetch attendance records (last 10 days for this class)
$attendance_records = [];
if ($class_id) {
    $att_sql = "SELECT a.date, s.roll_number, s.first_name, s.last_name, a.status
                FROM Attendance a
                JOIN Students s ON a.student_id = s.student_id
                WHERE a.class_id = ?
                ORDER BY a.date DESC, s.roll_number ASC
                LIMIT 100";
    $stmt3 = mysqli_prepare($conn, $att_sql);
    mysqli_stmt_bind_param($stmt3, "i", $class_id);
    mysqli_stmt_execute($stmt3);
    $attendance_records = mysqli_stmt_get_result($stmt3);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mark & View Attendance</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin-bottom: 25px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .status-options label {
            margin-right: 15px;
            padding: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .status-present input, .status-absent input, .status-late input { display: none; }

        .status-present { background: #e6ffe6; border: 1px solid #33cc33; }
        .status-absent { background: #ffe6e6; border: 1px solid #ff3333; }
        .status-late { background: #fff5e6; border: 1px solid #ff9933; }

        input[type="radio"]:checked + span {
            font-weight: bold;
            text-decoration: underline;
        }
        .att-status {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .att-Present { background: #d4edda; color: #155724; }
        .att-Absent { background: #f8d7da; color: #721c24; }
        .att-Late { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <h2>Mark Attendance</h2>
    <?php if ($class): ?>
        <p><strong>Class:</strong> <?php echo htmlspecialchars($class['class_name']); ?></p>
    <?php else: ?>
        <p>You are not assigned to any class yet.</p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p style="color: green;"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Attendance Form -->
    <?php if ($class && mysqli_num_rows($students) > 0): ?>
        <form method="post">
            <table>
                <tr>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Status</th>
                </tr>
                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></td>
                        <td class="status-options">
                            <label class="status-present">
                                <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="Present" required>
                                <span>Present</span>
                            </label>
                            <label class="status-absent">
                                <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="Absent">
                                <span>Absent</span>
                            </label>
                            <label class="status-late">
                                <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="Late">
                                <span>Late</span>
                            </label>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <input type="submit" value="Submit Attendance">
        </form>
    <?php endif; ?>

    <!-- Attendance Records -->
    <h2>View Attendance Records</h2>
    <?php if ($attendance_records && mysqli_num_rows($attendance_records) > 0): ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Roll No</th>
                <th>Name</th>
                <th>Status</th>
            </tr>
            <?php while ($record = mysqli_fetch_assoc($attendance_records)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['date']); ?></td>
                    <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                    <td><?php echo htmlspecialchars($record['first_name'] . " " . $record['last_name']); ?></td>
                    <td><span class="att-status att-<?php echo $record['status']; ?>"><?php echo $record['status']; ?></span></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No attendance records found yet.</p>
    <?php endif; ?>
</body>
</html>
