<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$teacher_id = intval($_GET['id']);
$successMsg = $errorMsg = "";

// Fetch teacher data
$stmt = mysqli_prepare($conn, "SELECT * FROM Teachers WHERE teacher_id = ?");
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$teacher = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$teacher) {
    die("Teacher not found.");
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST["username"]);
    $email      = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $last_name  = trim($_POST["last_name"]);
    $department = trim($_POST["department"]);
    $phone      = trim($_POST["phone_number"]);

    $stmt = mysqli_prepare($conn, "UPDATE Teachers SET username=?, email=?, first_name=?, last_name=?, department=?, phone_number=? WHERE teacher_id=?");
    mysqli_stmt_bind_param($stmt, "ssssssi", $username, $email, $first_name, $last_name, $department, $phone, $teacher_id);

    if (mysqli_stmt_execute($stmt)) {
        $successMsg = "Teacher updated successfully.";
        // Refresh teacher data
        $teacher['username'] = $username;
        $teacher['email'] = $email;
        $teacher['first_name'] = $first_name;
        $teacher['last_name'] = $last_name;
        $teacher['department'] = $department;
        $teacher['phone_number'] = $phone;
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
    <title>Edit Teacher</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Edit Teacher</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($teacher['username']); ?>" required> 

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required> 

        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required> 

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required> 

        <label>Department</label>
        <input type="text" name="department" value="<?php echo htmlspecialchars($teacher['department']); ?>"> 

        <label>Phone Number</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($teacher['phone_number']); ?>"> 

        <input type="submit" value="Update Teacher">
        <a href="view_teacher.php">Back to Teachers</a>
    </form>
</div>
</body>
</html>
