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

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $email = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $roll_number = trim($_POST["roll_number"]);
    $class_id = !empty($_POST["class_id"]) ? intval($_POST["class_id"]) : NULL;
    $dob = !empty($_POST["date_of_birth"]) ? $_POST["date_of_birth"] : NULL;
    $phone = trim($_POST["phone_number"]);

    $stmt = mysqli_prepare($conn, "INSERT INTO Students (username, password, email, first_name, last_name, roll_number, class_id, date_of_birth, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssssiss", $username, $password, $email, $first_name, $last_name, $roll_number, $class_id, $dob, $phone);

    if (mysqli_stmt_execute($stmt)) {
        $successMsg = "Student added successfully.";
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
    <title>Add Student</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Add Student</h2>
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

        <label>Roll Number</label>
        <input type="text" name="roll_number" required> 

        <label>Class</label>
        <select name="class_id">
            <option value="">-- None --</option>
            <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $c['class_id']; ?>">
                    <?php echo htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endwhile; ?>
        </select> 

        <label>Date of Birth</label>
        <input type="date" name="date_of_birth"> 

        <label>Phone Number</label>
        <input type="text" name="phone_number"> 

        <input type="submit" value="Add Student">
        <a href="view_student.php">View Students</a>
    </form>
</div>
</body>
</html>
