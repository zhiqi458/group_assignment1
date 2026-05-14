<?php
session_start();
include("db.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 
    $confirm_password = $_POST['confirm_password'];

    // 1. 验证两次密码是否一致
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } 
    else {
        // 2. 检查用户名是否已存在
        $check_user = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_user) > 0) {
            $error = "Username already exists!";
        } else {
            // 3. 插入新用户
            $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
            if (mysqli_query($conn, $sql)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="registerstyle.css">
</head>
<body>

<div class="register-card">
    <h2>Admin Register</h2>

    <?php if ($error != ""): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success != ""): ?>
        <div class="success-msg">
            <?php echo $success; ?>
            <div style="margin-top: 5px; font-size: 0.75rem; color: #57606f;">
                Redirecting to login in <span id="timer">3</span>s...
            </div>
        </div>
        
        <!-- 跳转脚本 -->
        <script>
            let count = 3;
            const timerElement = document.getElementById('timer');
            
            // 每秒更新一次显示的数字
            const countdown = setInterval(() => {
                count--;
                timerElement.innerText = count;
                if (count <= 0) clearInterval(countdown);
            }, 1000);

            // 3秒后跳转
            setTimeout(function() {
                window.location.href = "login.php";
            }, 3000);
        </script>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>New Username</label>
            <input type="text" name="username" placeholder="Create a username" required>
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="Create a password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat password" required>
        </div>

        <button type="submit" name="register" class="btn-register">Create Account</button>
    </form>

    <div class="footer-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>