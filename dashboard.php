<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["id"];

// Fetch student, class, and teacher details
$student_sql = "
    SELECT s.student_id, s.first_name, s.last_name, s.class_id, 
           c.class_name, 
           t.first_name AS teacher_fname, t.last_name AS teacher_lname
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
    WHERE s.student_id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_name = $student_info ? $student_info['first_name'] . " " . $student_info['last_name'] : "Student";
$class_id = $student_info['class_id'] ?? null;
$class_name = $student_info['class_name'] ?? "Not Assigned";
$teacher_name = ($student_info['teacher_fname'] ?? "") . " " . ($student_info['teacher_lname'] ?? "");

// Fetch stats
$attendance_sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present 
                  FROM attendance WHERE student_id = ?";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attendance_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total_attendance = $attendance_stats['total'] ?? 0;
$present_days = $attendance_stats['present'] ?? 0;
$attendance_percentage = $total_attendance > 0 ? round(($present_days / $total_attendance) * 100, 2) : 0;

$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM assignments WHERE class_id = $class_id"))['cnt'];
$total_submissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM assignment_submissions WHERE student_id = $student_id"))['cnt'];
$total_quizzes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM quizzes WHERE class_id = $class_id"))['cnt'];
$pending_fees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM fee_vouchers WHERE student_id = $student_id AND status = 'Pending'"))['cnt'];

// Fetch recent data
$attendance_sql = "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 5";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attendance_result = $stmt->get_result();
$stmt->close();

$announcements = $conn->query("SELECT title, content, posted_at FROM announcements ORDER BY posted_at DESC LIMIT 5");

$subjects = [];
if ($class_id) {
    $sub_stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE class_id = ?");
    $sub_stmt->bind_param("i", $class_id);
    $sub_stmt->execute();
    $subjects = $sub_stmt->get_result();
    $sub_stmt->close();
}

