<?php
include("db.php");

$table_number = isset($_GET['table_number']) ? mysqli_real_escape_string($conn, $_GET['table_number']) : '';

// 建议增加 i.price 的获取
$sql = "SELECT od.item_name, i.price, od.quantity 
        FROM order_details od
        JOIN orders o ON od.order_id = o.id
        JOIN items i ON i.i_name = od.item_name
        WHERE o.table_number = '$table_number' AND o.status != 'PAID'";

$result = mysqli_query($conn, $sql);
$grand_total = 0;

echo "<h3>Table #$table_number Bill Details</h3>";
// 增加一个 id="billDetails" 方便你之前的 JS 抓取内容
echo "<div id='billDetails' style='padding:0;'>";

if($result && mysqli_num_rows($result) > 0) {
    
    // 只使用一个循环
    while($row = mysqli_fetch_assoc($result)) {
        // 数据处理
        $price = (float)($row['price'] ?? 0); 
        $qty = (int)($row['quantity'] ?? 0);
        $subtotal = $price * $qty;
        
        // 累加总额
        $grand_total += $subtotal;
        
        // 打印每一行菜品（符合你 JS 抓取的结构）
        echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">';
        echo '  <span>' . htmlspecialchars($row['item_name']) . ' <small>x' . $qty . '</small></span>';
        echo '  <span style="font-weight:bold;">$' . number_format($subtotal, 2) . '</span>';
        echo '</div>';
    }

} else {
    echo "<div>No active orders found.</div>";
}

echo "</div>"; // 结束 billDetails
echo "<hr>";
echo "<div style='display:flex; justify-content:space-between; font-size:1.4rem; font-weight:bold;'>
        <span>GRAND TOTAL</span>
        <span class='total-amount'>$" . number_format($grand_total, 2) . "</span>
      </div>";
?>