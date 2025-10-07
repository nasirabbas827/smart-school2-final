<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$successMsg = $errorMsg = "";

// Fetch students for dropdown
$students = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students");

// Handle Add Voucher
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST["description"]);
    $month_year = trim($_POST["month_year"]);
    $student_id = intval($_POST["student_id"]);
    $amount = floatval($_POST["amount"]);
    $due_date = $_POST["due_date"];
    $status = 'Pending'; // Default status

    if (!empty($description) && !empty($month_year) && !empty($student_id) && !empty($amount) && !empty($due_date)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO fee_vouchers (description, month_year, student_id, amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssss", $description, $month_year, $student_id, $amount, $due_date, $status);

        if (mysqli_stmt_execute($stmt)) {
            $successMsg = "Fee voucher created successfully.";
        } else {
            $errorMsg = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $errorMsg = "All fields are required.";
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Fee Voucher</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Create Fee Voucher</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Description</label>
        <textarea name="description" rows="3" required></textarea>

        <label>Month & Year</label>
        <input type="text" name="month_year" placeholder="e.g., October 2025" required>

        <label>Select Student</label>
        <select name="student_id" required>
            <option value="">-- Select Student --</option>
            <?php while ($s = mysqli_fetch_assoc($students)): ?>
                <option value="<?php echo $s['student_id']; ?>">
                    <?php echo htmlspecialchars($s['first_name'] . " " . $s['last_name'] . " (" . $s['roll_number'] . ")"); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Amount</label>
        <input type="number" name="amount" step="0.01" required>

        <label>Due Date</label>
        <input type="date" name="due_date" required>

        <input type="submit" value="Create Voucher">
        <a href="view_vouchers.php">View Vouchers</a>
    </form>
</div>
</body>
</html>
