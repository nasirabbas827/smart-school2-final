<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$voucher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$successMsg = $errorMsg = "";

// Fetch students for dropdown
$students = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students");

// Fetch voucher details
$stmt = mysqli_prepare($conn, "SELECT * FROM fee_vouchers WHERE voucher_id = ?");
mysqli_stmt_bind_param($stmt, "i", $voucher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$voucher = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$voucher) {
    echo "Voucher not found.";
    exit();
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST["description"]);
    $month_year = trim($_POST["month_year"]);
    $student_id = intval($_POST["student_id"]);
    $amount = floatval($_POST["amount"]);
    $due_date = $_POST["due_date"];
    $status = $_POST["status"];

    if (!empty($description) && !empty($month_year) && !empty($student_id) && !empty($amount) && !empty($due_date)) {
        $update = mysqli_prepare($conn, "UPDATE fee_vouchers SET description=?, month_year=?, student_id=?, amount=?, due_date=?, status=? WHERE voucher_id=?");
        mysqli_stmt_bind_param($update, "sssssss", $description, $month_year, $student_id, $amount, $due_date, $status, $voucher_id);

        // Fix: correct binding string (s = string, i = int, d = double)
        // Correct order: s (desc), s (month), i (student), d (amount), s (due_date), s (status), i (voucher_id)
        mysqli_stmt_bind_param($update, "sssssss", $description, $month_year, $student_id, $amount, $due_date, $status, $voucher_id);

        if (mysqli_stmt_execute($update)) {
            $successMsg = "Voucher updated successfully.";
        } else {
            $errorMsg = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update);
    } else {
        $errorMsg = "All fields are required.";
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Fee Voucher</title>
    <link rel="stylesheet" href="../CSS/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Edit Fee Voucher</h2>
    <?php if ($successMsg) echo "<div class='success'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div class='error'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Description</label>
        <textarea name="description" rows="3" required><?php echo htmlspecialchars($voucher['description']); ?></textarea>

        <label>Month & Year</label>
        <input type="text" name="month_year" value="<?php echo htmlspecialchars($voucher['month_year']); ?>" required>

        <label>Select Student</label>
        <select name="student_id" required>
            <?php while ($s = mysqli_fetch_assoc($students)): ?>
                <option value="<?php echo $s['student_id']; ?>" <?php if ($s['student_id'] == $voucher['student_id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($s['first_name'] . " " . $s['last_name'] . " (" . $s['roll_number'] . ")"); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Amount</label>
        <input type="number" name="amount" step="0.01" value="<?php echo htmlspecialchars($voucher['amount']); ?>" required>

        <label>Due Date</label>
        <input type="date" name="due_date" value="<?php echo htmlspecialchars($voucher['due_date']); ?>" required>

        <label>Status</label>
        <select name="status" required>
            <option value="Pending" <?php if ($voucher['status'] == 'Pending') echo "selected"; ?>>Pending</option>
            <option value="Paid" <?php if ($voucher['status'] == 'Paid') echo "selected"; ?>>Paid</option>
            <option value="Overdue" <?php if ($voucher['status'] == 'Overdue') echo "selected"; ?>>Overdue</option>
        </select>

        <input type="submit" value="Update Voucher">
        <a href="view_vouchers.php">Back to List</a>
    </form>
</div>
</body>
</html>
