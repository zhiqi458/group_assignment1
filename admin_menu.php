<?php
    // 1. 开启 Session
    session_start();
    include("db.php");

    /**
     * 权限检查：
     * 如果 session 中没有 admin_user，说明没登录。
     * 直接重定向回 login.php，并停止执行后续代码。
     */
    if (!isset($_SESSION['admin_user'])) {
        header("Location: login.php");
        exit(); // 务必加上 exit，防止未登录用户看到下方的代码内容
    }

    // 分别获取 Food 和 Drink 的数据
    $food_result = mysqli_query($conn, "SELECT * FROM items WHERE UPPER(category)='FOOD'");
    $drink_result = mysqli_query($conn, "SELECT * FROM items WHERE UPPER(category)='DRINK'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Menu - Secured</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 登录信息样式 */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-name {
            color: #05c46b;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .logout-btn {
            color: #ff4757;
            text-decoration: none;
            font-size: 0.75rem;
            border: 1px solid #ff4757;
            padding: 2px 6px;
            border-radius: 4px;
            transition: 0.3s;
        }
        .logout-btn:hover {
            background: #ff4757;
            color: white;
        }
        
        /* 防止页面内容在重定向瞬间闪烁的保险样式 */
        body { font-family: sans-serif; }
    </style>
</head>
<body>
    <!-- 1. 侧边栏抽屉结构 -->
    <div id="side-drawer" class="drawer">
        <a href="javascript:void(0)" class="close-btn" onclick="closeNav()">&times;</a>
        <div class="drawer-content">
            <h3>Navigation</h3>
            <a href="#food-section" onclick="closeNav()">Food Selection</a>
            <a href="#drink-section" onclick="closeNav()">Drink Selection</a>
            <hr>
            <a href="customer.php">Customer Menu</a>
            <a href="close_bill.php">Close Bill</a>
            <a href="cashier.php">Cashier</a>
        </div>
    </div>

    <!-- 2. Header 部分 -->
    <div class="header">
        <span class="menu-icon" onclick="openNav()">&#9776; Menu</span>
        
        <h1>Menu Management</h1>

        <div class="login-link">
            <!-- 因为上面有拦截逻辑，运行到这里时一定是已登录状态 -->
            <div class="user-info">
                <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
                <!-- 建议这里指向专门的登出脚本 logout.php，而不是 login.php -->
                <a href="login.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <!-- 3. FOOD 区域 -->
    <div class="section-header" id="food-section">
        <h2 class="section-title">Food Selection</h2>
        <a href="admin_add.php" class="add-item-btn">+ Add New Food</a>
    </div>
    <div class="menu-container">
        <?php if (mysqli_num_rows($food_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($food_result)): ?>
                <div class="card">
                    <div class="admin-actions">
                        <a href="admin_edit.php?id=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                        <a href="admin_delete.php?id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('确定删除吗？')">Delete</a>
                    </div>
                    <img src="images/<?php echo $row['image']; ?>" alt="">
                    <div class="card_text"><?php echo $row['i_name']; ?></div>
                    <p class="description"><?php echo $row['description']; ?></p>
                    <p class="price">$<?php echo number_format($row['price'], 2); ?></p>
                    <p class="stock">Stock: <?php echo $row['stock']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-msg">No food items found.</p>
        <?php endif; ?>
    </div>

    <hr class="divider">

    <!-- 4. DRINK 区域 -->
    <div class="section-header" id="drink-section">
        <h2 class="section-title">Drink Selection</h2>
        <a href="admin_add.php" class="add-item-btn">+ Add New Drink</a>
    </div>
    <div class="menu-container">
        <?php if (mysqli_num_rows($drink_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($drink_result)): ?>
                <div class="card">
                    <div class="admin-actions">
                        <a href="admin_edit.php?id=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                        <a href="admin_delete.php?id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('确定删除吗？')">Delete</a>
                    </div>
                    <img src="images/<?php echo $row['image']; ?>" alt="">
                    <div class="card_text"><?php echo $row['i_name']; ?></div>
                    <p class="description"><?php echo $row['description']; ?></p>
                    <p class="price">$<?php echo number_format($row['price'], 2); ?></p>
                    <p class="stock">Stock: <?php echo $row['stock']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-msg">No drink items found.</p>
        <?php endif; ?>
    </div>

    <script>
        function openNav() {
            document.getElementById("side-drawer").style.width = "250px";
        }
        function closeNav() {
            document.getElementById("side-drawer").style.width = "0";
        }
    </script>
</body>
</html>