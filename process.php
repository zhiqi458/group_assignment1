<?php
include 'db.php';

$action = $_GET['action'] ?? '';

// 开启异常处理模式
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($action == 'place_order') {
    $table = $_POST['table_number'] ?? 'Unknown';
    $total = 0;
    $items_ordered = []; 

    try {
        // --- 开启事务 ---
        $conn->begin_transaction();

        // 1. 先插入一个基础订单（总价暂设为0，后续更新）
        $stmt_order = $conn->prepare("INSERT INTO orders (table_number, total_price, status) VALUES (?, 0, 'Pending')");
        $stmt_order->bind_param("s", $table);
        $stmt_order->execute();
        $order_id = $conn->insert_id;

        // 准备详情和库存更新语句
        $stmt_detail = $conn->prepare("INSERT INTO order_details (order_id, item_name, quantity, remark) VALUES (?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");

        // 2. 遍历提交的菜品数据
        if (isset($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $id => $qty) {
                $qty = intval($qty);
                
                // 只有数量大于 0 才处理
                if ($qty > 0) {
                    $id = intval($id);
                    
                    // 【关键修复】从数据库重新查询价格和名称，防止前端篡改价格
                    $res = $conn->query("SELECT i_name, price FROM items WHERE id = $id");
                    $item_data = $res->fetch_assoc();

                    if ($item_data) {
                        $name = $item_data['i_name'];
                        $price = floatval($item_data['price']);
                        $remark = $_POST['remark'][$id] ?? '';

                        $line_total = $price * $qty;
                        $total += $line_total;

                        $items_ordered[] = [
                            'name' => $name,
                            'qty' => $qty,
                            'price' => $price,
                            'remark' => $remark
                        ];

                        // 执行：插入订单详情
                        $stmt_detail->bind_param("isis", $order_id, $name, $qty, $remark);
                        $stmt_detail->execute();

                        // 执行：扣减库存
                        $stmt_stock->bind_param("ii", $qty, $id);
                        $stmt_stock->execute();
                    }
                }
            }
        }

        // 3. 更新订单最终总价
        $stmt_update_total = $conn->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
        $stmt_update_total->bind_param("di", $total, $order_id);
        $stmt_update_total->execute();

        // 4. 提交事务
        $conn->commit();

        // 5. 调用渲染函数显示成功界面
        render_success_page($table, $total, $order_id, $items_ordered);

    } catch (Exception $e) {
        $conn->rollback();
        die("Order Failed: " . $e->getMessage());
    }
    exit();
}

// 完成订单（厨房端使用）
if ($action == 'complete_order') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE orders SET status='Completed' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: kitchen.php");
    exit();
}

/**
 * 成功页面渲染函数 (HTML + Tailwind CSS)
 */
function render_success_page($table, $total, $order_id, $items) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <title>Order Confirmed</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
        <div class="max-w-md w-full bg-white shadow-2xl rounded-3xl p-8 border border-gray-100">
            <!-- 成功动画图标 -->
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center shadow-inner">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            
            <h1 class="text-3xl font-extrabold text-center text-gray-800 mb-1">Order Received!</h1>
            <p class="text-gray-500 text-center mb-8">We are preparing your delicious meal.</p>
            
            <div class="bg-gray-50 rounded-2xl p-6 space-y-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400 font-medium">Order Reference</span>
                    <span class="font-mono font-bold text-indigo-600">#<?php echo $order_id; ?></span>
                </div>
                <div class="flex justify-between text-sm border-b border-gray-200 pb-4">
                    <span class="text-gray-400 font-medium">Table Number</span>
                    <span class="font-bold text-gray-800"><?php echo htmlspecialchars($table); ?></span>
                </div>

                <!-- 菜品列表 -->
                <div class="pt-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Your Selection</p>
                    <ul class="space-y-4">
                        <?php foreach ($items as $item): ?>
                        <li class="flex justify-between items-start">
                            <div class="flex flex-col pr-4">
                                <span class="text-gray-800 font-semibold leading-tight"><?php echo htmlspecialchars($item['name']); ?></span>
                                <?php if (!empty($item['remark'])): ?>
                                    <span class="text-[11px] text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded mt-1 w-fit">
                                        Note: <?php echo htmlspecialchars($item['remark']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-gray-600 font-bold text-sm whitespace-nowrap">x <?php echo $item['qty']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- 总额 -->
                <div class="border-t border-dashed border-gray-300 pt-6 mt-6 flex justify-between items-center">
                    <span class="font-bold text-gray-800 text-lg">Total Paid</span>
                    <span class="font-black text-2xl text-red-500">$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <!-- 操作按钮 -->
            <div class="mt-8 space-y-3">
                <a href="customer.php" class="block w-full bg-gray-900 hover:bg-black text-white text-center font-bold py-4 rounded-2xl transition duration-300 shadow-lg">
                    Order More
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>