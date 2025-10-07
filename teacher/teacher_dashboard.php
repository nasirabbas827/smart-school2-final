<?php
include('config.php');
session_start();

// Allow only teachers
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["id"];
$class_name = "";
$students = [];
$subjects = [];
$recent_assignments = [];
$recent_quizzes = [];
$pending_submissions = [];
$recent_messages = [];

// Fetch teacher details
$teacher_sql = "SELECT first_name, last_name, department FROM teachers WHERE teacher_id = ?";
$stmt_teacher = mysqli_prepare($conn, $teacher_sql);
mysqli_stmt_bind_param($stmt_teacher, "i", $teacher_id);
mysqli_stmt_execute($stmt_teacher);
$teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_teacher));
mysqli_stmt_close($stmt_teacher);

// Fetch class assigned to this teacher
$class_sql = "SELECT class_id, class_name FROM classes WHERE teacher_id = ?";
$stmt_class = mysqli_prepare($conn, $class_sql);
mysqli_stmt_bind_param($stmt_class, "i", $teacher_id);
mysqli_stmt_execute($stmt_class);
$class_result = mysqli_stmt_get_result($stmt_class);

if ($row = mysqli_fetch_assoc($class_result)) {
    $class_id = $row['class_id'];
    $class_name = $row['class_name'];

    // Fetch students in the class
    $student_sql = "SELECT student_id, first_name, last_name, roll_number, email FROM students WHERE class_id = ?";
    $stmt_students = mysqli_prepare($conn, $student_sql);
    mysqli_stmt_bind_param($stmt_students, "i", $class_id);
    mysqli_stmt_execute($stmt_students);
    $students = mysqli_stmt_get_result($stmt_students);
    mysqli_stmt_close($stmt_students);

    // Fetch subjects taught by the teacher
    $subject_sql = "SELECT subject_id, subject_name FROM subjects WHERE teacher_id = ? AND class_id = ?";
    $stmt_subjects = mysqli_prepare($conn, $subject_sql);
    mysqli_stmt_bind_param($stmt_subjects, "ii", $teacher_id, $class_id);
    mysqli_stmt_execute($stmt_subjects);
    $subjects = mysqli_stmt_get_result($stmt_subjects);
    mysqli_stmt_close($stmt_subjects);

    // Fetch recent assignments
    $assignment_sql = "SELECT assignment_id, title, due_date FROM assignments WHERE created_by = ? AND class_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt_assignments = mysqli_prepare($conn, $assignment_sql);
    mysqli_stmt_bind_param($stmt_assignments, "ii", $teacher_id, $class_id);
    mysqli_stmt_execute($stmt_assignments);
    $recent_assignments = mysqli_stmt_get_result($stmt_assignments);
    mysqli_stmt_close($stmt_assignments);

    // Fetch recent quizzes
    $quiz_sql = "SELECT quiz_id, question, total_marks FROM quizzes WHERE created_by = ? AND class_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt_quizzes = mysqli_prepare($conn, $quiz_sql);
    mysqli_stmt_bind_param($stmt_quizzes, "ii", $teacher_id, $class_id);
    mysqli_stmt_execute($stmt_quizzes);
    $recent_quizzes = mysqli_stmt_get_result($stmt_quizzes);
    mysqli_stmt_close($stmt_quizzes);

    // Fetch pending assignment submissions
    $submission_sql = "SELECT s.submission_id, s.assignment_id, s.student_id, s.submitted_at, a.title, st.first_name, st.last_name 
                      FROM assignment_submissions s 
                      JOIN assignments a ON s.assignment_id = a.assignment_id 
                      JOIN students st ON s.student_id = st.student_id 
                      WHERE a.created_by = ? AND s.grade IS NULL LIMIT 5";
    $stmt_submissions = mysqli_prepare($conn, $submission_sql);
    mysqli_stmt_bind_param($stmt_submissions, "i", $teacher_id);
    mysqli_stmt_execute($stmt_submissions);
    $pending_submissions = mysqli_stmt_get_result($stmt_submissions);
    mysqli_stmt_close($stmt_submissions);

    // Fetch recent class messages
    $message_sql = "SELECT message_id, message, sent_at, sender_role, sender_id 
                    FROM class_messages 
                    WHERE class_id = ? ORDER BY sent_at DESC LIMIT 5";
    $stmt_messages = mysqli_prepare($conn, $message_sql);
    mysqli_stmt_bind_param($stmt_messages, "i", $class_id);
    mysqli_stmt_execute($stmt_messages);
    $recent_messages = mysqli_stmt_get_result($stmt_messages);
    mysqli_stmt_close($stmt_messages);
}
mysqli_stmt_close($stmt_class);

