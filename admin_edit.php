<?php
include("db.php");

// 1. 初始化变量，获取当前菜品的数据
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // 查询该 ID 对应的所有字段
    $result = mysqli_query($conn, "SELECT * FROM items WHERE id = $id");
    $item = mysqli_fetch_assoc($result);
    
    if (!$item) { 
        die("未找到该项目 (Item not found)."); 
    }
}

// 2. 处理表单提交的更新逻辑
if (isset($_POST['update_all'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    
    // 获取旧图片路径（防止没传新图时路径丢失）
    $db_save_path = $_POST['old_image'];

    // 检查是否有新图片上传
    if (!empty($_FILES['image']['name'])) {
        // 根据分类决定存放路径
        $sub_folder = ($category == "FOOD") ? "food/" : "drink/";
        $directory = "images/" . $sub_folder;

        // 如果文件夹不存在则创建
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $image_name = $_FILES['image']['name'];
        $target = $directory . basename($image_name);
        
        // 移动上传的文件
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // 更新数据库路径为相对路径：food/image.jpg
            $db_save_path = $sub_folder . $image_name;
        }
    }

    // 更新数据库中的所有字段
    $sql = "UPDATE items SET 
            i_name = '$name', 
            description = '$desc', 
            price = '$price', 
            category = '$category', 
            stock = '$stock', 
            image = '$db_save_path' 
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('所有信息更新成功！');
                window.location.href='admin_menu.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Full Item</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Edit Item Details</h1>
    </div>

    <div class="card" style="max-width: 500px; margin: 30px auto; padding: 20px;">
        <form method="POST" enctype="multipart/form-data">
            <!-- 隐藏域：保存 ID 和 旧图片路径 -->
            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
            <input type="hidden" name="old_image" value="<?php echo $item['image']; ?>">

            <label>Item Name:</label>
            <input type="text" name="name" value="<?php echo $item['i_name']; ?>" required>

            <label>Description:</label>
            <textarea name="description" rows="4"><?php echo $item['description']; ?></textarea>

            <label>Price ($):</label>
            <input type="number" step="0.01" name="price" value="<?php echo $item['price']; ?>" required>

            <label>Category:</label>
            <select name="category">
                <option value="FOOD" <?php echo ($item['category'] == 'FOOD') ? 'selected' : ''; ?>>Food</option>
                <option value="DRINK" <?php echo ($item['category'] == 'DRINK') ? 'selected' : ''; ?>>Drink</option>
            </select>

            <label>Current Stock:</label>
            <input type="number" name="stock" value="<?php echo $item['stock']; ?>" required>

            <label>Current Image:</label>
            <div style="margin: 10px 0;">
                <!-- 显示当前数据库里的图片 -->
                <img src="images/<?php echo $item['image']; ?>" width="120" style="border-radius: 8px; border: 1px solid #ddd;">
            </div>

            <label>Replace Image (Leave blank to keep current):</label>
            <input type="file" name="image">

            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

            <button type="submit" name="update_all">Update Everything</button>
            <a href="admin_menu.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">← Back to Menu</a>
        </form>
    </div>
</body>
</html>