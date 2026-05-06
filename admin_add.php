<?php
include("db.php");

// --- 保持你原本的 PHP 逻辑不变 ---
if (isset($_GET['action']) && $_GET['action'] == 'place_order') {
    $table_number = $_POST['table_number'];
    $qtys = $_POST['qty'];
    $remarks = $_POST['remark'];
    $prices = $_POST['price'];
    $names = $_POST['item_name'];

    foreach ($qtys as $id => $qty) {
        if ($qty > 0) {
            $name = $names[$id];
            $price = $prices[$id];
            $remark = $remarks[$id];
            $total = $qty * $price;

            $sql = "INSERT INTO orders (table_number, item_name, quantity, price, total_price, remark, status) 
                    VALUES ('$table_number', '$name', '$qty', '$price', '$total', '$remark', 'Unpaid')";
            mysqli_query($conn, $sql);
            mysqli_query($conn, "UPDATE items SET stock = stock - $qty WHERE id = $id");
        }
    }
    echo "<script>alert('Order Placed!'); window.location.href='cashier.php';</script>";
}

if (isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    
    $sub_folder = ($category == "FOOD") ? "food/" : "drink/";
    $directory = "images/" . $sub_folder;
    $image = $_FILES['image']['name'];
    $target = $directory . basename($image);

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $db_save_path = $sub_folder . $image;
    $sql = "INSERT INTO items (i_name, description, price, category, stock, image) 
            VALUES ('$name', '$desc', '$price', '$category', '$stock', '$db_save_path')";

    if (mysqli_query($conn, $sql)) {
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            echo "<script>alert('菜品添加成功！'); window.location.href='admin_menu.php';</script>";
        } else {
            echo "<script>alert('图片上传失败');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Menu Item</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="menu-icon" onclick="window.location.href='admin_menu.php'">✕</div>
        <h1>Add New Menu Item</h1>
    </div>
    
    <!-- 使用新的容器名，避免冲突 -->
    <div class="modern-form-wrapper">
        <div class="modern-form-card">
            <form method="POST" enctype="multipart/form-data">
                <h2 class="form-section-title">Item Details</h2>
                
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" placeholder="Enter item name" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="What's special about this dish?"></textarea>
                </div>

                <div class="form-row">
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
                    <input type="number" name="stock" placeholder="Enter quantity" required>
                </div>

                <div class="form-group">
                    <label>Upload Image</label>
                    <input type="file" name="image" class="file-input" required>
                </div>

                <button type="submit" name="add_item" class="submit-full-btn">Add To Menu</button>
                
                <a href="admin_menu.php" class="cancel-link">Cancel and Return</a>
            </form>
        </div>
    </div>
</body>
</html>