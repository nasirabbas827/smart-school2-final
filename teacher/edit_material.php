<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";

if (!isset($_GET['id'])) {
    die("Invalid material ID.");
}
$material_id = intval($_GET['id']);

// Fetch material + subject
$sql = "SELECT * FROM Materials WHERE material_id = ? AND uploaded_by = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $material_id, $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);

if (!$record) {
    die("Material not found or not authorized.");
}

// Fetch subjects for the same class
$subjects = [];
$class_id = $record['class_id'];
$sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
$stmt_sub = mysqli_prepare($conn, $sub_sql);
mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
mysqli_stmt_execute($stmt_sub);
$subjects = mysqli_stmt_get_result($stmt_sub);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject_id = intval($_POST['subject_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $file_path = $record['file_path'];

    if (!empty($_FILES['material_file']['name'])) {
        $upload_dir = "../uploads/";
        $file_name = time() . "_" . basename($_FILES['material_file']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
            $file_path = "uploads/" . $file_name;
        }
    }

    $update = "UPDATE Materials SET subject_id=?, title=?, description=?, file_path=? 
               WHERE material_id=? AND uploaded_by=?";
    $stmt2 = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt2, "isssii", $subject_id, $title, $description, $file_path, $material_id, $teacher_id);

    if (mysqli_stmt_execute($stmt2)) {
        header("Location: view_materials.php");
        exit();
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Material</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Study Material</h2>
<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<form method="post" enctype="multipart/form-data">
    <label>Subject:</label>
    <select name="subject_id" required>
        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
            <option value="<?php echo $sub['subject_id']; ?>" 
                <?php if ($sub['subject_id'] == $record['subject_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endwhile; ?>
    </select> 

    <label>Title:</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($record['title']); ?>" required> 

    <label>Description:</label>
    <textarea name="description"><?php echo htmlspecialchars($record['description']); ?></textarea> 

    <label>Replace File (optional):</label>
    <input type="file" name="material_file"> 

    <button type="submit">Update Material</button>
</form>
</body>
</html>
