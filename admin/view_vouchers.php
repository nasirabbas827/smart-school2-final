<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $voucher_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM fee_vouchers WHERE voucher_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $voucher_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: view_vouchers.php");
    exit();
}

// Fetch all vouchers with student details
$query = "
    SELECT v.voucher_id, v.description, v.month_year, v.amount, v.due_date, v.status, v.generated_at,
           s.first_name, s.last_name, s.roll_number
    FROM fee_vouchers v
    INNER JOIN students s ON v.student_id = s.student_id
    ORDER BY v.voucher_id DESC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Fee Vouchers</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Fee Vouchers List</h2>
    <a href="add_voucher.php">+ Add New Voucher</a>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Roll No</th>
            <th>Month & Year</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Generated At</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['voucher_id']; ?></td>
                <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['generated_at']); ?></td>
                <td>
                    <a href="edit_voucher.php?id=<?php echo $row['voucher_id']; ?>">Edit</a> |
                    <a href="view_vouchers.php?delete=<?php echo $row['voucher_id']; ?>" onclick="return confirm('Delete this voucher?');">Delete</a>
                    <?php if ($row['status'] === 'Paid'): ?>
                        | <a href="view_payment_details.php?voucher_id=<?php echo $row['voucher_id']; ?>">View Payment Details</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
