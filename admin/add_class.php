<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$successMsg = $errorMsg = "";

// Fetch teachers for dropdown
$teachers = mysqli_query($conn, "SELECT teacher_id, first_name, last_name FROM Teachers");

// Handle Add Class
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = trim($_POST["class_name"]);
    $teacher_id = !empty($_POST["teacher_id"]) ? intval($_POST["teacher_id"]) : NULL;

    $stmt = mysqli_prepare($conn, "INSERT INTO Classes (class_name, teacher_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "si", $class_name, $teacher_id);

    if (mysqli_stmt_execute($stmt)) {
        $successMsg = "Class added successfully.";
    } else {
        $errorMsg = "Error: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Class</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Add Class</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Class Name</label>
        <input type="text" name="class_name" required> 

        <label>Assign Teacher</label>
        <select name="teacher_id">
            <option value="">-- None --</option>
            <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $t['teacher_id']; ?>">
                    <?php echo htmlspecialchars($t['first_name'] . " " . $t['last_name']); ?>
                </option>
            <?php endwhile; ?>
        </select> 

        <input type="submit" value="Add Class">
        <a href="view_class.php">View Classes</a>
    </form>
</div>
</body>
</html>
