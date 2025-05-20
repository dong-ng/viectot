<?php
// baocao.php

// Ẩn Notices
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

// Khởi session nếu chưa active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../core/DB.php';
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';

CheckLogin();
CheckAdmin();

$db = new DB();
$conn = $db->connect();

// 1) Lấy danh sách dự án
$projects = $db->get_list("SELECT id, title FROM projects ORDER BY title ASC");

// 2) Tìm các năm có giao dịch
$years = $db->get_list("SELECT DISTINCT YEAR(FROM_UNIXTIME(created_at)) AS year FROM orders ORDER BY year DESC");
$minYear = !empty($years) ? min(array_column($years, 'year')) : date('Y');
$currentYear = date('Y');

// 3) Nhận bộ lọc
$project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
$month      = isset($_GET['month'])      ? (int) $_GET['month']      : 0;
$year       = isset($_GET['year'])       ? (int) $_GET['year']       : 0;
$search     = isset($_GET['search'])     ? trim($_GET['search'])     : '';

// 4) Xử lý duyệt đơn
if (isset($_POST['approve_order']) && isset($_POST['order_id'])) {
    $order_id = (int) $_POST['order_id'];
    $order = $db->get_row("SELECT status FROM orders WHERE id = {$order_id}");
    if ($order && $order['status'] !== 'completed') {
        $db->query("UPDATE orders SET status = 'completed' WHERE id = {$order_id}");
    }
}

// 5) Build WHERE cho bảng orders
$where = "";
if ($project_id > 0) {
    $where .= " AND o.project_id = {$project_id}";
}
if ($month > 0) {
    $where .= " AND MONTH(FROM_UNIXTIME(o.created_at)) = {$month}";
}
if ($year > 0) {
    $where .= " AND YEAR(FROM_UNIXTIME(o.created_at)) = {$year}";
}
if ($search !== '') {
    $esc = $conn->real_escape_string($search);
    $where .= " AND o.name LIKE '%{$esc}%'";
}
$where = $where ? "WHERE 1=1" . $where : "";

