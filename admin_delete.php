<?php
include("db.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. (可选) 获取图片路径，以便在删除数据库记录前先删除文件夹里的图片文件
    $result = mysqli_query($conn, "SELECT image FROM items WHERE id = $id");
    $item = mysqli_fetch_assoc($result);
    
    if ($item) {
        $image_path = "images/" . $item['image'];
        
        // 如果文件确实存在，则执行物理删除
        if (file_exists($image_path) && !empty($item['image'])) {
            unlink($image_path);
        }
    }

    // 2. 执行数据库删除指令
    $sql = "DELETE FROM items WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('Item deleted successfully!');
                window.location.href='admin_menu.php';
              </script>";
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    // 如果没有收到 ID，直接返回主页
    header("Location: admin_menu.php");
}
?>