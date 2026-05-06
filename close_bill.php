<?php
session_start();
include("db.php");

// 权限检查
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.php");
    exit();
}

// 处理支付请求 (AJAX)
if (isset($_POST['process_payment'])) {
    $table_id = $_POST['table_id'];
    $payment_method = $_POST['payment_method'];

    // 将该桌子所有 PENDING 订单更新为 COMPLETED
    $update_sql = "UPDATE orders SET status = 'COMPLETED', payment_method = '$payment_method' 
                   WHERE table_number = '$table_id' AND status = 'PENDING'";
    
    if (mysqli_query($conn, $update_sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// 获取当前有订单的桌号，存入数组用于状态对比
$active_tables = [];
$check_query = "SELECT DISTINCT table_number FROM orders WHERE status = 'PENDING'";
$check_res = mysqli_query($conn, $check_query);
while($row = mysqli_fetch_assoc($check_res)) {
    $active_tables[] = (int)$row['table_number'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Counter</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 20px; }
        
        /* 桌子网格 */
        .table-sidebar { background: white; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .table-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px; }
        
        .table-btn { 
            padding: 20px 10px; border: 2px solid #f1f2f6; border-radius: 15px; 
            background: #f8f9fa; cursor: pointer; text-align: center; 
            transition: 0.3s; display: flex; flex-direction: column; gap: 5px;
        }
        
        /* 状态颜色：有单为橙色，选中为绿色，空闲为灰色 */
        .table-btn.has-order { border-color: #ffa502; color: #ffa502; background: #fff; }
        .table-btn.empty { opacity: 0.5; cursor: not-allowed; }
        .table-btn.active { border-color: #05c46b !important; background: #eafaf1 !important; color: #05c46b !important; transform: scale(1.05); }

        .status-dot { font-size: 0.7rem; font-weight: normal; }

        /* 右侧账单 */
        .bill-details { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); min-height: 500px; }
        
        /* 支付按钮样式 */
        .payment-methods { display: flex; gap: 10px; margin-top: 20px; }
        .method-btn { 
            flex: 1; padding: 15px; border: 2px solid #f1f2f6; border-radius: 12px; 
            background: white; cursor: pointer; font-weight: 700; transition: 0.2s; 
            display: flex; flex-direction: column; align-items: center; gap: 8px;
        }
        .method-btn.selected { border-color: #05c46b; background: #eafaf1; color: #05c46b; }
        
        .pay-now-btn { 
            width: 100%; margin-top: 20px; padding: 18px; background: #05c46b; 
            color: white; border: none; border-radius: 12px; font-size: 1.1rem; 
            font-weight: 800; cursor: pointer; display: none; box-shadow: 0 5px 15px rgba(5,196,107,0.3);
        }

        /* 收据 Overlay */
        #receiptOverlay { 
            position: fixed; top:0; left:0; width:100%; height:100%; 
            background: rgba(0,0,0,0.85); display: none; justify-content: center; 
            align-items: center; z-index: 3000; 
        }
        .receipt-paper { 
            background: white; width: 350px; padding: 40px 30px; border-radius: 5px; 
            font-family: 'Courier New', Courier, monospace; position: relative;
        }
    </style>
</head>
<body>

<div class="header">
    <span class="menu-icon" onclick="window.location.href='admin_menu.php'">&#9776; Back</span>
    <h1>Checkout Counter</h1>
    <div style="width:60px"></div>
</div>

<div class="checkout-container">
    <!-- 1. 10张桌子展示区 -->
    <div class="table-sidebar">
        <h3 class="section-title">Table Status</h3>
        <div class="table-grid">
            <?php for ($i = 1; $i <= 10; $i++): 
                $is_active = in_array($i, $active_tables);
            ?>
                <div class="table-btn <?php echo $is_active ? 'has-order' : 'empty'; ?>" 
                     id="btn-<?php echo $i; ?>"
                     onclick="<?php echo $is_active ? "loadBill($i, this)" : "alert('Table $i has no pending orders.')" ; ?>">
                    <span style="font-size: 1.2rem; font-weight: 800;">T-<?php echo $i; ?></span>
                    <span class="status-dot"><?php echo $is_active ? "● In Use" : "○ Empty"; ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- 2. 账单明细区 -->
    <div class="bill-details" id="billContent">
        <div style="text-align:center; color:#b2bec3; margin-top:150px;">
            <div style="font-size: 40px; margin-bottom: 10px;">🧾</div>
            <h3>Please select an active table</h3>
        </div>
    </div>
</div>

<div id="receiptOverlay">
    <div class="receipt-paper" id="receiptPaper"></div>
</div>

<script>
let currentTable = null;
let selectedMethod = null;

function loadBill(tableId, element) {
    currentTable = tableId;
    // UI 反馈
    document.querySelectorAll('.table-btn').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');

    // 动态加载账单
    fetch(`get_table_bill.php?table_id=${tableId}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('billContent').innerHTML = html;
        });
}

function selectMethod(method) {
    selectedMethod = method;
    document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('payBtn').style.display = 'block';
}

function processPayment() {
    if (!selectedMethod || !currentTable) return;

    const total = document.getElementById('finalTotal').innerText;
    const formData = new FormData();
    formData.append('process_payment', true);
    formData.append('table_id', currentTable);
    formData.append('payment_method', selectedMethod);

    fetch('close_bill.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showReceipt(total);
            }
        });
}

function showReceipt(total) {
    const overlay = document.getElementById('receiptOverlay');
    const paper = document.getElementById('receiptPaper');
    const now = new Date().toLocaleString();

    paper.innerHTML = `
        <div style="text-align:center; margin-bottom:20px;">
            <h2 style="margin:0; letter-spacing:2px;">RESTO POS</h2>
            <p style="margin:5px 0; font-size:0.8rem;">Kuala Lumpur, Malaysia</p>
            <div style="border-bottom:1px dashed #000; margin:10px 0;"></div>
            <p><strong>OFFICIAL RECEIPT</strong></p>
        </div>
        <p>DATE: ${now}</p>
        <p>TABLE: T-${currentTable}</p>
        <p>METHOD: ${selectedMethod.toUpperCase()}</p>
        <div style="border-bottom:1px dashed #000; margin:10px 0;"></div>
        <div style="display:flex; justify-content:space-between; font-size:1.4rem; font-weight:bold;">
            <span>TOTAL</span>
            <span>RM ${total}</span>
        </div>
        <div style="border-bottom:1px dashed #000; margin:10px 0;"></div>
        <div style="text-align:center; margin-top:30px;">
            <p>Thank You & Come Again!</p>
            <p style="background:#000; color:#fff; display:inline-block; padding:5px 10px;">
                Closing in <span id="timer">10</span>s
            </p>
        </div>
    `;

    overlay.style.display = 'flex';

    let seconds = 10;
    const interval = setInterval(() => {
        seconds--;
        document.getElementById('timer').innerText = seconds;
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.reload(); 
        }
    }, 1000);
}
</script>
</body>
</html>