// 6) Tính tổng số giao dịch (total_count) từ bảng orders
$total_count = $db->get_row("
    SELECT COUNT(*) AS total_count
    FROM orders o
    {$where}
")['total_count'];

// 7) Tính tổng số tiền (total_amount) từ bảng projects
$where_projects = '';
if ($project_id > 0) {
    $where_projects = "WHERE id = {$project_id}";
}
$total_amount = $db->get_row("
    SELECT IFNULL(SUM(raised), 0) AS total_amount
    FROM projects
    {$where_projects}
")['total_amount'];

// Gộp lại vào $summary
$summary = [
    'total_amount' => $total_amount,
    'total_count' => $total_count
];

// 8) Query chi tiết (thêm status)
$orders = $db->get_list("
    SELECT
        o.id,
        p.title     AS project_title,
        o.name      AS donor_name,
        o.email,
        o.phone,
        o.amount,
        o.status,
        FROM_UNIXTIME(o.created_at, '%Y-%m-%d %H:%i:%s') AS created_at
    FROM orders o
    LEFT JOIN projects p ON o.project_id = p.id
    {$where}
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Báo cáo đóng góp | Hệ thống quản trị</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .sidebar { transition: all 0.3s; }
        .sidebar-collapsed { width: 80px; }
        .sidebar-collapsed .sidebar-text { display: none; }
        .sidebar-collapsed .logo-text { display: none; }
        .sidebar-collapsed .logo-icon { margin-left: 0; }
        .content-area { transition: all 0.3s; }
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .table-row:hover {
            background-color: #f8fafc;
        }
        .filter-input {
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        .filter-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- END SIDEBAR -->

        <!-- MAIN CONTENT -->
        <div class="content-area flex-1 flex flex-col overflow-hidden">
            <!-- HEADER -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-chart-pie mr-2 text-indigo-600"></i>
                            Báo cáo đóng góp
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="../index.php" class="text-gray-600 hover:text-indigo-600 transition-colors" title="Trang chủ">
                            <i class="fas fa-home"></i>
                        </a>
                    </div>
                </div>
            </header>
            <!-- END HEADER -->

            <!-- MAIN SECTION -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- SUMMARY CARD -->
                    <div class="card p-6 mb-6 bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-100">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800 mb-1">Tổng hợp đóng góp</h2>
                                <p class="text-sm text-gray-600">Thống kê theo bộ lọc hiện tại</p>
                            </div>
                            <div class="mt-4 md:mt-0 flex space-x-KILL6">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Tổng số giao dịch</p>
                                    <p class="text-2xl font-bold text-indigo-600"><?= $summary['total_count'] ?: 0 ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Tổng số tiền</p>
                                    <p class="text-2xl font-bold text-indigo-600"><?= number_format($summary['total_amount'] ?: 0) ?> ₫</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FILTER CARD -->
                    <div class="card p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Bộ lọc báo cáo</h2>
                        <form method="get" action="/index.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <input type="hidden" name="module" value="admin">
                            <input type="hidden" name="action" value="baocao">

                            <!-- Dự án -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Dự án</label>
                                <select name="project_id" class="filter-input w-full rounded p-2">
                                    <option value="0"<?= $project_id === 0 ? ' selected' : '' ?>>Tất cả dự án</option>
                                    <?php foreach($projects as $pr): ?>
                                        <option value="<?= $pr['id'] ?>"
                                            <?= ((int)$pr['id'] === $project_id) ? ' selected' : '' ?>>
                                            <?= htmlspecialchars($pr['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tháng -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Tháng</label>
                                <select name="month" class="filter-input w-full rounded p-2">
                                    <option value="0"<?= $month === 0 ? ' selected' : '' ?>>Tất cả tháng</option>
                                    <?php for($m=1;$m<=12;$m++): ?>
                                        <option value="<?= $m ?>"<?= $m === $month ? ' selected' : '' ?>>Tháng <?= $m ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Năm -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Năm</label>
                                <select name="year" class="filter-input w-full rounded p-2">
                                    <option value="0"<?= $year === 0 ? ' selected' : '' ?>>Tất cả năm</option>
                                    <?php foreach($years as $y): ?>
                                        <option value="<?= $y['year'] ?>"<?= $y['year'] === $year ? ' selected' : '' ?>><?= $y['year'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tìm theo tên người đóng -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text- gray-700">Tìm theo tên</label>
                                <div class="relative">
                                    <input type="text" name="search"
                                        value="<?= htmlspecialchars($search) ?>"
                                        placeholder="Nhập tên người đóng góp..."
                                        class="filter-input w-full rounded p-2 pl-10" />
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                            </div>

                            <!-- Nút submit -->
                            <div class="md:col-span-4 flex justify-end space-x-3">
                                <button type="submit"
                                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm transition-colors">
                                    <i class="fas fa-filter mr-2"></i> Áp dụng bộ lọc
                                </button>
                                <a href="?module=admin&action=baocao"
                                class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md shadow-sm transition-colors">
                                    <i class="fas fa-sync-alt mr-2"></i> Đặt lại
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- DATA TABLE CARD -->
                    <div class="card overflow-hidden">
                        <div class="px-6 py-4 border-b flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800">Chi tiết đóng góp</h2>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500">
                                    <?= count($orders) ?> kết quả
                                </span>
                                <button class="p-2 text-gray-500 hover:text-indigo-600 rounded-full hover:bg-gray-100">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dự án</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người đóng góp</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Liên hệ</th>
                                        <th scope="col" class="px-0 py-0 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                                        <th scope="col" class="px-0 py-0 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if($orders): ?>
                                        <?php foreach($orders as $o): ?>
                                            <tr class="table-row">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $o['id'] ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($o['project_title']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($o['donor_name']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <div class="flex flex-col">
                                                        <span><?= htmlspecialchars($o['email']) ?></span>
                                                        <span class="text-indigo-600"><?= htmlspecialchars($o['phone']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-0 py-0 whitespace-nowrap text-sm font-bold text-indigo-600"><?= number_format($o['amount']) ?> ₫</td>
                                                <td class="px-0 py-0 whitespace-nowrap text-sm text-gray-500"><?= $o['created_at'] ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span class="px-2 py-1 rounded text-xs font-medium
                                                        <?= $o['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                            ($o['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                            'bg-red-100 text-red-800') ?>">
                                                        <?= ucfirst($o['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <?php if($o['status'] !== 'completed'): ?>
                                                        <form method="post">
                                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                            <button type="submit" name="approve_order"
                                                                class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-xs">
                                                                Duyệt
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">Đã duyệt</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center text-gray-400">
                                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                                    <p class="text-lg font-medium">Không có dữ liệu</p>
                                                    <p class="text-sm mt-1">Thử thay đổi bộ lọc để xem kết quả khác</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if($orders): ?>
                            <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    Hiển thị <span class="font-medium">1</span> đến <span class="font-medium"><?= count($orders) ?></span> của <span class="font-medium"><?= count($orders) ?></span> kết quả
                                </div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border rounded text-gray-600 bg-white disabled:opacity-50" disabled>
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button class="px-3 py-1 border rounded text-gray-600 bg-white disabled:opacity-50" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <!-- END MAIN SECTION -->
        </div>
        <!-- END MAIN CONTENT -->
    </div>
</body>
</html>