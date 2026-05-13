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
        <div class="search-container">
            <input type="text" id="menuSearch" placeholder="Search for food or drinks..." onkeyup="filterMenu()">
            <span class="search-icon">🔍</span>
        </div>
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
<!-- 订单确认弹窗 -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h2>Confirm Your Order</h2>
        <hr>
        <div id="order-summary-list">
            <!-- 这里会由 JS 动态填充已点菜品 -->
        </div>
        <div class="modal-total">
            Total: $<span id="modal-total-amount">0.00</span>
        </div>
        <div class="modal-actions">
            <button type="button" class="cancel-btn" onclick="closeModal()">Back to Menu</button>
            <button type="button" class="confirm-submit-btn" onclick="submitFinalOrder()">Confirm & Send</button>
        </div>
    </div>
</div>

<script>
    // 获取相关的 DOM 元素
const orderForm = document.querySelector('form');
const confirmModal = document.getElementById('confirmModal');
const summaryList = document.getElementById('order-summary-list');
const modalTotal = document.getElementById('modal-total-amount');

// 1. 拦截表单提交
orderForm.addEventListener('submit', function(e) {
    e.preventDefault(); // 阻止直接提交
    
    // 检查是否选择了桌号
    const tableSelect = document.querySelector('.table-select');
    if (!tableSelect.value) {
        alert("Please select a table number!");
        return;
    }

    // 生成订单清单
    let hasItems = false;
    let summaryHtml = '';
    const qtyInputs = document.querySelectorAll('.qty-input');
    
    qtyInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            hasItems = true;
            const itemId = input.name.match(/\d+/)[0]; // 获取ID
            const itemName = document.querySelector(`input[name="item_name[${itemId}]"]`).value;
            const price = parseFloat(input.getAttribute('data-price'));
            const subtotal = (price * qty).toFixed(2);
            
            summaryHtml += `
                <div class="modal-summary-item">
                    <span><strong>${qty}x</strong> ${itemName}</span>
                    <span>$${subtotal}</span>
                </div>`;
        }
    });

    if (!hasItems) {
        alert("Your cart is empty!");
        return;
    }

    // 填充内容并显示弹窗
    summaryList.innerHTML = summaryHtml;
    modalTotal.innerText = document.getElementById('total-amount').innerText;
    confirmModal.style.display = "block";
});

// 2. 关闭弹窗函数
function closeModal() {
    confirmModal.style.display = "none";
}

// 3. 最终确认提交
function submitFinalOrder() {
    orderForm.submit(); // 调用原生的提交方法
}

// 点击背景也可以关闭
window.onclick = function(event) {
    if (event.target == confirmModal) {
        closeModal();
    }
}
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
function filterMenu() {
    const input = document.getElementById('menuSearch');
    const filter = input.value.toLowerCase();
    const menuCards = document.querySelectorAll('.menu-card');
    const sections = document.querySelectorAll('.section-header, .menu-grid');

    menuCards.forEach(card => {
        // 获取菜名和描述文字
        const itemName = card.querySelector('h3').innerText.toLowerCase();
        const itemDesc = card.querySelector('.description').innerText.toLowerCase();

        if (itemName.includes(filter) || itemDesc.includes(filter)) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });

    // 可选逻辑：如果搜索时某个分类下没有东西，隐藏分类标题 (Food Selection / Drinks)
    const foodGrid = document.querySelector('#food-section + .menu-grid');
    const drinkGrid = document.querySelector('#drink-section + .menu-grid');
    
    toggleSectionVisibility('food-section', foodGrid);
    toggleSectionVisibility('drink-section', drinkGrid);
}

function toggleSectionVisibility(sectionId, gridElement) {
    const sectionHeader = document.getElementById(sectionId);
    // 检查该 grid 下是否还有可见的卡片
    const hasVisibleItems = gridElement.querySelectorAll('.menu-card:not(.hidden)').length > 0;
    
    if (hasVisibleItems) {
        sectionHeader.style.display = "block";
        gridElement.style.display = "grid";
    } else {
        sectionHeader.style.display = "none";
        gridElement.style.display = "none";
    }
}
</script>

</body>
</html>