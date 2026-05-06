<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header-order-section">
    <h2>Kitchen Dashboard</h2>
    <div class="refresh-timer">Auto-refreshing every 30 seconds...</div>
</div>

<div class="order-grid">
    <?php
    // Fetch pending orders, oldest first
    $orders = $conn->query("SELECT * FROM orders WHERE status='Pending' ORDER BY id ASC");
    
    if($orders->num_rows > 0):
        while($order = $orders->fetch_assoc()):
    ?>
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">#<?php echo $order['id']; ?></span>
                <span class="table-tag">TABLE <?php echo $order['table_number']; ?></span>
            </div>
            
            <div class="order-body">
                <?php
                $details = $conn->query("SELECT * FROM order_details WHERE order_id=".$order['id']);
                while($d = $details->fetch_assoc()):
                ?>
                    <div class="item-row">
                        <div>
                            <span class="item-name"><?php echo $d['item_name']; ?></span>
                            <?php if(!empty($d['remark'])): ?>
                                <span class="item-remark">Note: <?php echo $d['remark']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="item-qty">x<?php echo $d['quantity']; ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="order-footer">
                <a href="process.php?action=complete_order&id=<?php echo $order['id']; ?>" 
                   class="btn-done"
                   onclick="return confirm('Mark Order #<?php echo $order['id']; ?> as completed?')">
                    Complete Order
                </a>
            </div>
        </div>
    <?php 
        endwhile; 
    else:
    ?>
        <div class="no-orders">
            <p>No pending orders at the moment. Good job!</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Refresh page every 30 seconds to get new orders
    setTimeout(function(){
        window.location.reload();
    }, 30000);
</script>

</body>
</html>