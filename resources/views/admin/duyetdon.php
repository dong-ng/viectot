<?php
require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

$db = new DB();

// Truy vấn dữ liệu từ bảng project_requests, bao gồm tên người dùng và tên quản trị viên
$sql = "
SELECT pr.*, u.name as user_name, ua.name as admin_name
FROM project_requests pr
LEFT JOIN users u ON pr.user_id = u.id
LEFT JOIN users ua ON pr.admin_id = ua.id
WHERE pr.user_id >= 0
";
$result = $db->query($sql);

// Lấy dữ liệu trả về vào mảng $donations
$donations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý duyệt dự án - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-active {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
            font-weight: 600;
        }
        .reason-tooltip:hover .reason-text {
            visibility: visible;
            opacity: 1;
        }
        [x-cloak] {
            display: none !important;
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

        /* Fix scrollbar issue */
        .main-content {
            margin-left: 256px; /* Adjust based on sidebar width */
            overflow-x: auto;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 256px; /* Adjust based on sidebar width */
            height: 100%;
            z-index: 10;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .sidebar {
                width: 100%;
                position: relative;
            }
        }
    </style>
</head>
<body class="bg-gray-50" 
      x-data="{
          activeTab: 'pending',
          showRejectModal: false,
          selectedId: null,
          rejectReason: '',
          chatPopup: false,
          currentChatUser: null,
          currentChatUserId: null
      }">

    <div class="flex min-h-screen">
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="main-content flex-1 p-6">
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Quản lý duyệt dự án</h1>
                <p class="text-gray-600">Xem xét và phê duyệt các yêu cầu</p>
            </header>
            
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button 
                        @click="activeTab = 'pending'" 
                        :class="{ 'tab-active': activeTab === 'pending' }"
                        class="py-4 px-1 text-sm font-medium text-gray-500 hover:text-blue-600 focus:outline-none">
                        <i class="fas fa-clock mr-2"></i>Chờ duyệt
                        <span class="ml-1 bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-0.5 rounded-full">
                            <?php echo count(array_filter($donations, function($item) {
                                return $item['status'] === 'pending';
                            })); ?>
                        </span>
                    </button>
                    <button 
                        @click="activeTab = 'approved'" 
                        :class="{ 'tab-active': activeTab === 'approved' }"
                        class="py-4 px-1 text-sm font-medium text-gray-500 hover:text-blue-600 focus:outline-none">
                        <i class="fas fa-check-circle mr-2"></i>Đã duyệt
                        <span class="ml-1 bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded-full">
                            <?php echo count(array_filter($donations, function($item) {
                                return $item['status'] === 'approved';
                            })); ?>
                        </span>
                    </button>
                    <button 
                        @click="activeTab = 'rejected'" 
                        :class="{ 'tab-active': activeTab === 'rejected' }"
                        class="py-4 px-1 text-sm font-medium text-gray-500 hover:text-blue-600 focus:outline-none">
                        <i class="fas fa-times-circle mr-2"></i>Đã từ chối
                        <span class="ml-1 bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded-full">
                            <?php echo count(array_filter($donations, function($item) {
                                return $item['status'] === 'rejected';
                            })); ?>
                        </span>
                    </button>
                </nav>
            </div>
            
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên dự án</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người gửi (user_id)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mục tiêu (goal)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th x-show="activeTab !== 'pending'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin duyệt</th>
                                <th x-show="activeTab !== 'pending'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <span x-show="activeTab === 'approved'">Thời gian duyệt</span>
                                    <span x-show="activeTab === 'rejected'">Thời gian từ chối</span>
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($donations as $index => $donation): ?>
                                <tr x-show="activeTab === '<?php echo $donation['status']; ?>'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($donation['title']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                            if (isset($donation['user_id']) && $donation['user_id'] > 0) {
                                                echo htmlspecialchars($donation['user_name']) . " (ID: " . $donation['user_id'] . ")";
                                            } else {
                                                echo "Không xác định (ID: N/A)";
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($donation['goal']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            <?php echo !empty($donation['start_date']) && $donation['start_date'] !== '0000-00-00' ? date('d/m/Y', strtotime($donation['start_date'])) : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($donation['status'] === 'pending'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Chờ duyệt
                                            </span>
                                        <?php elseif ($donation['status'] === 'approved'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Đã duyệt
                                            </span>
                                        <?php elseif ($donation['status'] === 'rejected'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i> Đã từ chối
                                            </span>
                                        <?php endif; ?>
                                        <a href="/admin/chitietduan.php?id=<?php echo (int)$donation['id']; ?>" 
                                           class="ml-2 text-blue-600 hover:text-blue-900 text-xs">
                                            Xem chi tiết
                                        </a>
                                    </td>
                                    <td x-show="activeTab !== 'pending'" class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo !empty($donation['admin_name']) ? htmlspecialchars($donation['admin_name']) . " (ID: " . $donation['admin_id'] . ")" : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td x-show="activeTab !== 'pending'" class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            <?php echo !empty($donation['action_timestamp']) ? date('d/m/Y H:i:s', strtotime($donation['action_timestamp'])) : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <?php if ($donation['status'] === 'pending'): ?>
                                            <button 
                                                @click="showRejectModal = true; selectedId = <?php echo (int)$donation['id']; ?>" 
                                                class="text-red-600 hover:text-red-900 mr-3">
                                                <i class="fas fa-times-circle"></i> Từ chối
                                            </button>
                                            <button 
                                                @click="approveDonation(<?php echo (int)$donation['id']; ?>)"
                                                class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-check-circle"></i> Duyệt
                                            </button>
                                            <?php if (isset($donation['user_id']) && $donation['user_id'] > 0): ?>
                                                <button 
                                                    @click="
                                                        chatPopup = true;
                                                        currentChatUser = '<?= htmlspecialchars($donation['user_name']) ?>';
                                                        currentChatUserId = <?= (int)$donation['user_id'] ?>;
                                                        openChat(<?= (int)$donation['user_id'] ?>);
                                                    "
                                                    class="text-blue-600 hover:text-blue-900 ml-3">
                                                    <i class="fas fa-comments"></i> Trao đổi
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($donation['status'] === 'rejected'): ?>
                                            <div class="relative reason-tooltip inline-block">
                                                <span class="text-red-600 cursor-help hover:underline text-sm">
                                                    <i class="fas fa-info-circle mr-1"></i>Lý do
                                                </span>
                                                <div class="reason-text absolute z-10 bg-white border border-gray-300 rounded p-2 text-xs text-gray-700 shadow-md w-64 left-1/2 transform -translate-x-1/2 mt-2 invisible opacity-0 transition-opacity duration-300">
                                                    <?php echo !empty($donation['reject_reason']) ? nl2br(htmlspecialchars($donation['reject_reason'])) : 'Không có lý do'; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Popup (FB Messenger style) -->
    <div class="chat-popup" :class="{ 'active': chatPopup }" x-cloak>
        <div class="chat-header">
            <div class="flex items-center">
                <div class="user-avatar">
                    <span x-text="currentChatUser ? currentChatUser.charAt(0).toUpperCase() : ''"></span>
                </div>
                <div>
                    <div class="font-medium" x-text="currentChatUser"></div>
                    <div class="text-xs opacity-80" id="chat-user-id"></div>
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

    <!-- Modal Từ chối đơn -->
    <div x-show="showRejectModal" x-cloak class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div 
                x-show="showRejectModal" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                aria-hidden="true">
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"></span>
            <div 
                x-show="showRejectModal"
                x-transition:enter="ease-out duration-300" 
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden 
                       shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Từ chối đơn ủng hộ
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Vui lòng nhập lý do từ chối đơn này (nếu cần).
                            </p>
                        </div>
                        <div class="mt-4">
                            <textarea 
                                x-model="rejectReason" 
                                rows="3" 
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md" 
                                placeholder="Nhập lý do từ chối..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button 
                        @click="showRejectModal = false; rejectReason = ''"
                        type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm 
                               px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none 
                               sm:mt-0 sm:col-start-1 sm:text-sm">
                        Hủy bỏ
                    </button>
                    <button 
                        @click="
                            rejectDonation(selectedId, rejectReason);
                            showRejectModal = false;
                            rejectReason = '';
                        "
                        type="button" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm 
                               px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none 
                               sm:col-start-2 sm:text-sm">
                        Xác nhận từ chối
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function approveDonation(id) {
            fetch('/ajaxs/admin/handle_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&action=approve&admin_id=1`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi. Vui lòng thử lại.');
            });
        }

        function rejectDonation(id, reason) {
            fetch('/ajaxs/admin/handle_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&action=reject&reason=${encodeURIComponent(reason)}&admin_id=1`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi. Vui lòng thử lại.');
            });
        }

        // Chat Logic
        let chatUserId = null;
        function openChat(userId) {
            chatUserId = userId;
            fetch(`/admin/chat.php?user_id=${userId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'fetch_messages=1'
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('chat-user-id').textContent = 'ID: ' + userId;
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
            fetch(`/admin/chat.php?user_id=${chatUserId}`, { method: 'POST', body: formData })
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
            fetch(`/admin/chat.php?user_id=${chatUserId}`, {
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
</body>
</html>