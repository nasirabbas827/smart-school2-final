<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$message = "";
$timetable_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get teacher class
$sql = "SELECT class_id, class_name FROM Classes WHERE teacher_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);
$class_id = $class ? $class['class_id'] : null;

if (!$class_id || !$timetable_id) {
    header("Location: view_timetables.php");
    exit();
}

// Fetch subjects for dropdown
$subjects = [];
$sub_sql = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
$stmt_sub = mysqli_prepare($conn, $sub_sql);
mysqli_stmt_bind_param($stmt_sub, "i", $class_id);
mysqli_stmt_execute($stmt_sub);
$subjects = mysqli_stmt_get_result($stmt_sub);

// Fetch existing timetable record
$existing = null;
$check_sql = "SELECT * FROM Timetables WHERE timetable_id = ? AND class_id = ?";
$stmt_check = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt_check, "ii", $timetable_id, $class_id);
mysqli_stmt_execute($stmt_check);
$res = mysqli_stmt_get_result($stmt_check);
$existing = mysqli_fetch_assoc($res);

if (!$existing) {
    header("Location: view_timetables.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $subject_id = intval($_POST['subject_id']);

    $update = "
        UPDATE Timetables
        SET day_of_week = ?, start_time = ?, end_time = ?, subject_id = ?
        WHERE timetable_id = ? AND uploaded_by = ?
    ";
    $stmt_up = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt_up, "ssssii", $day_of_week, $start_time, $end_time, $subject_id, $timetable_id, $teacher_id);
    if (mysqli_stmt_execute($stmt_up)) {
        header("Location: view_timetables.php");
        exit();
    } else {
        $message = "Error updating timetable: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Timetable</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<h2>Edit Timetable</h2>

<?php if ($message) echo "<p style='color:red;'>$message</p>"; ?>

<form method="post">
    <label>Day of Week:</label>
    <select name="day_of_week" required>
        <?php
        $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
        foreach ($days as $day) {
            $sel = ($existing['day_of_week'] === $day) ? "selected" : "";
            echo "<option $sel>$day</option>";
        }
        ?>
    </select> 

    <label>Start Time:</label>
    <input type="time" name="start_time" value="<?php echo htmlspecialchars($existing['start_time']); ?>" required> 

    <label>End Time:</label>
    <input type="time" name="end_time" value="<?php echo htmlspecialchars($existing['end_time']); ?>" required> 

    <label>Subject:</label>
    <select name="subject_id" required>
        <option value="">Select Subject</option>
        <?php foreach ($subjects as $sub): ?>
            <option value="<?php echo $sub['subject_id']; ?>"
                <?php if ($sub['subject_id'] == $existing['subject_id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($sub['subject_name']); ?>
            </option>
        <?php endforeach; ?>
    </select> 

    <button type="submit">Update</button>
    <a href="view_timetables.php">Cancel</a>
</form>

</body>
</html>
