<?php
header('Content-Type: application/json');
include 'db_connection.php'; // 请确保此文件连接的是 admin_order 数据库

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 接收来自 JavaScript 的参数
    // 对应 JS 中的 body: `table_number=${currentTable}&payment_method=${currentMethod}`
    $table_number = $_POST['table_number'] ?? '';
    $method = $_POST['payment_method'] ?? 'Cash';

    if (empty($table_number)) {
        echo json_encode(['success' => false, 'message' => 'Table number is missing']);
        exit;
    }

    try {
        /* 
           2. 执行结算更新：
           - 将 orders 表中该桌子的 'Pending' 订单改为 'Paid'。
           - 这样该订单就会从收银台消失，并出现在 History 查询中（假设 History 查的是 Paid）。
        */
        $sql = "UPDATE orders 
                SET status = 'Paid', payment_method = ? 
                WHERE table_number = ? AND status = 'Pending'";
        
        $stmt = $conn->prepare($sql);
        
        // 如果你的 table_number 在数据库是 VARCHAR，使用 "ss"；如果是 INT，使用 "is"
        $stmt->bind_param("ss", $method, $table_number);
        $stmt->execute();

        // 检查是否有行受影响（即是否真的找到了并更新了订单）
        if ($stmt->affected_rows > 0) {
            
            /* 
               3. (可选) 如果你有单独的 tables 表来记录桌子状态
               根据你的 SQL 结构，目前状态主要存在 orders 表。
               如果你有 tables 表，可以开启下面的注释：
               
               $conn->query("UPDATE tables SET status = 'IDLE' WHERE table_number = '$table_number'");
            */

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No pending order found for table ' . $table_number]);
        }

        $stmt->close();

    } catch (Exception $e) {
        // 如果发生数据库错误，返回报错信息
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>