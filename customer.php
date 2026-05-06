<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service Ordering System</title>
    <!-- 确保 style.css 路径正确 -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <!-- 头部区域：背景色和标题 -->
    <div class="header">
        <div class="menu-icon">☰</div> 
        <h1>🍴 Our Menu</h1>
        <p class="subtitle">Select your favorite dishes below</p>
    </div>

    <form action="process.php?action=place_order" method="POST">
        <div class="menu-grid">
            <?php
            $items = $conn->query("SELECT * FROM items WHERE stock > 0");
            while($item = $items->fetch_assoc()) {
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
        </div>

        <!-- 固定在底部的结算栏 -->
        <div class="checkout-bar">
            <div class="table-info">
                <label>Table:</label>
                <input type="number" name="table_number" placeholder="#" required>
            </div>
            
            <div class="total-display">
                Total: $<span id="total-amount">0.00</span>
            </div>

            <button type="submit" class="submit-btn">Place Order</button>
        </div>
    </form>
</div>

<script>
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