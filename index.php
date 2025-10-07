<?php
session_start();
include "config.php";

// Fetch latest announcements
$announcements = mysqli_query($conn, "SELECT title, content, posted_at FROM announcements ORDER BY posted_at DESC LIMIT 4");

// Fetch stats for display
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM students"))['cnt'];
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM teachers"))['cnt'];
$total_classes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM classes"))['cnt'];
$total_materials = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM materials"))['cnt'];

// Dummy content for testimonials
$testimonials = [
    [
        'name' => 'Sarah Johnson',
        'role' => 'Teacher',
        'quote' => 'The Smart School Management System has streamlined my classroom management, making it easy to track assignments and communicate with students.'
    ],
    [
        'name' => 'Ahmed Khan',
        'role' => 'Student',
        'quote' => 'Accessing study materials and submitting assignments online has made my academic life so much easier!'
    ],
    [
        'name' => 'Emily Davis',
        'role' => 'Parent',
        'quote' => 'I love how I can stay updated with my child\'s progress and school announcements in real-time.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        .navbar {
            background: #2c3e50;
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .navbar a:hover {
            background: #34495e;
        }
        .navbar .brand {
            font-size: 1.5em;
            font-weight: 600;
        }
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./images/img.jpg');
            background-size: cover;
            background-position: center;
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        .hero-content {
            max-width: 800px;
            padding: 20px;
        }
        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        .cta-button {
            padding: 15px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background 0.3s, transform 0.3s;
        }
        .cta-button:hover {
            background: #2980b9;
            transform: scale(1.05);
        }
        .section {
            padding: 80px 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
        }
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-title h2 {
            font-size: 2.5em;
            color: #2c3e50;
        }
        .section-title p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 2em;
            color: #3498db;
            margin-bottom: 10px;
        }
        .stat-card p {
            color: #7f8c8d;
            font-size: 1em;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .feature-card {
            background: #fff;
            padding: 30px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-card i {
            font-size: 2.5em;
            color: #3498db;
            margin-bottom: 20px;
        }
        .feature-card h3 {
            font-size: 1.5em;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .announcements {
            display: grid;
            gap: 25px;
            margin-top: 30px;
        }
        .announcement {
            background: #fff;
            padding: 25px;
            border-left: 6px solid #3498db;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .announcement:hover {
            transform: translateY(-5px);
        }
        .announcement small {
            color: #7f8c8d;
            font-size: 0.9em;
            display: block;
            margin-top: 10px;
        }
        .testimonials {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .testimonial-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .testimonial-card p {
            font-style: italic;
            color: #555;
            margin-bottom: 15px;
        }
        .testimonial-card h4 {
            color: #2c3e50;
            font-size: 1.2em;
        }
        .testimonial-card span {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }
        footer a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px;
        }
        footer a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2em;
            }
            .hero p {
                font-size: 1em;
            }
            .navbar {
                flex-direction: column;
                gap: 10px;
            }
            .features, .stats-container, .testimonials {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
        <?php include('navbar.php'); ?>


    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h1>Smart School Management System</h1>
            <p>Empowering education with seamless management for students, teachers, and administrators.</p>
            <a href="login.php" class="cta-button">Get Started</a>
        </div>
    </div>

    <!-- Stats Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Our Impact</h2>
                <p>Transforming education one click at a time</p>
            </div>
            <div class="stats-container">
                <div class="stat-card">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Students Enrolled</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_teachers; ?></h3>
                    <p>Teachers Onboard</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_classes; ?></h3>
                    <p>Classes Managed</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_materials; ?></h3>
                    <p>Study Materials</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" style="background: #f4f6f9;">
        <div class="container">
            <div class="section-title">
                <h2>Our Features</h2>
                <p>Comprehensive tools to enhance the educational experience</p>
            </div>
            <div class="features">
                <div class="feature-card">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Student Management</h3>
                    <p>Track student profiles, attendance, grades, and fee payments effortlessly.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Teacher Dashboard</h3>
                    <p>Manage classes, assignments, quizzes, and communicate with students.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-book"></i>
                    <h3>Study Materials</h3>
                    <p>Access and upload timetables, notes, and resources for seamless learning.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bullhorn"></i>
                    <h3>Announcements</h3>
                    <p>Stay informed with real-time school updates and notifications.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bus"></i>
                    <h3>Transport Management</h3>
                    <p>Monitor bus schedules and routes for safe student transportation.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-comment-alt"></i>
                    <h3>Class Communication</h3>
                    <p>Engage with teachers and classmates through secure messaging.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Latest Announcements</h2>
                <p>Stay updated with the latest school news</p>
            </div>
            <div class="announcements">
                <?php if (mysqli_num_rows($announcements) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($announcements)): ?>
                        <div class="announcement">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars(substr($row['content'], 0, 150) . (strlen($row['content']) > 150 ? '...' : ''))); ?></p>
                            <small><i class="fas fa-clock"></i> <?php echo date("M d, Y", strtotime($row['posted_at'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Dummy Announcements -->
                    <div class="announcement">
                        <h3>School Reopening</h3>
                        <p>We are excited to announce that the school will reopen on January 10, 2026. Prepare for an exciting new semester!</p>
                        <small><i class="fas fa-clock"></i> Oct 05, 2025</small>
                    </div>
                    <div class="announcement">
                        <h3>Annual Sports Day</h3>
                        <p>Join us for our Annual Sports Day on November 15, 2025. Register for events by November 1!</p>
                        <small><i class="fas fa-clock"></i> Oct 01, 2025</small>
                    </div>
                    <div class="announcement">
                        <h3>Parent-Teacher Meeting</h3>
                        <p>Schedule your meeting for October 20, 2025, to discuss your child's progress.</p>
                        <small><i class="fas fa-clock"></i> Sep 25, 2025</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section" style="background: #f4f6f9;">
        <div class="container">
            <div class="section-title">
                <h2>What Our Users Say</h2>
                <p>Hear from our community of students, teachers, and parents</p>
            </div>
            <div class="testimonials">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <p>"<?php echo htmlspecialchars($testimonial['quote']); ?>"</p>
                        <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                        <span><?php echo htmlspecialchars($testimonial['role']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Smart School Management System. All rights reserved.</p>
        <p>
            <a href="index.php">About Us</a> |
            <a href="index.php">Contact</a> |
            <a href="index.php">Privacy Policy</a>
        </p>
    </footer>

    <?php mysqli_close($conn); ?>
</body>
</html>