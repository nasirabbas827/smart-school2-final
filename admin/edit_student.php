<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$student_id = intval($_GET['id']);
$successMsg = $errorMsg = "";

// Fetch student
$stmt = mysqli_prepare($conn, "SELECT * FROM Students WHERE student_id=?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    die("Student not found.");
}

// Fetch classes
$classes = mysqli_query($conn, "SELECT class_id, class_name FROM Classes");

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $roll_number = trim($_POST["roll_number"]);
    $class_id = !empty($_POST["class_id"]) ? intval($_POST["class_id"]) : NULL;
    $dob = !empty($_POST["date_of_birth"]) ? $_POST["date_of_birth"] : NULL;
    $phone = trim($_POST["phone_number"]);

    // Only update password if entered
    if (!empty($_POST["password"])) {
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE Students SET email=?, first_name=?, last_name=?, roll_number=?, class_id=?, date_of_birth=?, phone_number=?, password=? WHERE student_id=?");
        mysqli_stmt_bind_param($stmt, "ssssisssi", $email, $first_name, $last_name, $roll_number, $class_id, $dob, $phone, $password, $student_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE Students SET email=?, first_name=?, last_name=?, roll_number=?, class_id=?, date_of_birth=?, phone_number=? WHERE student_id=?");
        mysqli_stmt_bind_param($stmt, "ssssissi", $email, $first_name, $last_name, $roll_number, $class_id, $dob, $phone, $student_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $successMsg = "Student updated successfully.";
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
    <title>Edit Student</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div>
    <h2>Edit Student</h2>
    <?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
    <?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

    <form action="" method="post">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required> 

        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required> 

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required> 

        <label>Roll Number</label>
        <input type="text" name="roll_number" value="<?php echo htmlspecialchars($student['roll_number']); ?>" required> 

        <label>Class</label>
        <select name="class_id">
            <option value="">-- None --</option>
            <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $c['class_id']; ?>" <?php if ($c['class_id'] == $student['class_id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endwhile; ?>
        </select> 

        <label>Date of Birth</label>
        <input type="date" name="date_of_birth" value="<?php echo $student['date_of_birth']; ?>"> 

        <label>Phone Number</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number']); ?>"> 

        <label>New Password (leave blank to keep old)</label>
        <input type="password" name="password"> 

        <input type="submit" value="Update Student">
        <a href="view_student.php">Back to Students</a>
    </form>
</div>
</body>
</html>
