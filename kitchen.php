<?php include 'db.php'; ?>

<?php 
if(isset($_POST['action'])) {
    $orders = $conn->query("SELECT orders.id, orders.table_number, GROUP_CONCAT(CONCAT(order_details.item_name, ' x', order_details.quantity)) as items, MIN(orders.created_time) as created_time FROM orders 
    INNER JOIN order_details ON orders.id = order_details.order_id
    WHERE orders.status='Pending' GROUP BY orders.id ORDER BY orders.id ASC");
    $result = mysqli_fetch_array($orders);
    $json_class=new stdClass();
    while($row = mysqli_fetch_assoc($orders)) {
        $json_class->orders[] = $row;

        $update = $conn->query("UPDATE orders SET orders.status='In Progress' WHERE id=".$row['id']);

    }


    echo json_encode($json_class);
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header-order-section" style="display: flex; justify-content: center; align-items: center; text-align: center; padding: 20px 0;">
    <div>
        <h2 style="margin-bottom: 8px; font-size: 2rem;">Kitchen Dashboard</h2>
        
        <div id="live-clock" style="color: #6c5ce7; font-weight: bold; font-family: monospace; font-size: 1.2rem; margin-bottom: 8px;">
            Loading date & time...
        </div>

        <!-- <div class="refresh-timer" style="color: #636e72; font-size: 1rem;">
            Auto-refreshing in <span id="countdown" style="color: #e17055; font-weight: bold;">2</span>s...
        </div> -->
    </div>
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
                <span class="order-time" style="font-size: 0.8rem; opacity: 0.8;">
                    Sent: <?php echo date('H:i', strtotime($order['created_time'])); ?>
                </span>
                <span class="table-tag">TABLE <?php echo $order['table_number']; ?></span>
            </div>
            
            <div class="order-body">
                <?php
                $details = $conn->query("SELECT * FROM order_details WHERE order_id=".$order['id']);
                while($d = $details->fetch_assoc()):
                ?>
                    <div class="item-row">
                        <div >
                            <span onclick="tickmenu(this)" class="item-name"><?php echo $d['item_name']; ?></span>
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
                   onclick="return confirm('Mark Order #<?php echo $order['id']; ?> as Completed?')">
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
    function updateClock() {
        const now = new Date();
        
        // 格式化日期：例如 2026/05/07
        const date = now.toLocaleDateString('zh-CN', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit',
            weekday: 'long' 
        });
        
        // 格式化时间：例如 20:28:45
        const time = now.toLocaleTimeString('en-GB', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });

        document.getElementById('live-clock').innerHTML = date + " | " + time;
    }

    // 每秒更新一次时钟
    setInterval(updateClock, 200);
    updateClock(); // 初始化显示

    // 倒计时刷新逻辑
    let timeLeft = 2;
    const countdownElement = document.getElementById('countdown');

    function tickmenu(e){
        e.style.backgroundColor="green";
    }

   function loadkitchen(){
    const menu=document.querySelector(".order-grid");
        const formData=new FormData();
        formData.append('action','post');
        fetch(window.location.href,{method:'POST',body:formData}
        ).then(res=>{
            return res.json();
        }).then(datas=>{
            // const kitchenList=data;
            orders=JSON.parse(JSON.stringify(datas.orders));
            
            orders.forEach((data)=>{ 
                
                const food_details=data.items.split(",");
                menu.innerHTML+=`
                <div class="order-card">
                <div class="order-header">
                    <span class="order-id">#${data.id}</span>
                    <span class="order-time" style="font-size: 0.8rem; opacity: 0.8;">
                        Sent: ${data.created_time}
                    </span>
                    <span class="table-tag">TABLE ${data.table_number}</span>
                </div>
                <div class="order-body">
                ${food_details.map(food=>`
                    <div class="item-row">
                        <div>
                            <span class="item-name">${food.split(" x")[0]}</span>
                        </div>
                        <div class="item-qty">x${food.split(" x")[1]}</div>
                    </div>
                `).join("")}
                </div>
                <div class="order-footer">
                    <a href="process.php?action=complete_order&id=${data.id}" 
                       class="btn-done"
                       onclick="return confirm('Mark Order #${data.id} as Completed?')">
                        Complete Order
                    </a>
                </div>`;
            });
        });
   }
   loadkitchen();
   setInterval(loadkitchen,5000);
</script>

</body>
</html>