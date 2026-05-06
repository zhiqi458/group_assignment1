<?php
    // 1. 开启 Session 并检查权限
    session_start();
    include("db.php");

    // 如果未登录，直接拦截
    if (!isset($_SESSION['admin_user'])) {
        header("Location: login.php");
        exit();
    }

    // 2. 获取日期过滤逻辑
    $today = date('Y-m-d');
    $selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

    // 3. 统计数据：总单数、总金额 (仅统计 COMPLETED 状态)
    $stats_query = "SELECT COUNT(*) as total_orders, SUM(total_price) as total_revenue 
                    FROM orders 
                    WHERE status = 'COMPLETED' AND DATE(created_time) = '$selected_date'";
    $stats_res = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_res);

    // 4. 获取详细交易记录
    $sql = "SELECT * FROM orders 
            WHERE status = 'COMPLETED' AND DATE(created_time) = '$selected_date' 
            ORDER BY created_time DESC";
    $result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier - Sales Report</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- 专门针对 Cashier 页面的补充样式 --- */
        .report-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .date-filter {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .date-filter label {
            font-weight: 700;
            color: #636e72;
        }

        .date-filter input[type="date"] {
            padding: 10px 15px;
            border: 2px solid #f1f2f6;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
        }

        .date-filter input:focus {
            border-color: #05c46b;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-bottom: 4px solid #dfe6e9;
        }

        .summary-card.highlight {
            border-bottom: 4px solid #05c46b;
        }

        .summary-card .label {
            font-size: 0.8rem;
            color: #b2bec3;
            text-transform: uppercase;
            font-weight: 700;
        }

        .summary-card .value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #2d3436;
            margin-top: 8px;
        }

        .summary-card.highlight .value {
            color: #05c46b;
        }

        .sales-list {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #636e72;
            border-bottom: 2px solid #f1f2f6;
        }

        .modern-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f2f6;
        }

        .status-badge.paid {
            background: #eafaf1;
            color: #27ae60;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .print-mini-btn {
            background: #f1f2f6;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            transition: 0.2s;
        }

        .print-mini-btn:hover {
            background: #2d3436;
            color: white;
        }

        @media (max-width: 600px) {
            .summary-grid { grid-template-columns: 1fr; }
            .modern-table th:nth-child(3), .modern-table td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header 与 Admin Menu 保持一致 -->
    <div class="header">
        <span class="menu-icon" onclick="window.location.href='admin_menu.php'" style="cursor:pointer;">&#9776; Back</span>
        <h1>Sales & Receipts</h1>
        <div class="login-link">
            <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
        </div>
    </div>

    <div class="report-container">
        <!-- 日期筛选 -->
        <div class="date-filter">
            <form method="GET" id="filterForm">
                <label>Report Date:</label>
                <input type="date" name="date" value="<?php echo $selected_date; ?>" onchange="document.getElementById('filterForm').submit()">
            </form>
        </div>

        <!-- 汇总卡片 -->
        <div class="summary-grid">
            <div class="summary-card">
                <span class="label">Total Orders</span>
                <span class="value"><?php echo $stats['total_orders']; ?></span>
            </div>
            <div class="summary-card highlight">
                <span class="label">Today's Revenue</span>
                <span class="value">RM <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></span>
            </div>
        </div>

        <!-- 交易明细 -->
        <div class="sales-list">
            <h3 style="margin-bottom:20px; font-size:1.1rem;">Transaction History</h3>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Table</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="time-small"><?php echo date('H:i', strtotime($row['created_time'])); ?></td>
                                <td><strong>#<?php echo $row['table_number']; ?></strong></td>
                                <td><span class="status-badge paid"><?php echo $row['status']; ?></span></td>
                                <td class="price-text">RM <?php echo number_format($row['total_price'], 2); ?></td>
                                <td>
                                    <button class="print-mini-btn" onclick="printReceipt(<?php echo htmlspecialchars(json_encode($row)); ?>)">Receipt</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px; color:#a4b0be;">No records found for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function printReceipt(data) {
        const receiptWindow = window.open('', '_blank', 'width=300,height=600');
        receiptWindow.document.write(`
            <html>
            <head>
                <style>
                    body { font-family: monospace; padding: 20px; line-height: 1.4; }
                    .center { text-align: center; }
                    .line { border-top: 1px dashed #000; margin: 10px 0; }
                    .row { display: flex; justify-content: space-between; margin: 5px 0; }
                </style>
            </head>
            <body>
                <div class="center">
                    <h2 style="margin:0;">RESTAURANT</h2>
                    <p>Official Receipt</p>
                </div>
                <div class="line"></div>
                <div class="row"><span>Date:</span> <span>${data.created_time.split(' ')[0]}</span></div>
                <div class="row"><span>Table:</span> <span>#${data.table_number}</span></div>
                <div class="row"><span>Order ID:</span> <span>#${data.id}</span></div>
                <div class="line"></div>
                <div class="row" style="font-weight:bold; font-size:1.2rem;">
                    <span>TOTAL</span>
                    <span>RM ${parseFloat(data.total_price).toFixed(2)}</span>
                </div>
                <div class="line"></div>
                <div class="center" style="margin-top:20px;">
                    <p>Thank you for dining with us!</p>
                </div>
            </body>
            </html>
        `);
        receiptWindow.document.close();
        receiptWindow.focus();
        setTimeout(() => { 
            receiptWindow.print(); 
            receiptWindow.close(); 
        }, 250);
    }
    </script>
</body>
</html>