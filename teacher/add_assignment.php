<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

$subjects = [];
if ($class_id) {
    $sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
    $stmt_sub = mysqli_prepare($conn, $sub_sql);
    mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
    mysqli_stmt_execute($stmt_sub);
    $subjects = mysqli_stmt_get_result($stmt_sub);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $class_id) {
    $subject_id = intval($_POST['subject_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $upload_dir = "../uploads/";
    $file_path = null;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['assignment_file']['name'])) {
        $file_name = time() . "_" . basename($_FILES['assignment_file']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
            $file_path = "uploads/" . $file_name;
        }
    }

    $insert = "INSERT INTO assignments (class_id, subject_id, title, description, due_date, file_path, created_by)
               VALUES (?,?,?,?,?,?,?)";
    $stmt2 = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt2, "iissssi", $class_id, $subject_id, $title, $description, $due_date, $file_path, $teacher_id);

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Assignment created successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Assignment</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add Assignment</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<?php if ($class_id): ?>
<form method="post" enctype="multipart/form-data">
    <label>Subject:</label>
    <select name="subject_id" required>
        <option value="">Select Subject</option>
        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>">
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Title:</label>
    <input type="text" name="title" required> 

    <label>Description:</label>
    <textarea name="description"></textarea> 

    <label>Due Date:</label>
    <input type="date" name="due_date" required> 

    <label>Upload File (optional):</label>
    <input type="file" name="assignment_file"> 

    <button type="submit">Create</button>
    <a href="view_assignments.php">View Assignments</a>
</form>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