$assignments = [];
if ($class_id) {
    $assign_sql = "SELECT assignment_id, title, due_date FROM assignments WHERE class_id = ? ORDER BY due_date DESC LIMIT 5";
    $stmt = $conn->prepare($assign_sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $assignments = $stmt->get_result();
    $stmt->close();
}

$materials = [];
if ($class_id) {
    $material_sql = "SELECT material_id, title, file_path, uploaded_at FROM materials WHERE class_id = ? ORDER BY uploaded_at DESC LIMIT 5";
    $stmt = $conn->prepare($material_sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $materials = $stmt->get_result();
    $stmt->close();
}

$messages = [];
if ($class_id) {
    $message_sql = "SELECT message, sender_role, sent_at FROM class_messages WHERE class_id = ? ORDER BY sent_at DESC LIMIT 5";
    $stmt = $conn->prepare($message_sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $messages = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="./CSS/style.css">
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
        .chart-container, .card, .activity-container { 
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
        .download { 
            color: #3498db; 
            text-decoration: none; 
            padding: 6px 12px; 
            border: 1px solid #3498db; 
            border-radius: 5px; 
            display: inline-block; 
        }
        .download:hover { background: #3498db; color: white; }
        .announcement { margin-bottom: 15px; }
        .announcement small { color: #7f8c8d; font-size: 12px; }
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
        <h1>Welcome, <?php echo htmlspecialchars($student_name); ?> (Class: <?php echo htmlspecialchars($class_name); ?>)</h1>
        <div class="nav-buttons">
            <button onclick="window.location.href='student_dashboard.php'">Home</button>
            <button onclick="window.location.href='view_grades.php'">Grades</button>
            <button onclick="window.location.href='student_leave.php'">Leave Application</button>
            <button onclick="window.location.href='student_vouchers.php'">Fee & Vouchers</button>
            <button onclick="window.location.href='exam_schedule.php'">Exam Schedule</button>
            <button onclick="window.location.href='view_transport.php'">Transport</button>
            <button onclick="window.location.href='chat.php'">Chat</button>
            <button onclick="window.location.href='feedback.php'">Feedback</button>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card"><h3><?php echo $attendance_percentage; ?>%</h3><p>Attendance</p></div>
            <div class="stat-card"><h3><?php echo $total_assignments; ?></h3><p>Assignments</p></div>
            <div class="stat-card"><h3><?php echo $total_submissions; ?></h3><p>Submissions</p></div>
            <div class="stat-card"><h3><?php echo $total_quizzes; ?></h3><p>Quizzes</p></div>
            <div class="stat-card"><h3><?php echo $pending_fees; ?></h3><p>Pending Fees</p></div>
        </div>

        <div class="main-content">
            <!-- Chart -->
            <div class="chart-container">
                <h2 class="section-title">Academic Overview</h2>
                <canvas id="academicChart"></canvas>
            </div>

            <!-- Subjects and Materials -->
            <div class="card">
                <h2 class="section-title">Your Subjects</h2>
                <?php if ($subjects && $subjects->num_rows > 0): ?>
                    <table>
                        <tr><th>Subject Name</th><th>Action</th></tr>
                        <?php while ($sub = $subjects->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                <td>
                                    <a class="download" href="subject_details.php?subject_id=<?php echo $sub['subject_id']; ?>">View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No subjects found.</p>
                <?php endif; ?>

                <h2 class="section-title" style="margin-top:20px;">Study Materials</h2>
                <?php if ($materials && $materials->num_rows > 0): ?>
                    <table>
                        <tr><th>Title</th><th>Uploaded At</th><th>Action</th></tr>
                        <?php while ($material = $materials->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($material['title']); ?></td>
                                <td><?php echo date('d M Y', strtotime($material['uploaded_at'])); ?></td>
                                <td>
                                    <a class="download" href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">Download</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No study materials available.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="activity-container">
                <h2 class="section-title">Recent Announcements</h2>
                <?php if ($announcements && $announcements->num_rows > 0): ?>
                    <?php while ($row = $announcements->fetch_assoc()): ?>
                        <div class="announcement">
                            <strong style="color: red;"><?php echo htmlspecialchars($row['title']); ?></strong>
                            <small><?php echo date('d M Y', strtotime($row['posted_at'])); ?></small>
                            <p><?php echo nl2br(htmlspecialchars(substr($row['content'], 0, 100) . (strlen($row['content']) > 100 ? '...' : ''))); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No announcements yet.</p>
                <?php endif; ?>

                <h2 class="section-title" style="margin-top:20px;">Recent Assignments</h2>
                <?php if ($assignments && $assignments->num_rows > 0): ?>
                    <table>
                        <tr><th>Title</th><th>Due Date</th><th>Action</th></tr>
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                <td><?php echo date('d M Y', strtotime($assignment['due_date'])); ?></td>
                                <td>
                                    <p>Please Upload Your Assignment Befor Due Date</p>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No assignments available.</p>
                <?php endif; ?>

                <h2 class="section-title" style="margin-top:20px;">Recent Messages</h2>
                <?php if ($messages && $messages->num_rows > 0): ?>
                    <table>
                        <tr><th>Sender</th><th>Message</th><th>Sent At</th></tr>
                        <?php while ($message = $messages->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $message['sender_role'] == 'teacher' ? 'Teacher' : 'Student'; ?></td>
                                <td><?php echo htmlspecialchars(substr($message['message'], 0, 30)) . (strlen($message['message']) > 30 ? '...' : ''); ?></td>
                                <td><?php echo date('d M Y', strtotime($message['sent_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No messages available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Academic Overview Chart
        const academicChart = document.getElementById('academicChart');
        new Chart(academicChart, {
            type: 'bar',
            data: {
                labels: ['Attendance %', 'Assignments', 'Submissions', 'Quizzes', 'Pending Fees'],
                datasets: [{
                    label: 'Academic Overview',
                    data: [<?php echo $attendance_percentage; ?>, <?php echo $total_assignments; ?>, <?php echo $total_submissions; ?>, <?php echo $total_quizzes; ?>, <?php echo $pending_fees; ?>],
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