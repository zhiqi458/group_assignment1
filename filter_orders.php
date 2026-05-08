<?php
include("db.php");
$date = $_GET['date'] ?? date('Y-m-d');

// 1. 获取该日订单
$sql = "SELECT id, table_number, payment_method, DATE_FORMAT(created_time, '%H:%i') as time, total_price 
        FROM orders 
        WHERE status = 'Paid' AND DATE(created_time) = '$date'
        ORDER BY created_time DESC";
$res = mysqli_query($conn, $sql);

$orders = [];
$total_revenue = 0;

while($row = mysqli_fetch_assoc($res)) {
    $orders[] = [
        'id' => $row['id'],
        'table_number' => $row['table_number'],
        'method' => $row['payment_method'],
        'time' => $row['time'],
        'amount' => $row['total_price']
    ];
    $total_revenue += (float)$row['total_price'];
}

// 返回 JSON
header('Content-Type: application/json');
echo json_encode([
    'total_revenue' => $total_revenue,
    'orders' => $orders
]);
?>