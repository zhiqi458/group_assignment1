<?php
session_start();
include("db.php");

$error = "";
$success = ""; // 新增：用于存储成功消息

// 设置老板主密码
$BOSS_MASTER_KEY = "BOSS888"; 

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $input_master_key = $_POST['master_password'];

    // 1. 验证主密码
    if ($input_master_key !== $BOSS_MASTER_KEY) {
        $error = "Master Password Incorrect! (老板授权码错误)";
    } else {
        // 2. 验证账号密码
        $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['admin_user'] = $row['username'];
            
            // 登录成功，设置成功消息，不再直接使用 header 跳转
            $success = "Login Successful! Welcome, " . $row['username'];
        } else {
            $error = "Invalid Username or Password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secured Admin Login</title>
    <style>
        body {
            background: linear-gradient(135deg, #fff9e6 0%, #ffdf91 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: sans-serif;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 40px;
            border-radius: 20px;
            width: 350px;
            color: #2d3436; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        }

        .login-card h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #05c46b;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .login-group {
            margin-bottom: 15px;
        }

        .login-group label {
            display: block;
            font-size: 0.75rem;
            margin-bottom: 8px;
            color: #2d3436; 
            font-weight: bold;
        }

        .login-group input {
            width: 100%;
            padding: 12px;
            background: #ffffff; 
            border: 1px solid #ced4da; 
            border-radius: 8px;
            color: #000000; 
            box-sizing: border-box;
            font-size: 1rem;
            transition: 0.3s;
        }

        .login-group input:focus {
            outline: none;
            border-color: #05c46b;
            box-shadow: 0 0 8px rgba(5, 196, 107, 0.2);
        }

        .master-key-input {
            border-left: 5px solid #ffa502 !important;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #05c46b;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            margin-top: 15px;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #0be881;
        }

        .register-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #0984e3;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: bold;
        }

        /* 成功消息样式 */
        .success-msg {
            color: #27ae60;
            background: #eafaf1;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #27ae60;
        }

        .error-msg {
            color: #d63031;
            background: #fab1a0;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #d63031;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Secure Login</h2>

    <!-- 显示错误消息 -->
    <?php if ($error != ""): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- 显示成功消息并执行跳转 -->
    <?php if ($success != ""): ?>
        <div class="success-msg">
            <?php echo $success; ?>
            <div style="font-size: 0.7rem; margin-top: 5px;">Redirecting in <span id="timer">3</span>s...</div>
        </div>
        <script>
            let timeLeft = 3;
            const timer = document.getElementById('timer');
            const countdown = setInterval(() => {
                timeLeft--;
                timer.innerText = timeLeft;
                if (timeLeft <= 0) clearInterval(countdown);
            }, 1000);

            setTimeout(() => {
                window.location.href = "admin_menu.php";
            }, 3000);
        </script>
    <?php endif; ?>

    <form method="POST">
        <div class="login-group">
            <label>Admin Username</label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>

        <div class="login-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>

        <div class="login-group">
            <label style="color: #ffa502;">Boss Master Key</label>
            <input type="password" 
                   name="master_password" 
                   class="master-key-input" 
                   placeholder="Hover to see key" 
                   onmouseover="this.type='text'" 
                   onmouseout="this.type='password'"
                   required>
        </div>

        <button type="submit" name="login" class="login-btn">Verify & Sign In</button>
        <a href="register.php" class="register-link">Don't Have Account? Click And Register.</a>
    </form>
</div>

</body>
</html>