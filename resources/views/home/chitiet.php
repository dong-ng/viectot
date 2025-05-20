<?php
session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

global $NNL;

// Lấy ID dự án từ URL
if (isset($_GET['id'])) {
    $project_id = (int)$_GET['id'];
} else {
    die('Không tìm thấy ID dự án!');
}

// Truy vấn thông tin dự án, join với bảng users để lấy tên và email người dùng
$project = $NNL->get_row("
    SELECT p.*, u.name AS user_name, u.email AS user_email 
    FROM projects p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE p.id = $project_id
");
if (!$project) {
    die('Dự án không tồn tại hoặc đã bị xóa.');
}

// Sử dụng raised trực tiếp từ bảng projects
$project['raised'] = $project['raised'] ?? 0;

// Kiểm tra trạng thái hoàn thành
$isCompleted = $project['raised'] >= $project['goal'];

// Kiểm tra trạng thái quá hạn
$isOverdue = !$isCompleted && $project['end_date'] && strtotime($project['end_date']) < time();

// Xử lý nội dung description
$description = htmlspecialchars($project['description'] ?? 'Đây là phần mô tả chi tiết hơn về dự án...');
// Chia nội dung thành các đoạn dựa trên \r\n\r\n
$paragraphs = explode("\r\n\r\n", $description);

// Hàm để kiểm tra và định dạng đoạn văn
function formatParagraph($paragraph) {
    if (strpos($paragraph, '–') === 0) {
        $items = explode("\r\n", $paragraph);
        $output = '<ul class="space-y-2">';
        foreach ($items as $item) {
            if (trim($item) !== '') {
                $item = trim(str_replace('–', '', $item));
                if (strpos($item, ':') !== false) {
                    list($title, $content) = explode(':', $item, 2);
                    $output .= '<li class="flex"><span class="font-medium text-gray-700 mr-2">' . trim($title) . ':</span> <span class="text-gray-600">' . nl2br(trim($content)) . '</span></li>';
                } else {
                    $output .= '<li class="text-gray-600">' . nl2br($item) . '</li>';
                }
            }
        }
        $output .= '</ul>';
        return $output;
    } elseif (preg_match('/(http|https):\/\/[^\s]+/', $paragraph, $matches)) {
        $link = $matches[0];
        $paragraph = str_replace($link, '<a href="' . $link . '" target="_blank" class="text-green-600 hover:text-green-800 font-medium">' . $link . '</a>', $paragraph);
        return '<p class="text-gray-700">' . nl2br($paragraph) . '</p>';
    } else {
        return '<p class="text-gray-700">' . nl2br($paragraph) . '</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết dự án - <?php echo htmlspecialchars($project['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Source+Sans+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .progress-container {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        .project-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .project-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            transform: translateY(-2px);
        }
        .tab-button {
            position: relative;
            padding-bottom: 0.5rem;
        }
        .tab-button.active:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 3px 3px 0 0;
        }
        .donor-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .donor-table thead th {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            z-index: 10;
        }
        .donor-table tr:not(:last-child) td {
            border-bottom: 1px solid #e2e8f0;
        }
        .modal-enter {
            animation: modal-enter 0.3s ease-out;
        }
        @keyframes modal-enter {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="/" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-green-600">
                        <i class="fas fa-home mr-2"></i>
                        Trang chủ
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400"></i>
                        <a href="/home/projects" class="ml-1 text-sm font-medium text-gray-500 hover:text-green-600 md:ml-2">Dự án</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400"></i>
                        <span class="ml-1 text-sm font-medium text-green-600 md:ml-2"><?php echo htmlspecialchars($project['title']); ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="bg-white rounded-xl shadow-sm project-card overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
                <!-- Project Image -->
                <div class="relative rounded-xl overflow-hidden h-96">
                    <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-full object-cover">
                    <?php if ($isCompleted): ?>
                        <div class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                            <i class="fas fa-check-circle mr-1"></i> Hoàn thành
                        </div>
                    <?php elseif ($isOverdue): ?>
                        <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                            <i class="fas fa-clock mr-1"></i> Quá hạn
                        </div>
                    <?php else: ?>
                        <div class="absolute top-4 right-4 bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                            <i class="fas fa-spinner mr-1"></i> Đang tiến hành
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Project Info -->
                <div class="space-y-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($project['title']); ?></h1>
                        <p class="text-gray-500 mt-2">Tạo bởi: <?php echo htmlspecialchars($project['user_name'] ?? 'Không xác định'); ?></p>
                    </div>

                    <!-- Progress Section -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Tiến độ quyên góp</span>
                            <span class="text-sm font-semibold text-green-600">
                                <?php 
                                    $progress = ($project['goal'] > 0) ? min(($project['raised'] / $project['goal']) * 100, 100) : 0;
                                    $progress_rounded = round($progress);
                                    echo $progress_rounded; 
                                ?>%
                            </span>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $progress_rounded; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="font-semibold text-green-600"><?php echo number_format($project['raised']); ?>đ</span>
                            <span class="text-gray-500">Mục tiêu: <?php echo number_format($project['goal']); ?>đ</span>
                        </div>
                    </div>

                    <!-- Key Info Cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <div class="text-gray-500 text-sm font-medium mb-1">Ngày bắt đầu</div>
                            <div class="text-gray-900 font-semibold">
                                <?php echo date('d/m/Y', strtotime($project['start_date'])); ?>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <div class="text-gray-500 text-sm font-medium mb-1">Ngày kết thúc</div>
                            <div class="text-gray-900 font-semibold">
                                <?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <div class="text-gray-500 text-sm font-medium mb-1">Địa điểm</div>
                            <div class="text-gray-900 font-semibold">
                                <?php echo htmlspecialchars($project['address'] ?? 'Không có thông tin'); ?>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <div class="text-gray-500 text-sm font-medium mb-1">Liên hệ</div>
                            <div class="text-gray-900 font-semibold">
                                <?php echo htmlspecialchars($project['user_email'] ?? 'Không xác định'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4">
                        <?php if (!$isCompleted && !$isOverdue): ?>
                            <?php if (CheckLogin()): ?>
                                <button onclick="openDonateModal()" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center">
                                    <i class="fas fa-heart mr-2"></i>
                                    Ủng hộ ngay
                                </button>
                            <?php else: ?>
                                <a href="/home/login" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Đăng nhập để ủng hộ
                                </a>
                            <?php endif; ?>
                        <?php elseif ($isCompleted): ?>
                            <a href="/home/report/<?php echo $project_id; ?>" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 quanhọc rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center">
                                <i class="fas fa-file-alt mr-2"></i>
                                Xem báo cáo chi tiết
                            </a>
                        <?php else: ?>
                            <div class="w-full bg-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg shadow-sm flex items-center justify-center">
                                <i class="fas fa-times-circle mr-2"></i>
                                Dự án đã quá hạn
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 px-6">
                <nav class="-mb-px flex space-x-8">
                    <button id="btnNoiDung" class="tab-button active py-4 px-1 text-sm font-medium text-green-600" onclick="showTab('noiDung')">
                        <i class="fas fa-align-left mr-2"></i>Nội dung dự án
                    </button>
                    <button id="btnDanhSach" class="tab-button py-4 px-1 text-sm font-medium text-gray-500 hover:text-green-600" onclick="showTab('danhSach')">
                        <i class="fas fa-users mr-2"></i>Danh sách ủng hộ
                    </button>
                    <?php if ($isCompleted): ?>
                        <button id="btnBaoCao" class="tab-button py-4 px-1 text-sm font-medium text-gray-500 hover:text-green-600" onclick="showTab('baoCao')">
                            <i class="fas fa-file-invoice mr-2"></i>Báo cáo chi tiết
                        </button>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Project Content -->
                <div id="noiDung" class="space-y-6">
                    <?php
                    foreach ($paragraphs as $index => $paragraph) {
                        if (trim($paragraph) === '') continue;
                        echo formatParagraph($paragraph);
                    }
                    ?>
                </div>

                <!-- Donation List -->
                <div id="danhSach" class="hidden">
                    <div class="mb-6">
                        <div class="relative max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="searchDonorInput" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-green-500 focus:border-green-500" placeholder="Tìm kiếm người ủng hộ...">
                        </div>
                    </div>
                    
                    <?php
                    $orders = $NNL->get_list("SELECT * FROM orders WHERE project_id = $project_id AND status = 'completed' ORDER BY created_at DESC");
                    if ($orders): ?>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table id="donationsTable" class="min-w-full divide-y divide-gray-200 donor-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người ủng hộ</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($orders as $order):
                                    $donorName = ($order['anonymous'] == 1) ? 'Nhà hảo tâm ẩn danh' : htmlspecialchars($order['name']);
                                    $amount = number_format($order['amount']) . 'đ';
                                    $time = date('H:i:s - d/m/Y', $order['created_at']); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-green-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $donorName; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-green-600"><?php echo $amount; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo $time; ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-users-slash text-gray-300 text-4xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900">Chưa có ai ủng hộ dự án này</h3>
                            <p class="mt-1 text-sm text-gray-500">Hãy là người đầu tiên ủng hộ dự án ý nghĩa này!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isCompleted): ?>
                    <div id="baoCao" class="hidden">
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Dự án đã hoàn thành thành công!</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>Dự án đã đạt được mục tiêu quyên góp và đã được thực hiện. Dưới đây là báo cáo chi tiết về việc sử dụng số tiền.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin tài chính</h3>
                                <ul class="space-y-3">
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">Tổng số tiền quyên góp</span>
                                        <span class="font-semibold text-green-600"><?php echo number_format($project['raised']); ?>đ</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">Mục tiêu ban đầu</span>
                                        <span class="font-semibold"><?php echo number_format($project['goal']); ?>đ</span>
                                    </li>
                                    <li class="flex justify-between pt-3 border-t border-gray-100">
                                        <span class="text-gray-600">Số tiền vượt mục tiêu</span>
                                        <span class="font-semibold text-blue-600"><?php echo number_format(max(0, $project['raised'] - $project['goal'])); ?>đ</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thời gian thực hiện</h3>
                                <ul class="space-y-3">
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">Ngày bắt đầu</span>
                                        <span class="font-semibold"><?php echo date('d/m/Y', strtotime($project['start_date'])); ?></span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">Ngày kết thúc</span>
                                        <span class="font-semibold"><?php echo date('d/m/Y', strtotime($project['end_date'])); ?></span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">Thời gian thực hiện</span>
                                        <span class="font-semibold">
                                            <?php
                                                $start = new DateTime($project['start_date']);
                                                $end = new DateTime($project['end_date']);
                                                $interval = $start->diff($end);
                                                echo $interval->format('%a ngày');
                                            ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Phân bổ ngân sách</h3>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">Chi phí nguyên vật liệu</span>
                                        <span class="text-sm font-medium text-gray-700">45%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 45%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">Chi phí nhân công</span>
                                        <span class="text-sm font-medium text-gray-700">30%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-green-600 h-2.5 rounded-full" style="width: 30%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">Chi phí vận chuyển</span>
                                        <span class="text-sm font-medium text-gray-700">15%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-yellow-500 h-2.5 rounded-full" style="width: 15%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">Chi phí khác</span>
                                        <span class="text-sm font-medium text-gray-700">10%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-purple-600 h-2.5 rounded-full" style="width: 10%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Hình ảnh báo cáo</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="rounded-lg overflow-hidden h-48">
                                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Report image" class="w-full h-full object-cover">
                                </div>
                                <div class="rounded-lg overflow-hidden h-48">
                                    <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Report image" class="w-full h-full object-cover">
                                </div>
                                <div class="rounded-lg overflow-hidden h-48">
                                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Report image" class="w-full h-full object-cover">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Donate Form -->
    <div id="donateModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeDonateModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"></span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full modal-enter">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-heart text-green-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Ủng hộ dự án</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Vui lòng điền thông tin để hoàn tất quyên góp cho dự án <span class="font-semibold"><?php echo htmlspecialchars($project['title']); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6">
                    <form id="donateForm" method="POST">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Họ và tên <span class="text-red-500">*</span></label>
                                <input type="text" id="name" name="name" required class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" value="<?= $getUser['email']?>" name="email" required readonly class="mt-1 bg-gray-100 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                <input type="tel" id="phone" name="phone" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Số tiền ủng hộ (VNĐ) <span class="text-red-500">*</span></label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">đ</span>
                                    </div>
                                    <input type="number" id="amount" name="amount" min="1000" step="1000" required class="focus:ring-green-500 focus:border-green-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="10000">
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <span class="text-gray-500 sm:text-sm">VNĐ</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Số tiền tối thiểu: 1,000đ</p>
                            </div>
                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700">Phương thức thanh toán <span class="text-red-500">*</span></label>
                                <select id="payment_method" name="payment_method" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                    <option value="bank">Chuyển khoản ngân hàng</option>
                                    <option value="sodu">Số dư tài khoản (<?php echo number_format($getUser['sodu']); ?>đ)</option>
                                </select>
                            </div>
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700">Lời nhắn (nếu có)</label>
                                <textarea id="message" name="message" rows="3" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                            </div>
                            <div class="flex items-center">
                                <input id="anonymous" name="anonymous" type="checkbox" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded">
                                <label for="anonymous" class="ml-2 block text-sm text-gray-700">Tôi muốn ủng hộ ẩn danh</label>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="button" onclick="closeDonateModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-1 sm:text-sm">
                                Hủy bỏ
                            </button>
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm">
                                <i class="fas fa-heart mr-2"></i> Xác nhận ủng hộ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function openDonateModal() {
            document.getElementById('donateModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeDonateModal() {
            document.getElementById('donateModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        document.getElementById('donateModal').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('modal-overlay')) {
                closeDonateModal();
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDonateModal();
            }
        });

        $('#donateForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/ajaxs/client/save_order.php',
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'SAVE_ORDER',
                    project_id: <?php echo $project_id; ?>,
                    name: $("#name").val(),
                    email: $("#email").val(),
                    phone: $("#phone").val(),
                    amount: $("#amount").val(),
                    message: $("#message").val(),
                    anonymous: $("#anonymous").is(':checked') ? 1 : 0,
                    payment_method: $("#payment_method").val()
                },
                success: function(result) {
                    if (result.status == 1) {
                        const phone = $("#phone").val() || 'anonymous';
                        if (result.payment_method === 'sodu') {
                            Swal.fire({
                                title: 'Thành công!',
                                text: 'Cảm ơn bạn đã ủng hộ! Số tiền ' + $("#amount").val() + 'đ đã được trừ từ số dư của bạn.',
                                icon: 'success',
                                confirmButtonColor: '#10b981',
                                confirmButtonText: 'Đóng'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            window.location.href = '/home/payment/' + encodeURIComponent(phone);
                        }
                    } else {
                        Swal.fire({
                            title: 'Thất bại!',
                            text: result.msg,
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra: ' + xhr.responseText,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        });

        function showTab(tabId) {
            document.getElementById('noiDung').classList.add('hidden');
            document.getElementById('danhSach').classList.add('hidden');
            <?php if ($isCompleted): ?>
                document.getElementById('baoCao').classList.add('hidden');
            <?php endif; ?>
            
            document.getElementById('btnNoiDung').classList.remove('active', 'text-green-600');
            document.getElementById('btnNoiDung').classList.add('text-gray-500');
            document.getElementById('btnDanhSach').classList.remove('active', 'text-green-600');
            document.getElementById('btnDanhSach').classList.add('text-gray-500');
            <?php if ($isCompleted): ?>
                document.getElementById('btnBaoCao').classList.remove('active', 'text-green-600');
                document.getElementById('btnBaoCao').classList.add('text-gray-500');
            <?php endif; ?>
            
            document.getElementById(tabId).classList.remove('hidden');
            if (tabId === 'noiDung') {
                document.getElementById('btnNoiDung').classList.add('active', 'text-green-600');
                document.getElementById('btnNoiDung').classList.remove('text-gray-500');
            } else if (tabId === 'danhSach') {
                document.getElementById('btnDanhSach').classList.add('active', 'text-green-600');
                document.getElementById('btnDanhSach').classList.remove('text-gray-500');
            } else if (tabId === 'baoCao') {
                document.getElementById('btnBaoCao').classList.add('active', 'text-green-600');
                document.getElementById('btnBaoCao').classList.remove('text-gray-500');
            }
        }

        const searchInput = document.getElementById('searchDonorInput');
        const donationsTable = document.getElementById('donationsTable');

        if (searchInput && donationsTable) {
            searchInput.addEventListener('input', function() {
                const filter = this.value.toLowerCase();
                const rows = donationsTable.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const donorCell = row.getElementsByTagName('td')[0];
                    if (donorCell) {
                        const donorName = donorCell.textContent.toLowerCase();
                        if (donorName.indexOf(filter) > -1) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>