<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service Ordering System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="order-page-wrapper">

<!-- 1. 新增：左侧抽屉结构 -->

<div id="myDrawer" class="drawer">
    
    <?php if(isset($_SESSION["admin_user"])): ?>
    
    </div>
    <?php endif; ?>
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <div class="drawer-content">
        <h3>Categories</h3>
        <!-- 通过锚点跳转到不同区域 -->
        <a href="#food-section" onclick="closeNav()">🍱 Food Selection</a>
        <a href="#drink-section" onclick="closeNav()">🥤 Drinks & Beverages</a>
        <hr style="border: 0; border-top: 1px solid #333; margin: 20px 32px;">
        <!-- <a href="index.php">🏠 Home Page</a> -->
    </div>
</div>

<div class="container">
    <!-- 头部区域 -->
    <div class="header">
        <!-- 修改：点击触发打开抽屉函数 -->
        <div class="menu-icon" onclick="openNav()">☰</div> 
        <h1>🍴 Our Menu</h1>
        <p class="subtitle">Select your favorite dishes below</p>
    </div>

    <form action="process.php?action=place_order" method="POST">
        
        <!-- --- Food Section --- -->
        <div id="food-section" class="section-header">
            <h2 class="section-title">Food Selection</h2>
        </div>
        <div class="menu-grid">
            <?php
            // 只查询 FOOD 类别
            $items = $conn->query("SELECT * FROM items WHERE stock > 0 AND category = 'FOOD'");
            while($item = $items->fetch_assoc()) {
                renderMenuCard($item);
            }
            ?>
        </div>

        <!-- --- Drink Section --- -->
        <div id="drink-section" class="section-header" style="margin-top: 40px;">
            <h2 class="section-title">Drinks & Beverages</h2>
        </div>
        <div class="menu-grid">
            <?php
            // 只查询 DRINK 类别
            $items = $conn->query("SELECT * FROM items WHERE stock > 0 AND category = 'DRINK'");
            while($item = $items->fetch_assoc()) {
                renderMenuCard($item);
            }
            ?>
        </div>

        <?php
        // 定义一个函数减少重复代码
        function renderMenuCard($item) {
            $id = $item['id'];
            $image = !empty($item['image']) ? $item['image'] : 'default-food.jpg';
            $price = number_format($item['price'], 2);
            echo "
            <div class='menu-card'>
                <div class='menu-image'>
                    <img src='images/{$image}' alt='{$item['i_name']}'>
                </div>
                <div class='menu-info'>
                    <h3>{$item['i_name']}</h3>
                    <p class='description'>{$item['description']}</p>
                    <span class='price'>\${$price}</span>
                </div>
                <div class='menu-action'>
                    <div class='input-group'>
                        <label>Qty</label>
                        <input type='number' name='qty[$id]' value='0' min='0' 
                               class='qty-input' data-price='{$item['price']}'>
                    </div>
                    <div class='input-group'>
                        <label>Note</label>
                        <input type='text' name='remark[$id]' placeholder='No spicy...' class='remark-input'>
                    </div>
                    <input type='hidden' name='item_name[$id]' value='{$item['i_name']}'>
                </div>
            </div>";
        }
        ?>

        <!-- 固定在底部的结算栏 -->
        <div class="checkout-bar">
            <div class="table-info">
                <label>Table:</label>
                <select name="table_number" required class="table-select">
                    <option value="" disabled selected>Select Table</option>
                    <?php 
                    for ($i = 1; $i <= 12; $i++) {
                        echo "<option value='$i'>Table $i</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="total-display">
                Total: $<span id="total-amount">0.00</span>
            </div>
            <button type="submit" class="submit-btn">Place Order</button>
        </div>
    </form>
</div>

<script>
// --- 抽屉控制函数 ---
function openNav() {
    document.getElementById("myDrawer").style.width = "280px";
}

function closeNav() {
    document.getElementById("myDrawer").style.width = "0";
}

// --- 原有的计算逻辑 ---
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const totalDisplay = document.getElementById('total-amount');

    function calculateTotal() {
        let total = 0;
        qtyInputs.forEach(input => {
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            const qty = parseInt(input.value) || 0;
            total += price * qty;
        });
        totalDisplay.innerText = total.toFixed(2);
    }

    qtyInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
        input.addEventListener('change', calculateTotal);
    });
});
</script>

</body>
</html>