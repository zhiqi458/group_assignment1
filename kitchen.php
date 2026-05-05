<?php include 'db.php'; ?>
<h2>Kitchen Dashboard</h2>
<div style="display: flex; flex-wrap: wrap;">
    <?php
    $orders = $conn->query("SELECT * FROM orders WHERE status='Pending'");
    while($order = $orders->fetch_assoc()) {
        echo "<div style='border:1px solid black; margin:10px; padding:10px; width:200px;'>
                <strong>Order ID: #{$order['id']}</strong><br>
                Table: {$order['table_number']}<hr>";
        
        $details = $conn->query("SELECT * FROM order_details WHERE order_id=".$order['id']);
        while($d = $details->fetch_assoc()) {
            echo "- {$d['item_name']} x {$d['quantity']} ({$d['remark']})<br>";
        }
        
        echo "<hr><a href='process.php?action=complete_order&id={$order['id']}' 
                 style='background:red; color:white; padding:5px; text-decoration:none;'>Delete (Complete)</a>
              </div>";
    }
    ?>
</div>