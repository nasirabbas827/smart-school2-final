<?php
session_start();
include('config.php');

// Check if student is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

// Fetch student's class info
$student_sql = "
    SELECT s.student_id, s.first_name, s.last_name, s.class_id, 
           c.class_name
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.class_id
    WHERE s.student_id = ?
";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();

$class_id = $student_info['class_id'] ?? null;
$class_name = $student_info['class_name'] ?? "N/A";

// Fetch transport details for student's class
$transport_result = null;
if ($class_id) {
    $transport_sql = "
        SELECT t.transport_id, t.bus_number, t.driver_name, t.driver_phone, t.route_details 
        FROM transport t
        WHERE t.class_id = ?
    ";
    $stmt2 = $conn->prepare($transport_sql);
    $stmt2->bind_param("i", $class_id);
    $stmt2->execute();
    $transport_result = $stmt2->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Transport</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <h2>Transport Details for Class: <?php echo htmlspecialchars($class_name); ?></h2>

    <?php if ($transport_result && $transport_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Bus Number</th>
                <th>Driver Name</th>
                <th>Driver Phone</th>
                <th>Route Details</th>
            </tr>
            <?php while ($t = $transport_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['transport_id']); ?></td>
                    <td><?php echo htmlspecialchars($t['bus_number']); ?></td>
                    <td><?php echo htmlspecialchars($t['driver_name']); ?></td>
                    <td><?php echo htmlspecialchars($t['driver_phone']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($t['route_details'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p class="no-data">No transport information available for your class.</p>
    <?php endif; ?>

</div>
</body>
</html>
<?php mysqli_close($conn); ?>
