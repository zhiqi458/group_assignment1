<?php
    include("db.php");
    // 分别获取 Food 和 Drink 的数据
    // 使用 UPPER 函数将数据库字段转为大写再对比，防止存入时大小写不统一
    $food_result = mysqli_query($conn, "SELECT * FROM items WHERE UPPER(category)='FOOD'");
    $drink_result = mysqli_query($conn, "SELECT * FROM items WHERE UPPER(category)='DRINK'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Menu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- 1. 侧边栏抽屉结构 -->
    <div id="side-drawer" class="drawer">
        <a href="javascript:void(0)" class="close-btn" onclick="closeNav()">&times;</a>
        <div class="drawer-content">
            <h3>Navigation</h3>
            <!-- 点击跳转到对应 ID 的区域并自动关闭抽屉 -->
            <a href="#food-section" onclick="closeNav()">Food Selection</a>
            <a href="#drink-section" onclick="closeNav()">Drink Selection</a>
            <hr>
            <a href="admin_add.php">Add New Item</a>
        </div>
    </div>

    <!-- 2. Header 部分：添加菜单图标 -->
    <div class="header">
        <!-- 图标通过 position: absolute 固定在左侧 -->
        <span class="menu-icon" onclick="openNav()">&#9776; Menu</span>
        
        <!-- H1 会因为父元素的 justify-content: center 而居中 -->
        <h1>Menu Management</h1>
    </div>

    <!-- 3. FOOD 区域：添加 id="food-section" -->
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
                    <!-- 图片路径现在包含分类文件夹 -->
                    <img src="images/<?php echo $row['image']; ?>" alt="">
                    <div class="card_text"><?php echo $row['i_name']; ?></div>
                    <p class="description"><?php echo $row['description']; ?></p>
                    <p class="price">$<?php echo number_format($row['price'], 2); ?></p>
                    <p class="stock">
                        Stock: <?php echo $row['stock']; ?> 
                    </p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-msg">No food items found.</p>
        <?php endif; ?>
    </div>

    <hr class="divider">

    <!-- DRINK 区域 -->
    <div class="section-header" id="drink-section">
        <h2 class="section-title">Drink Selection</h2>
        <a href="admin_add.php" class="add-item-btn">+ Add New Drink</a>
    </div>
    <div class="menu-container">
        <?php if (mysqli_num_rows($drink_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($drink_result)): ?>
                <div class="card">
                    <div class="admin-actions">
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                        <a href="delete.php?id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('确定删除吗？')">Delete</a>
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