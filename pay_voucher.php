<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];
$voucher_id = isset($_GET['voucher_id']) ? intval($_GET['voucher_id']) : 0;
$successMsg = $errorMsg = "";

// Fetch voucher details
$stmt = $conn->prepare("SELECT * FROM fee_vouchers WHERE voucher_id = ? AND student_id = ?");
$stmt->bind_param("ii", $voucher_id, $student_id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$voucher) {
    echo "Invalid voucher.";
    exit();
}

if ($voucher['status'] !== 'Pending') {
    echo "This voucher is already paid or not payable.";
    exit();
}

// Handle Payment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $method = trim($_POST["method"]);
    $paid_amount = floatval($_POST["paid_amount"]);
    $voucher_image = $_FILES["voucher_image"];

    if ($paid_amount <= 0) {
        $errorMsg = "Invalid payment amount.";
    } elseif (empty($method)) {
        $errorMsg = "Please select a payment method.";
    } elseif ($voucher_image["error"] != 0) {
        $errorMsg = "Please upload a valid voucher image.";
    } else {
        // Upload voucher image
        $target_dir = "./uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($voucher_image["name"]);
        $target_file = $target_dir . $file_name;

        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "pdf"];

        if (!in_array($file_type, $allowed_types)) {
            $errorMsg = "Only JPG, JPEG, PNG, or PDF files are allowed.";
        } elseif (move_uploaded_file($voucher_image["tmp_name"], $target_file)) {
            // Insert payment record
            $stmt = $conn->prepare("INSERT INTO payments (voucher_id, paid_amount, method, voucher_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $voucher_id, $paid_amount, $method, $file_name);

            if ($stmt->execute()) {
                // Update voucher status to Paid
                $update = $conn->prepare("UPDATE fee_vouchers SET status = 'Paid' WHERE voucher_id = ?");
                $update->bind_param("i", $voucher_id);
                $update->execute();
                $update->close();

                $successMsg = "Payment successful! Your voucher has been marked as Paid.";
            } else {
                $errorMsg = "Database error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errorMsg = "File upload failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pay Fee Voucher</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="card">
    <h2>Pay Voucher</h2>

    <?php if ($successMsg): ?>
        <div class="success"><?php echo $successMsg; ?></div>
        <a href="student_vouchers.php" class="back-btn">Back to Vouchers</a>
        <?php exit(); ?>
    <?php elseif ($errorMsg): ?>
        <div class="error"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <div class="details" style="text-align:center;">
        <p><strong>Description:</strong> <?php echo htmlspecialchars($voucher['description']); ?></p>
        <p><strong>Month & Year:</strong> <?php echo htmlspecialchars($voucher['month_year']); ?></p>
        <p><strong>Amount Due:</strong> <?php echo number_format($voucher['amount'], 2); ?></p>
        <p><strong>Due Date:</strong> <?php echo htmlspecialchars($voucher['due_date']); ?></p>
    </div>

    <form action="" method="post" enctype="multipart/form-data">
        <label>Payment Method</label>
        <select name="method" required>
            <option value="">-- Select Method --</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash Deposit">Cash Deposit</option>
            <option value="Online Payment">Online Payment</option>
            <option value="Easypaisa">Easypaisa</option>
            <option value="JazzCash">JazzCash</option>
        </select>

        <label>Paid Amount</label>
        <input type="number" name="paid_amount" step="0.01" value="<?php echo htmlspecialchars($voucher['amount']); ?>" required>

        <label>Upload Voucher Image / Receipt</label>
        <input type="file" name="voucher_image" accept=".jpg,.jpeg,.png,.pdf" required>

        <input type="submit" value="Submit Payment">
    </form>
</div>
</body>
</html>
