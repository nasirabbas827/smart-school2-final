<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch Stats
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM teachers"))['cnt'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM students"))['cnt'];
$total_classes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM classes"))['cnt'];
$total_announcements = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM announcements"))['cnt'];
$total_materials = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM materials"))['cnt'];
$total_timetables = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM timetables"))['cnt'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM assignments"))['cnt'];
$total_submissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM assignment_submissions"))['cnt'];
$total_exams = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM exams_schedule"))['cnt'];
$total_fees_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM fee_vouchers WHERE status = 'Pending'"))['cnt'];
$total_leaves_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM leave_applications WHERE status = 'Pending'"))['cnt'];

// Fetch latest data
$latest_teachers = mysqli_query($conn, "SELECT teacher_id, first_name, last_name, email, department FROM teachers ORDER BY created_at DESC LIMIT 5");
$latest_students = mysqli_query($conn, "SELECT student_id, first_name, last_name, email, class_id FROM students ORDER BY created_at DESC LIMIT 5");
$recent_announcements = mysqli_query($conn, "SELECT title, posted_at FROM announcements ORDER BY posted_at DESC LIMIT 5");
$pending_leaves = mysqli_query($conn, "SELECT l.leave_id, l.student_id, l.reason, l.start_date, s.first_name, s.last_name 
                                       FROM leave_applications l 
                                       JOIN students s ON l.student_id = s.student_id 
                                       WHERE l.status = 'Pending' LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .chart-container, .users-table, .recent-activity { 
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
        }
        .action-btn.approve { background: #2ecc71; }
        .action-btn.reject { background: #e74c3c; }
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
        <h1>Admin Dashboard</h1>
        <div class="nav-buttons">
            <button onclick="window.location.href='admin_dashboard.php'">Home</button>
            <button onclick="window.location.href='add_teacher.php'">Teachers</button>
            <button onclick="window.location.href='add_class.php'">Classes</button>
            <button onclick="window.location.href='add_student.php'">Students</button>
            <button onclick="window.location.href='add_announcement.php'">Announcements</button>
            <button onclick="window.location.href='view_attendance.php'">Attendance</button>
            <button onclick="window.location.href='add_voucher.php'">Fee</button>
            <button onclick="window.location.href='add_transport.php'">Transport</button>
            <button onclick="window.location.href='admin_feedback.php'">Feedback</button>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card"><h3><?php echo $total_teachers; ?></h3><p>Total Teachers</p></div>
            <div class="stat-card"><h3><?php echo $total_students; ?></h3><p>Total Students</p></div>
            <div class="stat-card"><h3><?php echo $total_classes; ?></h3><p>Total Classes</p></div>
            <div class="stat-card"><h3><?php echo $total_announcements; ?></h3><p>Announcements</p></div>
            <div class="stat-card"><h3><?php echo $total_materials; ?></h3><p>Study Materials</p></div>
            <div class="stat-card"><h3><?php echo $total_timetables; ?></h3><p>Timetables</p></div>
            <div class="stat-card"><h3><?php echo $total_assignments; ?></h3><p>Assignments</p></div>
            <div class="stat-card"><h3><?php echo $total_submissions; ?></h3><p>Submissions</p></div>
            <div class="stat-card"><h3><?php echo $total_exams; ?></h3><p>Exams</p></div>
            <div class="stat-card"><h3><?php echo $total_fees_pending; ?></h3><p>Pending Fees</p></div>
            <div class="stat-card"><h3><?php echo $total_leaves_pending; ?></h3><p>Pending Leaves</p></div>
        </div>

        <div class="main-content">
            <!-- Charts -->
            <div class="chart-container">
                <h2 class="section-title">System Overview</h2>
                <canvas id="overviewChart"></canvas>
            </div>

            <!-- Latest Users & Activities -->
            <div class="users-table">
                <h2 class="section-title">Latest Teachers</h2>
                <table>
                    <tr><th>Name</th><th>Email</th><th>Department</th></tr>
                    <?php while($t = mysqli_fetch_assoc($latest_teachers)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['first_name'] . " " . $t['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['email']); ?></td>
                            <td><?php echo htmlspecialchars($t['department']); ?></td>
                        </tr>
                    <?php } ?>
                </table>

                <h2 class="section-title" style="margin-top:20px;">Latest Students</h2>
                <table>
                    <tr><th>Name</th><th>Email</th></tr>
                    <?php while($s = mysqli_fetch_assoc($latest_students)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['first_name'] . " " . $s['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>

            <!-- Recent Activities -->
            <div class="recent-activity">
                <h2 class="section-title">Recent Announcements</h2>
                <table>
                    <tr><th>Title</th><th>Posted At</th></tr>
                    <?php while($a = mysqli_fetch_assoc($recent_announcements)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['title']); ?></td>
                            <td><?php echo date('d M Y', strtotime($a['posted_at'])); ?></td>
                        </tr>
                    <?php } ?>
                </table>

                <h2 class="section-title" style="margin-top:20px;">Pending Leave Applications</h2>
                <table>
                    <tr><th>Student</th><th>Reason</th><th>Date</th><th>Action</th></tr>
                    <?php while($l = mysqli_fetch_assoc($pending_leaves)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($l['first_name'] . " " . $l['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($l['reason'], 0, 30)) . (strlen($l['reason']) > 30 ? '...' : ''); ?></td>
                            <td><?php echo date('d M', strtotime($l['start_date'])); ?></td>
                            <td>
                                <button class="action-btn approve" onclick="handleLeaveAction(<?php echo $l['leave_id']; ?>, 'Approved')">Approve</button>
                                <button class="action-btn reject" onclick="handleLeaveAction(<?php echo $l['leave_id']; ?>, 'Rejected')">Reject</button>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        // User Distribution Chart
        const overviewChart = document.getElementById('overviewChart');
        new Chart(overviewChart, {
            type: 'bar',
            data: {
                labels: ['Teachers', 'Students', 'Classes', 'Assignments', 'Exams'],
                datasets: [{
                    label: 'System Overview',
                    data: [<?php echo $total_teachers; ?>, <?php echo $total_students; ?>, <?php echo $total_classes; ?>, <?php echo $total_assignments; ?>, <?php echo $total_exams; ?>],
                    backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6']
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Handle Leave Application Actions
        function handleLeaveAction(leaveId, status) {
            if (confirm(`Are you sure you want to ${status} this leave application?`)) {
                fetch('update_leave.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `leave_id=${leaveId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Leave application updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating leave application: ' + data.message);
                    }
                })
                .catch(error => alert('Error: ' + error));
            }
        }
    </script>
</body>
</html>