// Fetch stats
$total_students = mysqli_num_rows($students);
$total_subjects = mysqli_num_rows($subjects);
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM assignments WHERE created_by = $teacher_id"))['cnt'];
$total_quizzes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM quizzes WHERE created_by = $teacher_id"))['cnt'];
$total_pending_submissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM assignment_submissions s JOIN assignments a ON s.assignment_id = a.assignment_id WHERE a.created_by = $teacher_id AND s.grade IS NULL"))['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f0f2f5; color: #333; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { 
            background: #2c3e50; 
            color: white; 
            padding: 15px 20px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .header h1 { font-size: 24px; font-weight: 600; }
        .nav-buttons { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap;
        }
        .nav-buttons button { 
            padding: 10px 15px; 
            border: none; 
            border-radius: 5px; 
            background: #3498db; 
            color: white; 
            cursor: pointer; 
            transition: background 0.3s; 
            font-size: 14px;
        }
        .nav-buttons button:hover { background: #2980b9; }
        .nav-buttons .logout-btn { background: #e74c3c; }
        .nav-buttons .logout-btn:hover { background: #c0392b; }
        .stats-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 20px; 
        }
        .stat-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            text-align: center; 
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 32px; color: #2c3e50; margin-bottom: 10px; }
        .stat-card p { color: #7f8c8d; font-size: 14px; }
        .main-content { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 20px; 
        }
        .chart-container, .table-container, .activity-container { 
            background: #fff; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            height: 400px; 
            overflow-y: auto;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .section-title { color: #2c3e50; margin-bottom: 15px; font-size: 18px; }
        .action-btn { 
            padding: 6px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            color: white; 
            background: #3498db;
        }
        .action-btn:hover { opacity: 0.9; }
        @media (max-width: 768px) {
            .main-content { grid-template-columns: 1fr; }
            .stats-container { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 15px; }
            .nav-buttons { justify-content: center; }
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($teacher['first_name'] . " " . $teacher['last_name']); ?> (<?php echo htmlspecialchars($teacher['department']); ?>)</h1>
        <div class="nav-buttons">
            <button onclick="window.location.href='teacher_dashboard.php'">Home</button>
            <button onclick="window.location.href='add_assignment.php'">Assignments</button>
            <button onclick="window.location.href='add_quiz.php'">Quizzes</button>
            <button onclick="window.location.href='add_exam.php'">Exam Schedule</button>
            <button onclick="window.location.href='add_grade.php'">Grades</button>
            <button onclick="window.location.href='manage_leaves.php'">Leave Applications</button>
            <button onclick="window.location.href='chat.php'">Chat Message</button>
            <button onclick="window.location.href='teacher_feedback.php'">Feedback</button>
            <button onclick="window.location.href='add_material.php'">Study Materials</button>
            <button onclick="window.location.href='view_attendance.php'">Attendance</button>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card"><h3><?php echo $total_students; ?></h3><p>Total Students</p></div>
            <div class="stat-card"><h3><?php echo $total_subjects; ?></h3><p>Subjects Taught</p></div>
            <div class="stat-card"><h3><?php echo $total_assignments; ?></h3><p>Assignments</p></div>
            <div class="stat-card"><h3><?php echo $total_quizzes; ?></h3><p>Quizzes</p></div>
            <div class="stat-card"><h3><?php echo $total_pending_submissions; ?></h3><p>Pending Submissions</p></div>
        </div>

        <div class="main-content">
            <!-- Chart -->
            <div class="chart-container">
                <h2 class="section-title">Class Overview</h2>
                <canvas id="classChart"></canvas>
            </div>

            <!-- Students and Subjects -->
            <div class="table-container">
                <?php if ($class_name): ?>
                    <h2 class="section-title">Students in <?php echo htmlspecialchars($class_name); ?></h2>
                    <table>
                        <tr><th>Roll No</th><th>Name</th><th>Email</th></tr>
                        <?php while ($student = mysqli_fetch_assoc($students)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>

                    <h2 class="section-title" style="margin-top:20px;">Subjects Taught</h2>
                    <table>
                        <tr><th>Subject Name</th></tr>
                        <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>You are not assigned to any class yet.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="activity-container">
                <h2 class="section-title">Recent Assignments</h2>
                <table>
                    <tr><th>Title</th><th>Due Date</th></tr>
                    <?php while ($assignment = mysqli_fetch_assoc($recent_assignments)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                            <td><?php echo date('d M Y', strtotime($assignment['due_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>

                <h2 class="section-title" style="margin-top:20px;">Pending Submissions</h2>
                <table>
                    <tr><th>Assignment</th><th>Student</th><th>Submitted At</th><th>Action</th></tr>
                    <?php while ($submission = mysqli_fetch_assoc($pending_submissions)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['title']); ?></td>
                            <td><?php echo htmlspecialchars($submission['first_name'] . " " . $submission['last_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($submission['submitted_at'])); ?></td>
                            <td>
                                <button class="action-btn" onclick="window.location.href='grade_submission.php?submission_id=<?php echo $submission['submission_id']; ?>'">Grade</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>

                <h2 class="section-title" style="margin-top:20px;">Recent Messages</h2>
                <table>
                    <tr><th>Sender</th><th>Message</th><th>Sent At</th></tr>
                    <?php while ($message = mysqli_fetch_assoc($recent_messages)): ?>
                        <tr>
                            <td><?php echo $message['sender_role'] == 'teacher' ? 'Teacher' : 'Student'; ?></td>
                            <td><?php echo htmlspecialchars(substr($message['message'], 0, 30)) . (strlen($message['message']) > 30 ? '...' : ''); ?></td>
                            <td><?php echo date('d M Y', strtotime($message['sent_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Class Overview Chart
        const classChart = document.getElementById('classChart');
        new Chart(classChart, {
            type: 'bar',
            data: {
                labels: ['Students', 'Subjects', 'Assignments', 'Quizzes', 'Pending Submissions'],
                datasets: [{
                    label: 'Class Overview',
                    data: [<?php echo $total_students; ?>, <?php echo $total_subjects; ?>, <?php echo $total_assignments; ?>, <?php echo $total_quizzes; ?>, <?php echo $total_pending_submissions; ?>],
                    backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6']
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>