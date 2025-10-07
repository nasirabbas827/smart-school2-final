<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

/* ========== ADD LEAVE APPLICATION ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $reason = $_POST['reason'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if ($reason && $start_date && $end_date) {
        $stmt = $conn->prepare("INSERT INTO leave_applications (student_id, reason, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $student_id, $reason, $start_date, $end_date);
        $stmt->execute();
    }
}

/* ========== DELETE LEAVE (ONLY IF PENDING) ========== */
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $check = $conn->prepare("SELECT leave_id FROM leave_applications WHERE leave_id = ? AND student_id = ? AND status = 'Pending'");
    $check->bind_param("ii", $delete_id, $student_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $del_stmt = $conn->prepare("DELETE FROM leave_applications WHERE leave_id = ?");
        $del_stmt->bind_param("i", $delete_id);
        $del_stmt->execute();
    }
}

/* ========== FETCH ALL LEAVES FOR THIS STUDENT ========== */
$leaves_stmt = $conn->prepare("SELECT leave_id, reason, start_date, end_date, status, applied_at FROM leave_applications WHERE student_id = ? ORDER BY applied_at DESC");
$leaves_stmt->bind_param("i", $student_id);
$leaves_stmt->execute();
$leaves = $leaves_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Leave Applications</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<h2>Apply for Leave</h2>
<form action="" method="POST">
    <label>Reason:</label> 
    <textarea name="reason" required></textarea> 

    <label>Start Date:</label> 
    <input type="date" name="start_date" required> 

    <label>End Date:</label> 
    <input type="date" name="end_date" required> 

    <button type="submit" name="apply_leave">Submit Application</button>
</form>


<h2>Your Leave Applications</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Reason</th>
        <th>Start</th>
        <th>End</th>
        <th>Status</th>
        <th>Applied At</th>
        <th>Action</th>
    </tr>
    <?php if ($leaves->num_rows > 0): ?>
        <?php while ($row = $leaves->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['applied_at']); ?></td>
                <td>
                    <?php if ($row['status'] === 'Pending'): ?>
                        <a href="?delete_id=<?php echo $row['leave_id']; ?>" onclick="return confirm('Delete this application?');">Delete</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6">No leave applications found.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>
