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
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            width: 380px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            color: #2d3436;
        }

        .register-card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #05c46b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            margin-bottom: 5px;
            color: #636e72;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: #f1f2f6;
            border: 1px solid #dfe4ea;
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: #05c46b;
            background: #fff;
            box-shadow: 0 0 5px rgba(5, 196, 107, 0.2);
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: #05c46b;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-register:hover {
            background: #0be881;
            transform: translateY(-1px);
        }

        .error-msg {
            color: #ff4757;
            background: #ffeef0;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #ff4757;
        }

        .success-msg {
            color: #2ed573;
            background: #e8fdf1;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #2ed573;
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
        }

        .footer-link a {
            color: #05c46b;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
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