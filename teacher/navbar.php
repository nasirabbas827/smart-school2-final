<nav>
        <ul>
            <li><a href="teacher_dashboard.php">Smart School Management </a></li>
            <?php
            if (isset($_SESSION["id"]) && !empty($_SESSION["id"])) {
                echo '<li><a href="teacher_dashboard.php">Home</a></li>';
                echo '<li><a href="mark_attendance.php">Attendance</a></li>';
                echo '<li><a href="add_subject.php">Subjects</a></li>';
                echo '<li><a href="add_timetable.php">TimeTable</a></li>';
                echo '<li><a href="add_material.php">Study Material</a></li>';
                echo '<li><a href="add_announcement.php">Announcement</a></li>';
                echo '<li><a href="logout.php">Logout</a></li>';
            } else {
                echo '<li><a href="login.php">Login</a></li>';
                echo '<li><a href="adminlogin.php">Admin Login</a></li>';
            }
            ?>
        </ul>
    </nav>