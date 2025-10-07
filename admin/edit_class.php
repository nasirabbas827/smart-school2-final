<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$class_id = intval($_GET['id']);
$successMsg = $errorMsg = "";

// Fetch class data
$stmt = mysqli_prepare($conn, "SELECT * FROM Classes WHERE class_id = ?");
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$class) {
    die("Class not found.");
}

// Fetch teachers for dropdown
$teachers = mysqli_query($conn, "SELECT teacher_id, first_name, last_name FROM Teachers");

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = trim($_POST["class_name"]);
    $teacher_id = !empty($_POST["teacher_id"]) ? intval($_POST["teacher_id"]) : NULL;

    $stmt = mysqli_prepare($conn, "UPDATE Classes SET class_name=?, teacher_id=? WHERE class_id=?");
    mysqli_stmt_bind_param($stmt, "sii", $class_name, $teacher_id, $class_id);

    if (mysqli_stmt_execute($stmt)) {
        $successMsg = "Class updated successfully.";
        $class['class_name'] = $class_name;
        $class['teacher_id'] = $teacher_id;
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
    <title>Edit Class</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Edit Class</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Class Name</label>
        <input type="text" name="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required> 

        <label>Assign Teacher</label>
        <select name="teacher_id">
            <option value="">-- None --</option>
            <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $t['teacher_id']; ?>" <?php if ($t['teacher_id'] == $class['teacher_id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($t['first_name'] . " " . $t['last_name']); ?>
                </option>
            <?php endwhile; ?>
        </select> 

        <input type="submit" value="Update Class">
        <a href="view_class.php">Back to Classes</a>
    </form>
</div>
</body>
</html>
