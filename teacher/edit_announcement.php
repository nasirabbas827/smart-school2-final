<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];

$id = $_GET['id'] ?? 0;
$stmt = mysqli_prepare($conn, "SELECT * FROM Announcements WHERE announcement_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$announcement = mysqli_fetch_assoc($result);

$successMsg = $errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title   = trim($_POST["title"]);
    $content = trim($_POST["content"]);

    if (!empty($title) && !empty($content)) {
        $update = mysqli_prepare($conn, "UPDATE Announcements SET title=?, content=? WHERE announcement_id=?");
        mysqli_stmt_bind_param($update, "ssi", $title, $content, $id);

        if (mysqli_stmt_execute($update)) {
            $successMsg = "Announcement updated successfully.";
            $announcement['title'] = $title;
            $announcement['content'] = $content;
        } else {
            $errorMsg = "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($update);
    } else {
        $errorMsg = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Announcement</title>
    <link rel="stylesheet" href="../CSS/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Announcement</h2>

<?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
<?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

<form action="" method="post">
    <div>
        <label>Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
    </div>
    <div>
        <label>Content:</label>
        <textarea name="content" rows="5" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
    </div>
    <div>
        <input type="submit" value="Update Announcement">
        <a href="view_announcements.php">Back</a>
    </div>
</form>
</body>
</html>
