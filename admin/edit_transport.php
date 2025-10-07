<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$transport_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$successMsg = $errorMsg = "";

// Fetch classes
$classes = mysqli_query($conn, "SELECT class_id, class_name FROM Classes");

// Fetch transport record
$stmt = mysqli_prepare($conn, "SELECT * FROM Transport WHERE transport_id = ?");
mysqli_stmt_bind_param($stmt, "i", $transport_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transport = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$transport) {
    echo "Transport record not found.";
    exit();
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = intval($_POST["class_id"]);
    $bus_number = trim($_POST["bus_number"]);
    $driver_name = trim($_POST["driver_name"]);
    $driver_phone = trim($_POST["driver_phone"]);
    $route_details = trim($_POST["route_details"]);

    if (!empty($class_id) && !empty($bus_number) && !empty($driver_name) && !empty($driver_phone) && !empty($route_details)) {
        $stmt = mysqli_prepare($conn, "UPDATE Transport SET class_id=?, bus_number=?, driver_name=?, driver_phone=?, route_details=? WHERE transport_id=?");
        mysqli_stmt_bind_param($stmt, "issssi", $class_id, $bus_number, $driver_name, $driver_phone, $route_details, $transport_id);

        if (mysqli_stmt_execute($stmt)) {
            $successMsg = "Transport record updated successfully.";
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
    <title>Edit Transport</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Edit Transport</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Class</label>
        <select name="class_id" required>
            <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $c['class_id']; ?>" <?php if ($transport['class_id'] == $c['class_id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Bus Number</label>
        <input type="text" name="bus_number" value="<?php echo htmlspecialchars($transport['bus_number']); ?>" required>

        <label>Driver Name</label>
        <input type="text" name="driver_name" value="<?php echo htmlspecialchars($transport['driver_name']); ?>" required>

        <label>Driver Phone</label>
        <input type="text" name="driver_phone" value="<?php echo htmlspecialchars($transport['driver_phone']); ?>" required>

        <label>Route Details</label>
        <textarea name="route_details" rows="3" required><?php echo htmlspecialchars($transport['route_details']); ?></textarea>

        <input type="submit" value="Update Transport">
        <a href="view_transport.php">Back to List</a>
    </form>
</div>
</body>
</html>
