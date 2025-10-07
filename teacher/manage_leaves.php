<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];

// Handle status update
if (isset($_POST['leave_id']) && isset($_POST['status'])) {
    $leave_id = intval($_POST['leave_id']);
    $status = $_POST['status'];

    if (in_array($status, ['Approved', 'Rejected'])) {
        $update_sql = "
            UPDATE leave_applications
            SET status = ?, reviewed_by = ?
            WHERE leave_id = ?
        ";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sii", $status, $teacher_id, $leave_id);
        mysqli_stmt_execute($stmt);
    }
}

// Fetch all leave applications
$query = "
    SELECT la.leave_id, la.reason, la.start_date, la.end_date, la.status, la.applied_at,
           stu.first_name AS student_name
    FROM leave_applications la
    LEFT JOIN students stu ON la.student_id = stu.student_id
    ORDER BY la.applied_at DESC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Leave Applications</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<h2>Leave Applications</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Student</th>
        <th>Reason</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Applied At</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
        <td><?php echo htmlspecialchars($row['reason']); ?></td>
        <td><?php echo $row['start_date']; ?></td>
        <td><?php echo $row['end_date']; ?></td>
        <td><?php echo $row['applied_at']; ?></td>
        <td><?php echo $row['status']; ?></td>
        <td>
            <?php if ($row['status'] === 'Pending'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="leave_id" value="<?php echo $row['leave_id']; ?>">
                    <input type="hidden" name="status" value="Approved">
                    <button type="submit">Approve</button>
                </form>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="leave_id" value="<?php echo $row['leave_id']; ?>">
                    <input type="hidden" name="status" value="Rejected">
                    <button type="submit">Reject</button>
                </form>
            <?php else: ?>
                <?php echo $row['status']; ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
