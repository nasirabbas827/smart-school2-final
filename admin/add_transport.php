<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$successMsg = $errorMsg = "";

// Fetch classes for dropdown
$classes = mysqli_query($conn, "SELECT class_id, class_name FROM Classes");

// Handle Add Transport
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = intval($_POST["class_id"]);
    $bus_number = trim($_POST["bus_number"]);
    $driver_name = trim($_POST["driver_name"]);
    $driver_phone = trim($_POST["driver_phone"]);
    $route_details = trim($_POST["route_details"]);

    if (!empty($class_id) && !empty($bus_number) && !empty($driver_name) && !empty($driver_phone) && !empty($route_details)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO Transport (class_id, bus_number, driver_name, driver_phone, route_details) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $class_id, $bus_number, $driver_name, $driver_phone, $route_details);

        if (mysqli_stmt_execute($stmt)) {
            $successMsg = "Transport record added successfully.";
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
    <title>Add Transport</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Add Transport</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Class</label>
        <select name="class_id" required>
            <option value="">-- Select Class --</option>
            <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $c['class_id']; ?>">
                    <?php echo htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Bus Number</label>
        <input type="text" name="bus_number" required>

        <label>Driver Name</label>
        <input type="text" name="driver_name" required>

        <label>Driver Phone</label>
        <input type="text" name="driver_phone" required>

        <label>Route Details</label>
        <textarea name="route_details" rows="3" required></textarea>

        <input type="submit" value="Add Transport">
        <a href="view_transport.php">View Transport</a>
    </form>
</div>
</body>
</html>
