<?php
session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/DB.php';
require_once __DIR__ . '/../../../core/helpers.php';

CheckLogin();
$db = new DB();
$user_id = $getUser['id'];

// Lấy danh sách đơn hàng của user
$orders = $db->get_list("
    SELECT o.*, p.title AS project_title
    FROM orders o
    LEFT JOIN projects p ON o.project_id = p.id
    ORDER BY o.created_at DESC
");


?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn hàng của bạn</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="max-w-6xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Đơn hàng của bạn</h1>

    <?php if (empty($orders)): ?>
        <div class="text-gray-600">Bạn chưa có đơn hàng nào.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-200">
    <thead>
        <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-700">
            <th class="py-3 px-4 border-b">STT</th>
            <th class="py-3 px-4 border-b">Chiến dịch</th>
            <th class="py-3 px-4 border-b">Tên</th>
            <th class="py-3 px-4 border-b">Số điện thoại</th>
            <th class="py-3 px-4 border-b">Số tiền</th>
            <th class="py-3 px-4 border-b">Trạng thái</th>
            <th class="py-3 px-4 border-b">Thời gian</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $index => $order): 
        // Ẩn số điện thoại kiểu 012*****345
        $masked_phone = 'N/A';
        if (!empty($order['phone']) && strlen($order['phone']) >= 7) {
            $masked_phone = substr($order['phone'], 0, 3) . '*****' . substr($order['phone'], -3);
        }
    ?>
        <tr class="border-b hover:bg-gray-50">
            <td class="py-2 px-4"><?= $index + 1 ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($order['project_title']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($order['name']) ?></td>
            <td class="py-2 px-4"><?= $masked_phone ?></td>
            <td class="py-2 px-4 text-green-600 font-semibold"><?= number_format($order['amount']) ?> đ</td>
            <td class="py-2 px-4">
                <?php if ($order['status'] == 'completed'): ?>
                    <span class="text-green-600 font-medium">Đã thanh toán</span>
                <?php else: ?>
                    <span class="text-red-600 font-medium">Chưa thanh toán</span>
                <?php endif; ?>
            </td>
            <td class="py-2 px-4"><?= date("d/m/Y H:i", $order['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

        </div>
    <?php endif; ?>
</div>
</body>
</html>
