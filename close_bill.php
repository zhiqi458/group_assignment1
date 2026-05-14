<?php
session_start();
include("db.php");

// --- PHP 部分 ---
if (isset($_POST['action'])) {
    if (ob_get_length()) ob_end_clean(); 
    header('Content-Type: application/json');

    $table_id = mysqli_real_escape_string($conn, $_POST['table_id']);
    $action = $_POST['action'];

    if ($action === 'pay') {
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        
        // 核心：更新状态，记录支付方式
        // 只要状态变成 PAID，cashier.php 就能查到这条记录
        $sql = "UPDATE orders SET 
                status = 'PAID', 
                payment_method = '$payment_method',
                created_time = NOW() 
                WHERE table_number = '$table_id' AND status != 'PAID'";
                
    } else if ($action === 'cancel') {
        // 取消订单
        $sql = "UPDATE orders SET status = 'CANCELLED' 
                WHERE table_number = '$table_id' AND status != 'PAID'";
    }

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conn)]);
    }
    exit();
}

// --- 2. 获取有账单的桌子 (用于前端显示桌子是否亮起) ---
$active_tables = [];
// 状态同步改为 'PAID'
$check_query = "SELECT DISTINCT o.table_number 
                FROM orders o
                INNER JOIN order_details od ON o.id = od.order_id 
                WHERE o.status NOT IN ('Paid', 'CANCELLED')";
$check_res = mysqli_query($conn, $check_query);
if($check_res){
    while($row = mysqli_fetch_assoc($check_res)) {
        $active_tables[] = (int)$row['table_number'];
    }
}

// 在确认支付的按钮点击事件里

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern POS Checkout</title>
    <link rel="stylesheet" href="billstyle.css">
</head>
<body>

<div class="header">
    <button onclick="window.location.href='admin_menu.php'">⬅ Back to Admin</button>
    <h2 style="margin:0; letter-spacing:3px;">ARIELA POS SYSTEM</h2>
    <button onclick="window.location.href='cashier.php'" style="background:var(--success)">📊 Sales Record</button>
</div>

<div class="main-container">
    <div class="sidebar">
        <h3 style="margin-top:0; color:var(--primary);">Table Selection</h3>
        <p style="font-size: 0.9rem; color: #636e72;">Identify tables with active bills:</p>
        <div class="table-grid">
            <?php for($i=1; $i<=12; $i++): 
                $active = in_array($i, $active_tables);
            ?>
            <div class="table-btn <?php echo $active ? 'has-order' : 'empty'; ?>" 
                 id="btn-<?php echo $i; ?>"
                 onclick="<?php echo $active ? "loadBill($i)" : "" ?>">
                <strong>#<?php echo $i; ?></strong>
                <small><?php echo $active ? "READY" : "IDLE"; ?></small>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="bill-panel">
        <div id="billContent">
            <div style="text-align:center; color:#dfe6e9; margin-top:150px;">
                <h1 style="font-size: 6rem; margin:0;">🧾</h1>
                <p style="color:#b2bec3; font-size:1.1rem;">Select a table to display the receipt details</p>
            </div>
        </div>

        <!-- 支付方式选择区域 -->
        <div id="paymentArea" style="display:none;">
            <p style="margin-bottom:10px; font-weight:bold; color:#636e72;">SELECT PAYMENT METHOD</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div class="method-btn" onclick="selectMethod('Cash', this)">💵 Cash</div>
                <div class="method-btn" onclick="selectMethod('Card', this)">💳 Card</div>
                <div class="method-btn" onclick="selectMethod('TNG', this)">📱 TNG</div>
            </div>
            
            <button onclick="processPayment()" class="main-pay-btn">PROCESS PAYMENT</button>
            <button onclick="deleteOrder()" class="cancel-btn">Cancel Order</button>
        </div>

        <!-- 弹窗遮罩层 -->
        <div id="receiptOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
            <div id="receiptBox" style="background:white; width:350px; border-radius:15px; max-height:90vh; overflow-y:auto;">
                <!-- JS 会把内容填入这里 -->
    </div>
</div>

<!-- 打印专用隐藏容器 -->
<div id="print-section"></div>
    </div>
</div>

<div id="receiptOverlay">
    <div class="receipt-box" id="receiptBox"></div>
</div>

<script>
let currentTable = null;
let currentMethod = null;

