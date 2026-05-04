<?php
include'db.php';

$orders = $conn->query("SELECT * FROM orders WHERE status='Pending'");
?>

<h1>Kitchen Dashboard</h1>
<?php while($order = $orders->fetch_assoc()): ?>
    <div style="border:1px solid #000; margin:10px; padding:10px;">
        <p><strong>Order ID: <?php echo $order['id']; ?></strong></p>
        <p>Table Number: <?php echo $order['table_number']; ?></p>
        <p>Total Price: $<?php echo number_format($order['total_price'], 2); ?></p>
        <a href="?complete=<?php echo $order['id']; ?>" style="background:gray; color:white; padding:5px;">Mark as Done</a>
    </div>
<?php endwhile; ?>