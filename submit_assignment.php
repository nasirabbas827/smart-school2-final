<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['assignment_id'], $_POST['subject_id']) || !isset($_FILES['assignment_file'])) {
        die("Invalid request.");
    }

    $assignment_id = intval($_POST['assignment_id']);
    $subject_id = intval($_POST['subject_id']);
    $student_id = $_SESSION['id'];

    // Check if the student already submitted
    $check_sql = "SELECT submission_id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $assignment_id, $student_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();

    if ($existing) {
        header("Location: upload_assignment.php?subject_id=" . $subject_id);
        exit();
    }

    // File upload handling
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = $_FILES['assignment_file']['name'];
    $tmpPath = $_FILES['assignment_file']['tmp_name'];

    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $newFileName = 'assignment_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($tmpPath, $destination)) {
        $insert_sql = "
            INSERT INTO assignment_submissions (assignment_id, student_id, file_path)
            VALUES (?, ?, ?)
        ";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $assignment_id, $student_id, $destination);
        $insert_stmt->execute();

        header("Location: upload_assignment.php?subject_id=" . $subject_id);
        exit();
    } else {
        echo "File upload failed.";
    }
} else {
    echo "Invalid request method.";
}
