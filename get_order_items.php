<?php
include("db.php");

// 仅在调试时开启，正式运行建议保持 error_reporting(0)
error_reporting(0); 

header('Content-Type: application/json');

if(isset($_GET['order_id'])) {
    $order_id = mysqli_real_escape_string($conn, $_GET['order_id']);
    
    // 确保 SQL 查询的字段名清楚
    $sql = "SELECT od.item_name, od.quantity, i.price 
            FROM order_details od 
            JOIN items i ON od.item_name = i.i_name 
            WHERE od.order_id = '$order_id'";
            
    $result = mysqli_query($conn, $sql);
    $items = [];
    
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) { // 修正变量名为 $result
            $items[] = [
                // 这里的键名（Key）必须和 JavaScript 里的 item.xxx 一致
                'i_name'   => $row['item_name'], 
                'price'    => $row['price'],
                'quantity' => $row['quantity']
            ];
        }
    }
    
    echo json_encode($items);
} else {
    echo json_encode(["error" => "No order_id provided"]);
}
?>