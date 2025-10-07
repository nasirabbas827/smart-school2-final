<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$successMsg = "";

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete = mysqli_query($conn, "DELETE FROM Transport WHERE transport_id = $delete_id");
    if ($delete) {
        $successMsg = "Record deleted successfully.";
    }
}

// Fetch transport records
$sql = "
    SELECT t.transport_id, c.class_name, t.bus_number, t.driver_name, t.driver_phone, t.route_details
    FROM Transport t
    JOIN Classes c ON t.class_id = c.class_id
    ORDER BY t.transport_id DESC
";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Transport</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Transport Records</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>

    <a href="add_transport.php">Add New Transport</a>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Class</th>
            <th>Bus Number</th>
            <th>Driver Name</th>
            <th>Driver Phone</th>
            <th>Route Details</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo $row['transport_id']; ?></td>
            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
            <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
            <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
            <td><?php echo htmlspecialchars($row['driver_phone']); ?></td>
            <td><?php echo htmlspecialchars($row['route_details']); ?></td>
            <td>
                <a href="edit_transport.php?id=<?php echo $row['transport_id']; ?>">Edit</a> |
                <a href="?delete_id=<?php echo $row['transport_id']; ?>" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
