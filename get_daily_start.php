<?php
include("db.php");
header('Content-Type: application/json');

$date = $_GET['date'] ?? date('Y-m-d');
$date = mysqli_real_escape_string($conn, $date);

// 1. 查询该日总营业额
$revenue_sql = "SELECT SUM(total_price) as daily_total FROM orders 
                WHERE status = 'Paid' AND DATE(created_time) = '$date'";
$revenue_res = mysqli_query($conn, $revenue_sql);
$revenue_data = mysqli_fetch_assoc($revenue_res);
$revenue = $revenue_data['daily_total'] ?? 0;

// 2. 查询该日所有账单
$orders_sql = "SELECT id, table_number, payment_method, total_price, DATE_FORMAT(created_time, '%H:%i') as time 
               FROM orders 
               WHERE status = 'Paid' AND DATE(created_time) = '$date'
               ORDER BY created_time DESC";
$orders_res = mysqli_query($conn, $orders_sql);
$orders = [];

while($row = mysqli_fetch_assoc($orders_res)) {
    $orders[] = $row;
}

echo json_encode([
    'revenue' => $revenue,
    'orders' => $orders
]);
?>