// 1. 加载账单
function loadBill(tableId) {
    currentTable = tableId;
    currentMethod = null; 
    document.querySelectorAll('.table-grid .table-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(`btn-${tableId}`).classList.add('active');
    document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
    
    document.getElementById('billContent').innerHTML = '<p style="text-align:center;margin-top:150px;">Loading bill data...</p>';

    fetch(`get_table_bill.php?table_number=${tableId}`)
        .then(res => res.text())
        .then(html => { 
            document.getElementById('billContent').innerHTML = `<div id="billDetails">${html}</div>`;
            document.getElementById('paymentArea').style.display = 'block';
        });
}

// 2. 选择支付方式
// 2. 选择支付方式
function selectMethod(method, element) {
    const btns = document.querySelectorAll('.method-btn');
    btns.forEach(btn => btn.classList.remove('selected'));
    
    element.classList.add('selected');
    
    // 修改这里：从 selectedPaymentMethod 改为 currentMethod
    currentMethod = method; 
}

// 3. 点击大按钮：仅弹出收据预览，不改数据库
// 3. 点击大按钮：仅弹出收据预览
function processPayment() {
    if(!currentTable || !currentMethod) return alert("Please select both table and payment method!");
    
    const billContainer = document.getElementById('billDetails');
    // 关键点：这里选择器必须匹配 get_table_bill.php 输出的 HTML 结构
    const items = billContainer.querySelectorAll('div[style*="justify-content:space-between"], .bill-item'); 
    
    let itemsHtml = "";
    items.forEach(item => {
        // 过滤掉包含 "Total" 字样的行，只抓取菜品
        if(!item.innerText.includes("Total") && !item.innerText.includes("TOTAL")) {
            itemsHtml += `<div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem;">${item.innerHTML}</div>`;
        }
    });

    // 获取总额：匹配你 PHP 输出中 class 为 total-amount 的元素
    const totalElement = document.querySelector('.total-amount');
    const total = totalElement ? totalElement.innerText : "$0.00";

    // 弹出收据预览（预览框里会有最终结算按钮）
    showReceipt(total, itemsHtml);
}

// --- 取消订单函数 ---
function deleteOrder() {
    if(!currentTable) return;
    
    if(confirm(`Are you sure you want to CANCEL and DELETE order for Table #${currentTable}?`)) {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('table_id', currentTable);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert("Order cancelled successfully.");
                location.reload(); // 刷新页面，桌子会变回灰色无状态
            } else {
                alert("Error: " + data.msg);
            }
        });
    }
}

async function finalSettlePayment() {
    const formData = new FormData();
    formData.append('action', 'pay');
    formData.append('table_id', currentTable);
    formData.append('payment_method', currentMethod);

    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const text = await response.text();
        const result = JSON.parse(text);

        if (result.status) {
            alert("支付成功！");
            location.reload(); // 刷新后，Paid 状态的桌子会变回 IDLE 灰色
        } else {
            alert("错误: " + result.status);
        }
    } catch (e) {
        alert("网络或系统错误，请检查后端输出。");
    }
}



// 5. 显示收据函数
function showReceipt(total, itemsHtml) {
    const box = document.getElementById('receiptBox');
    const now = new Date();
    const dateString = now.toLocaleDateString();
    const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    box.innerHTML = `
        <div style="text-align:left; font-family:monospace; padding:20px; position:relative;">
            <div onclick="document.getElementById('receiptOverlay').style.display='none'" 
                 style="position:absolute; right:15px; top:10px; font-size:24px; cursor:pointer; color:#636e72; font-weight:bold;">
                 ×
            </div>

            <div style="text-align:center;">
                <h2 style="color:#27ae60; margin-bottom:5px;">PAYMENT CONFIRMATION</h2>
                <p style="color:#636e72; margin-top:0;">Please review the items below</p>
                <p>------------------------------------------</p>
            </div>

            <div style="display:flex; justify-content:space-between; margin:5px 0;"><span>TABLE:</span> <strong>#${currentTable}</strong></div>
            <div style="display:flex; justify-content:space-between; margin:5px 0;"><span>METHOD:</span> <strong>${currentMethod.toUpperCase()}</strong></div>
            <div style="display:flex; justify-content:space-between; margin:5px 0;"><span>TIME:</span> <strong>${dateString} ${timeString}</strong></div>
            
            <p>------------------------------------------</p>
            <div style="margin:15px 0;">
                ${itemsHtml}
            </div>
            <p>------------------------------------------</p>

            <div style="display:flex; justify-content:space-between; font-size:1.6rem; margin:20px 0; color:#130f40;">
                <span>TOTAL:</span> <strong>${total}</strong>
            </div>
            
            <p>------------------------------------------</p>
            <div style="text-align:center;">
                <p style="font-size:0.8rem; color:red;">* Click below to finalize the payment *</p>
                <button onclick="finalSettlePayment()" 
                        style="margin-top:15px; width:100%; padding:15px; background:#27ae60; color:white; border:none; border-radius:10px; cursor:pointer; font-weight:bold;">
                        DONE & FINISH
                </button>
            </div>
        </div>
    `;
    document.getElementById('receiptOverlay').style.display = 'flex';
}
<<<<<<< HEAD



=======
>>>>>>> 38ececc264a66b6cba388e052b4b9041b4e31c21
</script>
</body>
</html>