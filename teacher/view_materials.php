<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];

// Get teacher class
$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

// Handle Delete
if (isset($_GET['delete'])) {
    $material_id = intval($_GET['delete']);
    $del_sql = "DELETE FROM Materials WHERE material_id = ? AND uploaded_by = ?";
    $stmt_del = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt_del, "ii", $material_id, $teacher_id);
    mysqli_stmt_execute($stmt_del);
}

$materials = [];
if ($class_id) {
    $query = "
        SELECT m.material_id, m.title, m.description, m.file_path, m.uploaded_at,
               s.subject_name
        FROM Materials m
        LEFT JOIN subjects s ON m.subject_id = s.subject_id
        WHERE m.class_id = ?
        ORDER BY m.uploaded_at DESC
    ";
    $stmt2 = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt2, "i", $class_id);
    mysqli_stmt_execute($stmt2);
    $materials = mysqli_stmt_get_result($stmt2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Materials</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Study Materials</h2>

<?php if ($class_id): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>Subject</th>
        <th>Title</th>
        <th>Description</th>
        <th>File</th>
        <th>Uploaded At</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($materials)): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars($row['description']); ?></td>
        <td><a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">View</a></td>
        <td><?php echo $row['uploaded_at']; ?></td>
        <td>
            <a href="edit_material.php?id=<?php echo $row['material_id']; ?>">Edit</a> | 
            <a href="?delete=<?php echo $row['material_id']; ?>" onclick="return confirm('Delete this material?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>You are not assigned to any class.</p>
<?php endif; ?>
</body>
</html>
