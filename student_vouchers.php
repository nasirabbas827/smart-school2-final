<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

// Fetch student's vouchers
$sql = "
    SELECT v.voucher_id, v.description, v.month_year, v.amount, v.due_date, v.status, v.generated_at
    FROM fee_vouchers v
    WHERE v.student_id = ?
    ORDER BY v.generated_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$vouchers = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Fee Vouchers</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="card">
    <h2>Your Fee Vouchers</h2>

    <?php if ($vouchers->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Month & Year</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Generated At</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $vouchers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['voucher_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                    <td><?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                    <td class="status-<?php echo strtolower($row['status']); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['generated_at']); ?></td>
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <a class="pay-btn" href="pay_voucher.php?voucher_id=<?php echo $row['voucher_id']; ?>">Pay Now</a>
                        <?php elseif ($row['status'] === 'Paid'): ?>
                            <a class="view-btn" href="view_payment_details.php?voucher_id=<?php echo $row['voucher_id']; ?>">View Payment Details</a>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No fee vouchers found.</p>
    <?php endif; ?>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
