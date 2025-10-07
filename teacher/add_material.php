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

// Get teacher class
$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

// Fetch subjects based on the same class
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
    $upload_dir = "../uploads/";
    $file_path = "";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['material_file']['name'])) {
        $file_name = time() . "_" . basename($_FILES['material_file']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
            $file_path = "uploads/" . $file_name;
        } else {
            $message = "Error uploading file.";
        }
    }

    if ($file_path && $subject_id) {
        $insert = "INSERT INTO Materials (class_id, subject_id, title, description, file_path, uploaded_by) 
                   VALUES (?,?,?,?,?,?)";
        $stmt2 = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt2, "iisssi", $class_id, $subject_id, $title, $description, $file_path, $teacher_id);

        if (mysqli_stmt_execute($stmt2)) {
            $message = "Material uploaded successfully.";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Material</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add Study Material</h2>
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

    <label>Upload File:</label>
    <input type="file" name="material_file" required> 

    <button type="submit">Upload</button>
    <a href="view_materials.php">View Materials</a>
</form>
<?php else: ?>
<p>You are not assigned to a class.</p>
<?php endif; ?>
</body>
</html>
