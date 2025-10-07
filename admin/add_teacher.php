<?php
session_start();
include "config.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$successMsg = $errorMsg = "";

// Handle Add Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST["username"]);
    $password   = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT); // hash password
    $email      = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $last_name  = trim($_POST["last_name"]);
    $department = trim($_POST["department"]);
    $phone      = trim($_POST["phone_number"]);

    $stmt = mysqli_prepare($conn, "INSERT INTO Teachers 
        (username, password, email, first_name, last_name, department, phone_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "sssssss", $username, $password, $email, $first_name, $last_name, $department, $phone);

    if (mysqli_stmt_execute($stmt)) {
        $successMsg = "Teacher added successfully.";
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
    <title>Add Teacher</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Add Teacher</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Username</label>
        <input type="text" name="username" required> 

        <label>Password</label>
        <input type="password" name="password" required> 

        <label>Email</label>
        <input type="email" name="email" required> 

        <label>First Name</label>
        <input type="text" name="first_name" required> 

        <label>Last Name</label>
        <input type="text" name="last_name" required> 

        <label>Department</label>
        <input type="text" name="department"> 

        <label>Phone Number</label>
        <input type="text" name="phone_number"> 

        <input type="submit" value="Add Teacher">
        <a href="view_teacher.php">View Teachers</a>
    </form>
</div>
</body>
</html>
