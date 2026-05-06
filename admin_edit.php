<?php
include("db.php");

// 1. 初始化变量，获取当前菜品的数据
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = mysqli_query($conn, "SELECT * FROM items WHERE id = $id");
    $item = mysqli_fetch_assoc($result);
    
    if (!$item) { 
        die("未找到该项目 (Item not found)."); 
    }
}

// 2. 处理表单提交的更新逻辑
if (isset($_POST['update_all'])) {
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    
    $db_save_path = $_POST['old_image'];

    if (!empty($_FILES['image']['name'])) {
        $sub_folder = ($category == "FOOD") ? "food/" : "drink/";
        $directory = "images/" . $sub_folder;

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $image_name = $_FILES['image']['name'];
        $target = $directory . basename($image_name);
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $db_save_path = $sub_folder . $image_name;
        }
    }

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
    <span class="menu-icon" onclick="window.location.href='admin_menu.php'">&#9776; Back</span>
    <h1>Edit Item Details</h1>
    <div style="width: 60px;"></div> <!-- 保持标题居中的占位 -->
</div>

<div class="modern-form-wrapper">
    <div class="modern-form-card">
        <form method="POST" enctype="multipart/form-data">
            <!-- 隐藏域 -->
            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
            <input type="hidden" name="old_image" value="<?php echo $item['image']; ?>">

            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="name" value="<?php echo $item['i_name']; ?>" placeholder="e.g. Nasi Lemak" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe the item..."><?php echo $item['description']; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price (RM)</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $item['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="FOOD" <?php echo ($item['category'] == 'FOOD') ? 'selected' : ''; ?>>Food</option>
                        <option value="DRINK" <?php echo ($item['category'] == 'DRINK') ? 'selected' : ''; ?>>Drink</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Current Stock</label>
                <input type="number" name="stock" value="<?php echo $item['stock']; ?>" required>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <div class="current-image-preview">
                    <img src="images/<?php echo $item['image']; ?>" alt="Current">
                    <span>File: <?php echo basename($item['image']); ?></span>
                </div>
            </div>

            <div class="form-group">
                <label>Replace Image</label>
                <input type="file" name="image" accept="image/*">
                <small style="color: #a4b0be; font-size: 0.65rem;">Leave blank to keep current photo</small>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" name="update_all" class="submit-full-btn">Save Changes</button>
                <a href="admin_menu.php" class="cancel-link">Cancel and Go Back</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>