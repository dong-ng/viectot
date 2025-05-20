<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';
session_start();
CheckLogin();
$db = new DB();
$user_id = $getUser['id'];

// Lấy dự án đã duyệt (từ bảng projects)
$approved = $db->get_list("SELECT * FROM `projects` WHERE `user_id` = '$user_id'");
$count_approved = count($approved);

// Lấy dự án đã hoàn thành (raised >= goal)
$completed = array_filter($approved, function($proj) {
    return ($proj['raised'] >= $proj['goal']);
});
$count_completed = count($completed);

// Lấy chờ duyệt
$pending = $db->get_list("
    SELECT * FROM `project_requests`
    WHERE `user_id` = '$user_id' AND `status` = 'pending'
");
$count_pending = count($pending);

// Lấy bị từ chối
$rejected = $db->get_list("
    SELECT * FROM `project_requests`
    WHERE `user_id` = '$user_id' AND `status` = 'rejected'
");
$count_rejected = count($rejected);

// Tính tổng tiền & nổi bật
$total_raised = 0;
$most_successful = null;
$max_amount = 0;
foreach ($approved as $proj) {
    $raised = $proj['raised'] ?? 0; // Sử dụng cột raised từ bảng projects
    $total_raised += $raised;
    if ($raised > $max_amount) {
        $max_amount = $raised;
        $most_successful = $proj;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chiến dịch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        .progress-bar {
            transition: width 0.6s ease;
        }
        .campaign-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .campaign-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        /* Chat popup styles */
        .chat-popup {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .chat-popup.active {
            transform: translateY(0);
            opacity: 1;
        }
        .chat-header {
            background: #3b82f6;
            color: white;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #f5f6f7;
        }
        .message {
            margin-bottom: 12px;
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.4;
            position: relative;
        }
        .message-in {
            background: #f1f1f1;
            border-top-left-radius: 4px;
            align-self: flex-start;
            margin-right: auto;
        }
        .message-out {
            background: #3b82f6;
            color: white;
            border-top-right-radius: 4px;
            align-self: flex-end;
            margin-left: auto;
        }
        .message-time {
            font-size: 11px;
            color: #65676b;
            margin-top: 4px;
            text-align: right;
        }
        .message-out .message-time {
            color: rgba(255,255,255,0.8);
        }
        .chat-input {
            padding: 12px;
            border-top: 1px solid #ddd;
            background: white;
        }
        .chat-input textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 18px;
            padding: 10px 15px;
            resize: none;
            outline: none;
            font-size: 14px;
        }
        .chat-input button {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            margin-left: 8px;
            cursor: pointer;
        }
        .close-chat {
            cursor: pointer;
            font-size: 18px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .typing-indicator {
            display: flex;
            padding: 8px 12px;
            background: white;
            border-radius: 18px;
            width: fit-content;
            margin-bottom: 12px;
        }
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #65676b;
            border-radius: 50%;
            margin: 0 2px;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
        .detail-button {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #3b82f6;
            background-color: #e0e7ff;
            transition: all 0.2s ease;
        }
        .detail-button:hover {
            background-color: #c7d2fe;
            color: #1d4ed8;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ 
    tab: 'approved', 
    chatPopup: false, 
    currentChatUser: 'Admin', 
    currentChatUserId: null 
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Quản lý Chiến dịch</h1>
            <p class="text-gray-600 mt-2">Theo dõi và quản lý các chiến dịch gây quỹ của bạn</p>
        </div>
        <a href="/home/createcamp" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i> Tạo chiến dịch mới
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Total Campaigns -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-lg bg-blue-50 text-blue-600 mr-4">
                    <i class="fas fa-project-diagram text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Tổng chiến dịch</p>
                    <h3 class="text-2xl font-semibold text-gray-900"><?= $count_approved ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Total Raised -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-lg bg-green-50 text-green-600 mr-4">
                    <i class="fas fa-donate text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Tổng tiền quyên góp</p>
                    <h3 class="text-2xl font-semibold text-gray-900"><?= number_format($total_raised) ?> VND</h3>
                </div>
            </div>
        </div>
        
        <!-- Most Successful -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-lg bg-purple-50 text-purple-600 mr-4">
                    <i class="fas fa-trophy text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Chiến dịch nổi bật</p>
                    <?php if ($most_successful): ?>
                        <h3 class="text-lg font-semibold text-purple-700 truncate"><?= htmlspecialchars($most_successful['title']) ?></h3>
                        <p class="text-sm text-purple-500 font-medium"><?= number_format($max_amount) ?> VND</p>
                    <?php else: ?>
                        <p class="text-gray-400 italic">Chưa có chiến dịch</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="-mb-px flex space-x-8">
            <button @click="tab = 'approved'" :class="tab === 'approved' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i> Đã duyệt
                <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><?= $count_approved ?></span>
            </button>
            
            <button @click="tab = 'completed'" :class="tab === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                <i class="fas fa-check-double mr-2"></i> Đã hoàn thành
                <span class="ml-2 bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><?= $count_completed ?></span>
            </button>
            
            <button @click="tab = 'pending'" :class="tab === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                <i class="fas fa-clock mr-2"></i> Chờ duyệt
                <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><?= $count_pending ?></span>
            </button>
            
            <button @click="tab = 'rejected'" :class="tab === 'rejected' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                <i class="fas fa-times-circle mr-2"></i> Đã huỷ
                <span class="ml-2 bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><?= $count_rejected ?></span>
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="space-y-6">
        <!-- Approved Campaigns -->
        <div x-show="tab === 'approved'" x-cloak>
            <?php if ($count_approved > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($approved as $proj):
                        $raised = $proj['raised'] ?? 0; // Sử dụng cột raised từ bảng projects
                        $percent = $proj['goal'] > 0 ? round($raised / $proj['goal'] * 100, 2) : 0;
                    ?>
                        <div class="campaign-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
                            <div class="p-5">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($proj['title']) ?></h3>
                                    <span class="detail-button">
                                        <a href="/user/chitietduan.php?id=<?= (int)$proj['id'] ?>&source=projects" 
                                           class="text-blue-600 hover:text-blue-900">
                                            Xem chi tiết
                                        </a>
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4"><?= nl2br(htmlspecialchars(substr($proj['description'], 0, 100))) ?>...</p>
                                
                                <div class="mb-3">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Tiến độ</span>
                                        <span><?= $percent ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-green-600 h-2.5 rounded-full progress-bar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Đã gây quỹ</span>
                                    <span class="font-medium text-gray-900"><?= number_format($raised) ?> VND</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Mục tiêu</span>
                                    <span class="font-medium text-gray-900"><?= number_format($proj['goal']) ?> VND</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                    <i class="fas fa-project-diagram text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Bạn chưa có chiến dịch nào được duyệt</h3>
                    <p class="mt-2 text-sm text-gray-500">Tạo chiến dịch mới để bắt đầu gây quỹ</p>
                    <div class="mt-6">
                        <a href="/home/createcamp" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Tạo chiến dịch
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed Campaigns -->
        <div x-show="tab === 'completed'" x-cloak>
            <?php if ($count_completed > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($completed as $proj):
                        $raised = $proj['raised'] ?? 0;
                        $percent = $proj['goal'] > 0 ? round($raised / $proj['goal'] * 100, 2) : 0;
                        $disbursed = $proj['disbursed_amount'] ?? 0;
                    ?>
                        <div class="campaign-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
                            <div class="p-5">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($proj['title']) ?></h3>
                                    <span class="detail-button">
                                        <a href="/user/chitietduan.php?id=<?= (int)$proj['id'] ?>&source=projects" 
                                           class="text-blue-600 hover:text-blue-900">
                                            Xem chi tiết
                                        </a>
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4"><?= nl2br(htmlspecialchars(substr($proj['description'], 0, 100))) ?>...</p>
                                
                                <div class="mb-3">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Tiến độ</span>
                                        <span><?= $percent ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-green-600 h-2.5 rounded-full progress-bar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Đã gây quỹ</span>
                                    <span class="font-medium text-gray-900"><?= number_format($raised) ?> VND</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Mục tiêu</span>
                                    <span class="font-medium text-gray-900"><?= number_format($proj['goal']) ?> VND</span>
                                </div>
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-gray-500">Đã giải ngân</span>
                                    <span class="font-medium text-gray-900"><?= number_format($disbursed) ?> VND</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                    <i class="fas fa-check-double text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Bạn chưa có chiến dịch nào hoàn thành</h3>
                    <p class="mt-2 text-sm text-gray-500">Chiến dịch hoàn thành khi đạt 100% mục tiêu gây quỹ</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Campaigns -->
        <div x-show="tab === 'pending'" x-cloak>
            <?php if ($count_pending > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($pending as $req): ?>
                        <div class="campaign-card bg-white rounded-lg shadow-md overflow-hidden border border-yellow-100">
                            <div class="p-5">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($req['title']) ?></h3>
                                    <span class="detail-button">
                                        <a href="/user/chitietduan.php?id=<?= (int)$req['id'] ?>&source=project_requests" 
                                           class="text-blue-600 hover:text-blue-900">
                                            Xem chi tiết
                                        </a>
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4"><?= nl2br(htmlspecialchars(substr($req['description'], 0, 200))) ?></p>
                                
                                <div class="text-sm text-gray-500 italic">
                                    <i class="fas fa-info-circle mr-1"></i> Chiến dịch của bạn đang chờ được xét duyệt
                                </div>
                            </div>
                            <div class="bg-gray-50 px-5 py-3 flex justify-between">
                                <div class="flex space-x-3">
                                    <button 
                                        @click="
                                            chatPopup = true;
                                            currentChatUserId = <?= (int)$req['user_id'] ?>;
                                            openChat(<?= (int)$req['user_id'] ?>);
                                        "
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-comments mr-2"></i> Chat với Admin
                                    </button>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="edit.php?id=<?= (int)$req['id'] ?>" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-edit mr-2"></i> Sửa
                                    </a>
                                    <button onclick="confirmDelete(<?= (int)$req['id'] ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fas fa-trash-alt mr-2"></i> Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                    <i class="fas fa-clock text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Không có chiến dịch nào đang chờ duyệt</h3>
                    <p class="mt-2 text-sm text-gray-500">Tạo chiến dịch mới để bắt đầu quá trình xét duyệt</p>
                    <div class="mt-6">
                        <a href="/home/createcamp" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Tạo chiến dịch
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rejected Campaigns -->
        <div x-show="tab === 'rejected'" x-cloak>
            <?php if ($count_rejected > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($rejected as $request): ?>
                        <div class="campaign-card bg-white rounded-lg shadow-md overflow-hidden border border-red-100">
                            <div class="p-5">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($request['title']) ?></h3>
                                    <span class="detail-button">
                                        <a href="/user/chitietduan.php?id=<?= (int)$request['id'] ?>&source=project_requests" 
                                           class="text-blue-600 hover:text-blue-900">
                                            Xem chi tiết
                                        </a>
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4"><?= nl2br(htmlspecialchars(substr($request['description'], 0, 200))) ?></p>
                                
                                <?php if (!empty($request['reject_reason'])): ?>
                                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-circle h-5 w-5 text-red-400"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-red-700">
                                                    <strong>Lý do từ chối:</strong> <?= htmlspecialchars($request['reject_reason']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-500 italic">
                                        <i class="fas fa-info-circle mr-1"></i> Không có lý do cụ thể
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-gray-50 px-5 py-3 flex justify-end">
                                <a href="/home/createcamp" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-redo mr-2"></i> Gửi lại
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                    <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Không có chiến dịch nào bị huỷ</h3>
                    <p class="mt-2 text-sm text-gray-500">Tất cả các chiến dịch của bạn đã được chấp nhận hoặc đang chờ xét duyệt</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Popup -->
    <div class="chat-popup" :class="{ 'active': chatPopup }">
        <div class="chat-header">
            <div class="flex items-center">
                <div class="user-avatar">
                    <span x-text="currentChatUser ? currentChatUser.charAt(0).toUpperCase() : ''"></span>
                </div>
                <div>
                    <div class="font-medium" x-text="currentChatUser"></div>
                    <div class="text-xs opacity-80" id SAV="chat-user-id"></div>
                </div>
            </div>
            <div class="flex items-center">
                <button @click="chatPopup = false; closeChat()" class="close-chat text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="chat-body" id="chat-messages"></div>
        
        <div class="chat-input">
            <form id="chat-form" class="flex items-center">
                <textarea 
                    id="chat-input"
                    placeholder="Nhập tin nhắn..." 
                    rows="1"
                    class="flex-1"></textarea>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

<!-- Delete Confirmation Script -->
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Bạn sẽ không thể hoàn tác hành động này!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Đã xóa!',
                        'Chiến dịch đã được xóa thành công.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Lỗi!',
                        data.message || 'Có lỗi xảy ra khi xóa chiến dịch.',
                        'error'
                    );
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire(
                    'Lỗi!',
                    'Có lỗi xảy ra, vui lòng thử lại sau.',
                    'error'
                );
            });
        }
    });
}

// Chat Logic
let chatUserId = null;
function openChat(userId) {
    chatUserId = userId;
    // Fetch initial messages
    fetch(`/user/chat.php?user_id=${userId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'fetch_messages=1'
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('chat-messages').innerHTML = data.html;
        scrollToBottom();
    });
}
function closeChat() {
    chatUserId = null;
}
function scrollToBottom() {
    const box = document.getElementById('chat-messages');
    box.scrollTop = box.scrollHeight;
}
document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('chat-input').value.trim();
    if (!msg) return;
    const formData = new URLSearchParams();
    formData.append('message', msg);
    formData.append('receiver_id', chatUserId);
    fetch(`/user/chat.php?user_id=${chatUserId}`, { 
        method: 'POST', 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('chat-input').value = '';
        }
    });
});
setInterval(() => {
    if (!chatUserId) return;
    const box = document.getElementById('chat-messages');
    const isAtBottom = box.scrollHeight - box.clientHeight <= box.scrollTop + 50;
    fetch(`/user/chat.php?user_id=${chatUserId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'fetch_messages=1'
    })
    .then(res => res.json())
    .then(data => {
        if (data.html !== box.innerHTML) {
            box.innerHTML = data.html;
            if (isAtBottom) scrollToBottom();
        }
    });
}, 2000);

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('.chat-input textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});
</script>

<!-- Include SweetAlert for beautiful alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>