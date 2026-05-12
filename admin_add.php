<?php
include("db.php");

// ==========================================
// 逻辑 A：处理下单 (从 Cashier 页面传来)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'place_order') {
    $table_number = mysqli_real_escape_string($conn, $_POST['table_number']);
    $qtys = $_POST['qty'] ?? [];
    $prices = $_POST['price'] ?? []; // 确保这里收到了价格数组
    $names = $_POST['item_name'] ?? [];
    $remarks = $_POST['remark'] ?? [];

    // 1. 创建主订单记录
    $order_sql = "INSERT INTO orders (table_number, total_price, status) VALUES ('$table_number', 0, 'PENDING')";
    if (!mysqli_query($conn, $order_sql)) {
        die("Order Creation Failed: " . mysqli_error($conn));
    }
    $new_order_id = mysqli_insert_id($conn);

    $grand_total = 0;

    // 2. 循环处理菜品
    foreach ($qtys as $id => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $name = mysqli_real_escape_string($conn, $names[$id]);
            // 核心修复：确保 price 存在且为数字
            $price = isset($prices[$id]) ? (float)$prices[$id] : 0; 
            $remark = mysqli_real_escape_string($conn, $remarks[$id]);
            $subtotal = $qty * $price;
            $grand_total += $subtotal;

            // 写入详情表
            $detail_sql = "INSERT INTO order_details (order_id, item_name, price, quantity, remark) 
                           VALUES ('$new_order_id', '$name', '$price', '$qty', '$remark')";
            mysqli_query($conn, $detail_sql);

            // 更新库存
            mysqli_query($conn, "UPDATE items SET stock = stock - $qty WHERE id = '$id'");
        }
    }

    // 3. 更新主表总价
    mysqli_query($conn, "UPDATE orders SET total_price = '$grand_total' WHERE id = '$new_order_id'");

    echo "<script>alert('Order Placed Successfully! Total: RM" . number_format($grand_total, 2) . "'); window.location.href='cashier.php';</script>";
    exit();
}

// ==========================================
// 逻辑 B：处理添加新菜品 (当前页面表单提交)
// ==========================================
if (isset($_POST['add_item'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $category = $_POST['category'];
    $stock = (int)$_POST['stock'];
    
    $sub_folder = ($category == "FOOD") ? "food/" : "drink/";
    $directory = "images/" . $sub_folder;
    $image = $_FILES['image']['name'];
    $target = $directory . basename($image);

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $db_save_path = $sub_folder . $image;
        // 注意：这里使用的是 i_name (匹配你之前的 get_table_bill.php 查询)
        $sql = "INSERT INTO items (i_name, description, price, category, stock, image) 
                VALUES ('$name', '$desc', '$price', '$category', '$stock', '$db_save_path')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Item added successfully!'); window.location.href='admin_menu.php';</script>";
        } else {
            echo "<script>alert('Database Error: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Image upload failed. Check folder permissions.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Menu Item</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <div class="header" style="background:#2d3436; color:white; padding:20px; text-align:center; position:relative;">
        <span style="position:absolute; left:20px; cursor:pointer;" onclick="window.location.href='admin_menu.php'">✕</span>
        <h1 style="margin:0;">Add New Menu Item</h1>
    </div>
    
    <div class="modern-form-wrapper">
        <div class="modern-form-card">
            <form method="POST" enctype="multipart/form-data">
                <h2 style="margin-top:0; color:#6c5ce7;">Item Details</h2>
                
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" placeholder="e.g. Chocolate Cookie" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the item..."></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="FOOD">Food</option>
                            <option value="DRINK">Drink</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Initial Stock</label>
                    <input type="number" name="stock" placeholder="100" required>
                </div>

                <div class="form-group">
                    <label>Upload Image</label>
                    <input type="file" name="image" required>
                </div>

                <button type="submit" name="add_item" class="submit-full-btn">Create Item</button>
            </form>
        </div>
    </div>
</body>
</html>