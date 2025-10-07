<?php
session_start();
include('config.php');

// Ensure only students can access
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION["id"];
$successMsg = $errorMsg = "";

// Fetch current student details
$sql = "SELECT username, email, first_name, last_name, roll_number, class_id, date_of_birth, phone_number 
        FROM Students WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If student not found
if (!$student) {
    die("Student not found.");
}

// Update profile on form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $phone_number = trim($_POST["phone_number"]);
    $date_of_birth = trim($_POST["date_of_birth"]);

    if (empty($username) || empty($first_name) || empty($last_name)) {
        $errorMsg = "Username, First Name, and Last Name are required.";
    } else {
        $update_sql = "UPDATE Students 
                       SET username=?, first_name=?, last_name=?, phone_number=?, date_of_birth=?, updated_at=NOW()
                       WHERE student_id=?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $username, $first_name, $last_name, $phone_number, $date_of_birth, $student_id);

        if (mysqli_stmt_execute($stmt)) {
            $successMsg = "Profile updated successfully!";
            // Refresh student info
            $student['username'] = $username;
            $student['first_name'] = $first_name;
            $student['last_name'] = $last_name;
            $student['phone_number'] = $phone_number;
            $student['date_of_birth'] = $date_of_birth;
        } else {
            $errorMsg = "Error updating profile. Try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
    <?php include('navbar.php'); ?>

    <h2>Edit Profile</h2>

    <?php if (!empty($successMsg)) echo "<p style='color:green;'>$successMsg</p>"; ?>
    <?php if (!empty($errorMsg)) echo "<p style='color:red;'>$errorMsg</p>"; ?>

    <form method="post" action="">
        <div>
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
        </div>
        <div>
            <label>Email (cannot change)</label>
            <input type="email" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
        </div>
        <div>
            <label>First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
        </div>
        <div>
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
        </div>
        <div>
            <label>Roll Number (cannot change)</label>
            <input type="text" value="<?php echo htmlspecialchars($student['roll_number']); ?>" disabled>
        </div>
        <div>
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
        </div>
        <div>
            <label>Phone Number</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number']); ?>">
        </div>
        <div>
            <input type="submit" value="Update Profile">
        </div>
    </form>
</body>
</html>
