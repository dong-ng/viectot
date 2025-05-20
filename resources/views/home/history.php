<?php
define("IN_SITE", true);
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';
session_start();
CheckLogin();
global $getUser;

$db = new DB();
$conn = $db->connect();

// Lấy email user từ session và escape
$user_email = $getUser['email'];
$safe_email = mysqli_real_escape_string($conn, $user_email);

// Query lịch sử quyên góp
$history = $db->get_list("
    SELECT 
        p.title AS project_title,
        o.amount AS amount,
        o.message AS message,
        o.created_at AS created_at,
        o.status AS status
    FROM orders o
    LEFT JOIN projects p ON o.project_id = p.id
    WHERE o.email = '{$safe_email}'
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử giao dịch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .status-pending { color: #f59e0b; }
        .status-completed { color: #10b981; }
        .status-failed { color: #ef4444; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
        <?php include 'nav.php'?>

    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900">Lịch sử giao dịch</h1>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <?php if (empty($history)): ?>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-info-circle text-gray-400 text-3xl mb-4"></i>
                <p class="text-gray-600">Bạn chưa có giao dịch quyên góp nào.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chiến dịch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lời nhắn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $stt = 1; foreach ($history as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $stt++ ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($row['project_title'] ?? 'Không xác định') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= number_format($row['amount']) ?>₫
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($row['message'] ?? 'Không có lời nhắn') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d/m/Y H:i', $row['created_at']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="status-<?= strtolower($row['status']) ?>">
                                            <?php 
                                                $status = strtolower($row['status']);
                                                if ($status == 'pending') echo 'Đang chờ thanh toán';
                                                elseif ($status == 'completed') echo 'Hoàn thành';
                                                elseif ($status == 'failed') echo 'Thất bại';
                                                else echo 'Không xác định';
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

        <?php include 'footer.php'?>

</body>
</html>