<?php
include("db.php");

if (isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category']; // FOOD 或 DRINK
    $stock = $_POST['stock'];
    
    // --- 动态路径修改开始 ---
    // 根据选择的分类决定存放在 images/food/ 还是 images/drink/
    $sub_folder = ($category == "FOOD") ? "food/" : "drink/";
    $directory = "images/" . $sub_folder;
    // --- 动态路径修改结束 ---

    $image = $_FILES['image']['name'];
    $target = $directory . basename($image);

    // 如果分类文件夹不存在，则自动创建
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    // 数据库中仍然建议只存文件名，或者存相对路径
    // 这里为了方便管理，我们在数据库存入包含分类的路径：food/image.jpg
    $db_save_path = $sub_folder . $image;

    $sql = "INSERT INTO items (i_name, description, price, category, stock, image) 
            VALUES ('$name', '$desc', '$price', '$category', '$stock', '$db_save_path')";

    if (mysqli_query($conn, $sql)) {
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            echo "<script>
                    alert('菜品添加成功，已存入 " . $category . " 目录！');
                    window.location.href='admin_menu.php';
                  </script>";
        } else {
            echo "<script>alert('图片移动失败，请检查文件夹权限');</script>";
        }
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Menu Item</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Add New Menu Item</h1>
    </div>
    
    <div class="card">
        <!-- enctype 必须保留，否则无法上传图片 -->
        <form method="POST" enctype="multipart/form-data">
            
            <label>Item Name:</label>
            <input type="text" name="name" placeholder="Enter item name" required>

            <label>Description:</label>
            <textarea name="description" placeholder="Enter item description"></textarea>

            <label>Price:</label>
            <input type="number" step="0.01" name="price" placeholder="8.00" required>

            <label>Category:</label>
            <select name="category">
                <!-- 这里的 value 必须和数据库查询语句中的 'FOOD' 和 'DRINK' 完全一样 -->
                <option value="FOOD">Food</option>
                <option value="DRINK">Drink</option>
            </select>

            <label>Initial Stock:</label>
            <input type="number" name="stock" placeholder="Enter stock quantity" required>

            <label>Upload Image:</label>
            <input type="file" name="image" required>

            <!-- 重要：去掉按钮里的 <a> 标签，否则表单不会提交 -->
            <button type="submit" name="add_item">Add Item</button>
            
            <a href="admin_menu.php" class="back-link">Back To Menu Page</a>
            
        </form>
    </div>
</body>
</html>