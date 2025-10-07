<?php
include('../config.php');
session_start();

// Allow only teachers
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

// Validate subject_id from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_subjects.php");
    exit();
}

$subject_id = intval($_GET['id']);

// Fetch subject (make sure teacher owns it)
$sql = "SELECT subject_name, class_id FROM subjects WHERE subject_id = ? AND teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $subject_id, $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($result);

if (!$subject) {
    header("Location: view_subjects.php");
    exit();
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject_name = $_POST['subject_name'];

    $update_sql = "UPDATE subjects SET subject_name = ? WHERE subject_id = ? AND teacher_id = ?";
    $stmt2 = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt2, "sii", $subject_name, $subject_id, $teacher_id);

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Subject updated successfully.";
        // Refresh data
        $subject['subject_name'] = $subject_name;
    } else {
        $message = "Error updating subject: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Subject</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Subject</h2>

<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<form method="post">
    <label>Subject Name:</label>
    <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
     

    <button type="submit">Update Subject</button>
    <a href="view_subjects.php">Back</a>
</form>
</body>
</html>
