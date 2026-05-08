<?php
session_start();
include("db.php");

// 1. 获取所有已支付订单，按时间倒序排列
$sql = "SELECT o.id, o.table_number, o.payment_method, o.created_time,
               o.total_price as total_amount
        FROM orders o
        WHERE o.status = 'Paid'
        ORDER BY o.created_time DESC";

$result = mysqli_query($conn, $sql);

// 2. 计算今日总营业额
$today_date = date('Y-m-d');
$revenue_sql = "SELECT SUM(total_price) as daily_total 
                FROM orders 
                WHERE status = 'Paid' AND DATE(created_time) = '$today_date'";
$revenue_res = mysqli_query($conn, $revenue_sql);
$revenue_data = mysqli_fetch_assoc($revenue_res);
$daily_total = (float)($revenue_data['daily_total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier - Sales History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    /* 侧边栏（Drawer）基础样式 */
    .drawer {
        height: 100%;
        width: 0; /* 初始宽度为0，隐藏 */
        position: fixed;
        z-index: 1000;
        top: 0;
        right: 0;
        background-color: #fff;
        overflow-x: hidden;
        transition: 0.5s; /* 滑动动画 */
        padding-top: 60px;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
    }

    .drawer-content { padding: 20px; }
    
    .close-btn {
        position: absolute;
        top: 20px;
        left: 25px;
        font-size: 36px;
        cursor: pointer;
        color: #636e72;
    }

    /* 激活日历的按钮样式 */
    .btn-open-calendar {
        background: #0984e3;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* 日历容器 */
    #calendar-container { margin-top: 20px; }


        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .cashier-container { max-width: 1100px; margin: 0 auto; }
        
        /* 统计卡片 */
        .stats-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 5px solid #00b894; }
        .stat-card h3 { margin: 0; color: #636e72; font-size: 0.9rem; text-transform: uppercase; }
        .stat-card p { margin: 10px 0 0; font-size: 1.8rem; font-weight: bold; color: #2d3436; }

        /* 面板与表格 */
        .history-panel { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; color: #636e72; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #2d3436; }
        
        /* 日期分组栏 */
        .date-divider { background: #dfe6e9; padding: 10px 20px; font-weight: bold; color: #2d3436; font-size: 0.9rem; }
        .history-row { display: none; background: #fafafa; } /* 默认隐藏历史记录 */

        .method-tag { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .cash { background: #e1f5fe; color: #0288d1; }
        .card { background: #ede7f6; color: #512da8; }
        .tng { background: #fff3e0; color: #ef6c00; }
        
        .btn-back { background: #636e72; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; }
        .btn-history { background: #0984e3; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }

        @media print {
            body * { visibility: hidden; }
            #print-section, #print-section * { visibility: visible !important; }
            #print-section { position: absolute; left: 0; top: 0; width: 100%; display: block !important; }
        }
        #print-section { display: none; }

        /* 让日期选择器看起来更像一个浮动的小窗口 */
        .flatpickr-calendar {
            box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important;
        }

        /* 之前已有的 history-row 逻辑保持不变 */
        .history-row { display: none; background: #fafafa; }
    </style>
</head>
<body>

<div class="cashier-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <!-- 顶部导航栏 -->
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        
        <!-- 左侧：返回按钮 -->
        <div style="flex: 1; display: flex; justify-content: flex-start;">
            <a href="close_bill.php" class="btn-back" style="display: inline-flex; align-items: center; white-space: nowrap;">⬅ Back</a>
        </div>

        <!-- 中间：标题 -->
        <div style="flex: 2; text-align: center;">
            <h1 style="margin: 0; font-size: 1.8rem; color: #2d3436; white-space: nowrap;">
                Cashier Management
            </h1>
        </div>

        <!-- 右侧：日期选择器 -->
        <div style="flex: 1; display: flex; justify-content: flex-end;">
            <div style="position: relative;">
                <button id="calendar-trigger" class="btn-history" style="background: #0984e3; white-space: nowrap;">
                    📅 Select Date
                </button>
                <input type="text" id="datepicker" style="position: absolute; opacity: 0; width: 100%; height: 100%; left: 0; top: 0; cursor: pointer;">
            </div>
        </div>

    </div>

    <!-- 下方的表格区域会自动继承 cashier-container 的宽度 -->
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse;">
            <!-- 这里放你之前的 <thead> 和 <tbody> -->
        </table>
    </div>
</div>

    <div class="stats-row">
        <div class="stat-card">
            <h3>Today's Revenue</h3>
            <p>$ <?php echo number_format($daily_total, 2); ?></p>
        </div>
        <div class="stat-card" style="border-left-color: #0984e3;">
            <h3>Today's Date</h3>
            <p><?php echo date('M d, Y'); ?></p>
        </div>
        <div class="stat-card" style="border-left-color: #6c5ce7;">
            <h3>Store Status</h3>
            <p>OPEN</p>
        </div>
    </div>

    <div class="history-panel">
        
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Table</th>
                    <th>Time Paid</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $current_date = ""; 
                if(mysqli_num_rows($result) > 0): 
                    while($row = mysqli_fetch_assoc($result)): 
                        $order_date = date('Y-m-d', strtotime($row['created_time']));
                        $is_today = ($order_date == $today_date);
                        
                        // 如果日期改变，显示日期分割条
                        if ($order_date != $current_date): 
                            $current_date = $order_date;
                ?>
                    <!-- 找到这一行，确保它有 history-row 类，方便我们控制 -->
                    <tr class="date-row <?php echo $is_today ? '' : 'history-row'; ?>" style="display: table-row;">
                        <td colspan="6" class="date-divider">
                            📅 <?php echo $is_today ? "TODAY (" . $order_date . ")" : $order_date; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                    <tr class="<?php echo $is_today ? 'today-row' : 'history-row'; ?>" <?php echo $is_today ? '' : 'style="display:none;"'; ?>>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td>Table <?php echo $row['table_number']; ?></td>
                        <td><?php echo date('H:i', strtotime($row['created_time'])); ?></td>
                        <td>
                            <span class="method-tag <?php echo strtolower($row['payment_method']); ?>">
                                <?php echo $row['payment_method'] ?: 'N/A'; ?>
                            </span>
                        </td>
                        <td style="font-weight: bold; color: #00b894;">
                            $ <?php echo number_format((float)$row['total_amount'], 2); ?>
                        </td>
                        <td>
                            <button onclick="printReceipt('<?php echo $row['id']; ?>', '<?php echo $row['table_number']; ?>', '<?php echo $row['payment_method']; ?>', '<?php echo number_format($row['total_amount'], 2); ?>')" 
                                    style="padding: 5px 12px; background: #6c5ce7; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                Print 🖨️
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="print-section"></div>

<!-- 引入 Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化日历
    flatpickr("#datepicker", {
        dateFormat: "Y-m-d",
        maxDate: "today", // 不能选未来
        disableMobile: "true", // 强制使用插件样式而非原生手机日历
        onChange: function(selectedDates, dateStr) {
            jumpToDate(dateStr);
        }
    });
});

function jumpToDate(dateStr) {
    // 1. 获取所有的行（包括日期分割条和订单行）
    const allRows = document.querySelectorAll('tbody tr');
    let found = false;

    // 2. 首先隐藏所有行
    allRows.forEach(row => {
        row.style.display = 'none';
    });

    // 3. 遍历寻找匹配的日期
    // 逻辑：找到日期分割条后，显示该分割条及其紧随其后的订单行
    const dividers = document.querySelectorAll('.date-divider');
    dividers.forEach(divider => {
        if (divider.innerText.includes(dateStr)) {
            found = true;
            const dividerRow = divider.parentElement;
            
            // 显示当前的日期分割条
            dividerRow.style.display = 'table-row';

            // 显示该日期下的所有订单行
            // 逻辑：循环显示接下来的行，直到遇到下一个分割条为止
            let nextRow = dividerRow.nextElementSibling;
            while (nextRow && !nextRow.querySelector('.date-divider')) {
                nextRow.style.display = 'table-row';
                nextRow = nextRow.nextElementSibling;
            }

            // 滚动到该位置
            dividerRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    if (!found) {
        alert("No records found for " + dateStr);
        // 如果没找到，可以选择显示回今天的记录，或者保持空白
    }
}

function showTodayOnly() {
    const todayDate = "<?php echo $today_date; ?>"; // 获取 PHP 传来的今天日期
    jumpToDate(todayDate);
}

// 保留你原来的全显/全隐函数
function toggleHistory() {
    const historyRows = document.querySelectorAll('.history-row');
    const isHidden = historyRows.length > 0 && historyRows[0].style.display === 'none';
    historyRows.forEach(row => {
        row.style.display = isHidden ? 'table-row' : 'none';
    });
}



// ... 你之前的 printReceipt 等其他函数 ...

// 打印功能 (保持你之前的逻辑)
async function printReceipt(orderId, tableNum, method, totalAmount) {
    try {
        const response = await fetch(`get_order_items.php?order_id=${orderId}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const items = await response.json();
        
        let itemsHtml = "";
        // 修正：确保在循环内部使用 item (单数)
        // cashier.php 中的循环部分
        items.forEach(item => {
            // 这里的 item.i_name 对应 PHP 里的 'i_name'
            let name = item.i_name || "Unknown";
            let price = parseFloat(item.price) || 0;
            let qty = parseInt(item.quantity) || 0;
            let subtotal = (price * qty).toFixed(2);
            
            itemsHtml += `
                <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                    <span>${name} x${qty}</span>
                    <span>$${subtotal}</span>
                </div>`;
        });

        const printSection = document.getElementById('print-section');
        printSection.innerHTML = `
            <div style="width: 300px; margin: 20px auto; padding: 20px; font-family: monospace; border: 1px solid #000; background: #fff; color: #000;">
                <div style="text-align:center;">
                    <h2 style="margin-bottom:5px;">PAYMENT RECEIPT</h2>
                    <p>Ariela Restaurant</p>
                    <hr>
                </div>
                <p>ORDER: #${orderId} | TABLE: #${tableNum}</p>
                <p>METHOD: ${method ? method.toUpperCase() : 'N/A'}</p>
                <hr>
                ${itemsHtml}
                <hr>
                <h3 style="display:flex; justify-content:space-between;"><span>TOTAL:</span> <span>$ ${totalAmount}</span></h3>
                <div style="text-align:center; font-size:0.8rem; margin-top:10px;">Thank you!</div>
            </div>`;

        // 给一点点时间渲染 HTML 之后再调用打印
        setTimeout(() => { 
            window.print(); 
        }, 300);

    } catch (e) { 
        console.error("Detailed Error:", e); // 这会在控制台显示具体哪里错了
        alert("Print Error: " + e.message); 
    }
}
// 5. 显示收据函数（包含打印逻辑）
function showReceipt(total, itemsHtml) {
    const box = document.getElementById('receiptBox');
    const now = new Date();
    const dateString = now.toLocaleDateString();
    const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    // 关键点：如果 itemsHtml 是从之前的 processPayment 传过来的
    // 请确保 itemsHtml 里的 innerHTML 已经被正确替换
    
    box.innerHTML = `
        <div style="text-align:left; font-family:monospace; padding:20px; position:relative; color:#000;">
            <div onclick="document.getElementById('receiptOverlay').style.display='none'" 
                 style="position:absolute; right:15px; top:10px; font-size:24px; cursor:pointer; color:#636e72; font-weight:bold;">
                 ×
            </div>

            <div style="text-align:center;">
                <h2 style="margin-bottom:5px;">PAYMENT RECEIPT</h2>
                <p style="margin-top:0;">Ariela Restaurant</p>
                <p>------------------------------------------</p>
            </div>

            <div style="display:flex; justify-content:space-between; margin:5px 0;"><span>TABLE:</span> <strong>#${currentTable}</strong></div>
            <div style="display:flex; justify-content:space-between; margin:5px 0;"><span>METHOD:</span> <strong>${currentMethod ? currentMethod.toUpperCase() : 'N/A'}</strong></div>
            <div style="display:flex; justify-content:space-between; margin:5px 0;"><span>TIME:</span> <strong>${dateString} ${timeString}</strong></div>
            
            <p>------------------------------------------</p>
            <div style="margin:15px 0; min-height:50px;">
                ${itemsHtml} <!-- 这里的 HTML 必须在生成时就确定数据正确 -->
            </div>
            <p>------------------------------------------</p>

            <div style="display:flex; justify-content:space-between; font-size:1.6rem; margin:20px 0;">
                <span>TOTAL:</span> <strong>${total}</strong>
            </div>
            
            <p>------------------------------------------</p>
            <div style="text-align:center;">
                <button onclick="finalSettlePayment()" id="settleBtn"
                        style="margin-top:15px; width:100%; padding:15px; background:#27ae60; color:white; border:none; border-radius:10px; cursor:pointer; font-weight:bold;">
                        DONE & FINISH
                </button>
            </div>
        </div>
    `;
    document.getElementById('receiptOverlay').style.display = 'flex';
}
</script>
</body>
</html>