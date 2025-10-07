<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

if (!isset($_GET['voucher_id'])) {
    header("Location: view_vouchers.php");
    exit();
}

$voucher_id = intval($_GET['voucher_id']);

// Fetch voucher details
$voucher_sql = "
    SELECT v.voucher_id, v.description, v.amount, v.status, v.month_year, 
           s.first_name, s.last_name, s.roll_number
    FROM fee_vouchers v
    INNER JOIN students s ON v.student_id = s.student_id
    WHERE v.voucher_id = ?
";
$stmt = $conn->prepare($voucher_sql);
$stmt->bind_param("i", $voucher_id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();

// Fetch payment details
$payment_sql = "SELECT * FROM payments WHERE voucher_id = ?";
$stmt2 = $conn->prepare($payment_sql);
$stmt2->bind_param("i", $voucher_id);
$stmt2->execute();
$payment = $stmt2->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Details</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Payment Details for Voucher ID: <?php echo htmlspecialchars($voucher_id); ?></h2>

    <?php if ($voucher): ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <tr><th>Student Name</th><td><?php echo htmlspecialchars($voucher['first_name'] . ' ' . $voucher['last_name']); ?></td></tr>
            <tr><th>Roll Number</th><td><?php echo htmlspecialchars($voucher['roll_number']); ?></td></tr>
            <tr><th>Description</th><td><?php echo htmlspecialchars($voucher['description']); ?></td></tr>
            <tr><th>Month & Year</th><td><?php echo htmlspecialchars($voucher['month_year']); ?></td></tr>
            <tr><th>Amount</th><td><?php echo number_format($voucher['amount'], 2); ?></td></tr>
            <tr><th>Status</th><td><?php echo htmlspecialchars($voucher['status']); ?></td></tr>
        </table>

        <h3>Payment Information</h3>
        <?php if ($payment): ?>
            <table border="1" cellpadding="8" cellspacing="0">
                <tr><th>Payment ID</th><td><?php echo htmlspecialchars($payment['payment_id']); ?></td></tr>
                <tr><th>Paid Amount</th><td><?php echo number_format($payment['paid_amount'], 2); ?></td></tr>
                <tr><th>Payment Date</th><td><?php echo htmlspecialchars($payment['payment_date']); ?></td></tr>
                <tr><th>Method</th><td><?php echo htmlspecialchars($payment['method']); ?></td></tr>
                <tr>
                    <th>Voucher Image</th>
                    <td>
                        <?php if (!empty($payment['voucher_image'])): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($payment['voucher_image']); ?>" target="_blank">View Image</a>
                        <?php else: ?>
                            No Image Uploaded
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        <?php else: ?>
            <p>No payment record found for this voucher.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Voucher not found.</p>
    <?php endif; ?>

    <a href="view_vouchers.php">Back to Voucher List</a>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
