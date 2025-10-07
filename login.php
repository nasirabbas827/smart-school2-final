<?php
include('config.php');
session_start();

// Initialize variables
$email = $password = $role = "";
$email_err = $password_err = $role_err = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    // Validate
    if (empty($email)) $email_err = "Please enter your email.";
    if (empty($password)) $password_err = "Please enter your password.";
    if (empty($role)) $role_err = "Please select role.";

    if (empty($email_err) && empty($password_err) && empty($role_err)) {
        if ($role === "student") {
            $sql = "SELECT student_id, email, password FROM Students WHERE email = ?";
        } elseif ($role === "teacher") {
            $sql = "SELECT teacher_id, email, password FROM Teachers WHERE email = ?";
        } else {
            $role_err = "Invalid role selected.";
        }

        if (empty($role_err) && $stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $email_db, $hashed_password);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        // Session
                        $_SESSION["id"] = $id;
                        $_SESSION["email"] = $email_db;
                        $_SESSION["role"] = $role;

                        // Redirect based on role
                        if ($role === "student") {
                            header("Location: dashboard.php");
                        } elseif ($role === "teacher") {
                            header("Location: teacher/teacher_dashboard.php");
                        }
                        exit();
                    } else {
                        $password_err = "Incorrect password.";
                    }
                }
            } else {
                $email_err = "No account found with that email.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    <div>
        <h2>Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div>
                <label>Role</label>
                <select name="role">
                    <option value="">-- Select Role --</option>
                    <option value="student" <?php if ($role=="student") echo "selected"; ?>>Student</option>
                    <option value="teacher" <?php if ($role=="teacher") echo "selected"; ?>>Teacher</option>
                </select>
                <span class="error"><?php echo $role_err; ?></span>
            </div>
            <div>
                <input type="submit" value="Login">
            </div>
        </form>
    </div>
</body>
</html>
