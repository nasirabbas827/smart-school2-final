<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$successMsg = $errorMsg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title   = trim($_POST["title"]);
    $content = trim($_POST["content"]);

    if (!empty($title) && !empty($content)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO Announcements (title, content) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $title, $content);

        if (mysqli_stmt_execute($stmt)) {
            $successMsg = "Announcement added successfully.";
        } else {
            $errorMsg = "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    } else {
        $errorMsg = "Please fill in all fields.";
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Announcement</title>
    <link rel="stylesheet" href="../CSS/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<h2>Add school events, announcements, and notifications</h2>

<?php if ($successMsg) echo "<div style='color:green;'>$successMsg</div>"; ?>
<?php if ($errorMsg) echo "<div style='color:red;'>$errorMsg</div>"; ?>

<form action="" method="post">
    <div>
        <label>Title:</label>
        <input type="text" name="title" required>
    </div>
    <div>
        <label>Content:</label>
        <textarea name="content" rows="5" required></textarea>
    </div>
    <div>
        <input type="submit" value="Add Announcement">
        <a href="view_announcements.php">View All</a>
    </div>
</form>
</body>
</html>
