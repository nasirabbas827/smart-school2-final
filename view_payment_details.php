<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['voucher_id'])) {
    header("Location: view_vouchers_student.php");
    exit();
}

$voucher_id = intval($_GET['voucher_id']);

// Fetch payment details
$sql = "SELECT * FROM payments WHERE voucher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $voucher_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Details</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="card">
    <h2>Payment Details</h2>

    <?php if ($payment): ?>
        <table>
            <tr><th>Payment ID</th><td><?php echo $payment['payment_id']; ?></td></tr>
            <tr><th>Voucher ID</th><td><?php echo $payment['voucher_id']; ?></td></tr>
            <tr><th>Paid Amount</th><td><?php echo number_format($payment['paid_amount'], 2); ?></td></tr>
            <tr><th>Payment Date</th><td><?php echo htmlspecialchars($payment['payment_date']); ?></td></tr>
            <tr><th>Method</th><td><?php echo htmlspecialchars($payment['method']); ?></td></tr>
            <tr><th>Voucher Image</th>
                <td>
                    <?php if (!empty($payment['voucher_image'])): ?>
                        <a href="uploads/<?php echo htmlspecialchars($payment['voucher_image']); ?>" target="_blank">View Uploaded Voucher</a>
                    <?php else: ?>
                        No Image Uploaded
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    <?php else: ?>
        <p>No payment record found for this voucher.</p>
    <?php endif; ?>

</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
