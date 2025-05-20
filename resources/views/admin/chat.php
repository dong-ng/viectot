<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../../../core/DB.php';
require_once __DIR__ . '/../../../core/helpers.php';

$db = new DB();

// Lấy user đang đăng nhập từ session/cookie
$token = $_SESSION['login'] ?? $_COOKIE['token'] ?? null;
if (!$token) {
    die("Không xác định được phiên đăng nhập.");
}

$admin = $db->get_row("SELECT * FROM users WHERE token = '" . check_string($token) . "'");
if (!$admin || $admin['is_admin'] != 1) {
    die("Bạn không có quyền truy cập.");
}
$admin_id = $admin['id'];

// Lấy user_id từ URL
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $params);
$user_id = isset($params['user_id']) ? (int)$params['user_id'] : 0;
if ($user_id <= 0) {
    die("ID người dùng không hợp lệ.");
}

// Xử lý gửi tin nhắn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !isset($_POST['fetch_messages'])) {
    $message = trim($_POST['message'] ?? '');
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Tin nhắn không được để trống.']);
        exit();
    }

    $data = [
        'sender_id'   => $admin_id,
        'receiver_id' => $receiver_id,
        'message'     => $message,
        'created_at'  => date('Y-m-d H:i:s')
    ];
    $insert = $db->insert('messages', $data);

    echo json_encode(['success' => $insert ? true : false]);
    exit();
}

// Lấy thông tin user
$user = $db->get_row("SELECT * FROM users WHERE id = $user_id");
if (!$user) {
    die("Không tìm thấy người dùng.");
}

// Xử lý yêu cầu lấy tin nhắn mới (cho AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_messages'])) {
    $messages = $db->get_list("
        SELECT * FROM messages
        WHERE (sender_id = $admin_id AND receiver_id = $user_id)
           OR (sender_id = $user_id AND receiver_id = $admin_id)
        ORDER BY created_at ASC
    ");

    // Trả về HTML cho tin nhắn
    ob_start();
    foreach ($messages as $msg) {
        ?>
        <div class="message <?= $msg['sender_id'] == $admin_id ? 'message-out' : 'message-in' ?>">
            <div class="message-content">
                <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                <div class="message-time">
                    <?= !empty($msg['created_at']) ? date('H:i', strtotime($msg['created_at'])) : '--:--' ?>
                    <?php if ($msg['sender_id'] == $admin_id): ?>
                        <span class="status-icon"><i class="fas fa-check-double"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html, 'user_name' => htmlspecialchars($user['name'])]);
    exit();
}

// Lấy tin nhắn giữa admin và user
$messages = $db->get_list("
    SELECT * FROM messages
    WHERE (sender_id = $admin_id AND receiver_id = $user_id)
       OR (sender_id = $user_id AND receiver_id = $admin_id)
    ORDER BY created_at ASC
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trò chuyện với <?= htmlspecialchars($user['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --admin-color: #4361ee;
            --user-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --dark-bg: #e9ecef;
            --text-color: #495057;
            --text-light: #6c757d;
            --white: #ffffff;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            max-width: 1000px;
            margin: 0 auto;
            height: 100vh;
            width: 100%;
            background-color: var(--white);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .chat-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .chat-header .user-info {
            display: flex;
            align-items: center;
        }

        .chat-header .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--white);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            font-size: 18px;
        }

        .chat-header .user-name {
            font-weight: 500;
            font-size: 1.1rem;
        }

        .chat-header .user-id {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 2px;
        }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background-color: var(--light-bg);
            background-image: url('https://transparenttextures.com/patterns/cubes.png');
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
        }

        .message {
            max-width: 70%;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
        }

        .message-in {
            align-items: flex-start;
        }

        .message-out {
            align-items: flex-end;
        }

        .message-content {
            padding: 12px 16px;
            border-radius: var(--border-radius);
            position: relative;
            word-wrap: break-word;
            animation: fadeIn 0.3s ease;
        }

        .message-in .message-content {
            background-color: var(--white);
            color: var(--text-color);
            border-top-left-radius: 4px;
        }

        .message-out .message-content {
            background-color: var(--admin-color);
            color: var(--white);
            border-top-right-radius: 4px;
        }

        .message-text {
            margin-bottom: 4px;
        }

        .message-time {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            opacity: 0.8;
        }

        .message-in .message-time {
            color: var(--text-light);
        }

        .message-out .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .status-icon {
            margin-left: 4px;
            font-size: 0.6rem;
        }

        .chat-input {
            padding: 1rem;
            background-color: var(--white);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .message-form {
            width: 100%;
            display: flex;
            align-items: center;
        }

        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 24px;
            outline: none;
            font-family: inherit;
            font-size: 0.95rem;
            resize: none;
            transition: border-color 0.3s;
            max-height: 120px;
            min-height: 48px;
        }

        .message-input:focus {
            border-color: var(--primary-color);
        }

        .send-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            margin-left: 12px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-button:hover {
            background-color: var(--secondary-color);
        }

        .send-button i {
            font-size: 1.1rem;
        }

        .typing-indicator {
            font-size: 0.8rem;
            color: var(--text-light);
            padding: 0 1.5rem 0.5rem;
            display: none;
        }

        .no-messages {
            text-align: center;
            color: var(--text-light);
            margin: auto;
            padding: 2rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
        }

        ::-webkit-scrollbar-thumb {
            background-color: var(--primary-color);
            border-radius: 4px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chat-container {
                height: 100vh;
                border-radius: 0;
            }
            
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-id">ID: <?= $user_id ?></div>
                </div>
            </div>
            <div class="header-actions">
                <i class="fas fa-ellipsis-v"></i>
            </div>
        </div>

        <div class="chat-messages" id="chat-box">
            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <i class="far fa-comment-dots fa-3x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Bắt đầu cuộc trò chuyện với <?= htmlspecialchars($user['name']) ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= $msg['sender_id'] == $admin_id ? 'message-out' : 'message-in' ?>">
                        <div class="message-content">
                            <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="message-time">
                                <?= !empty($msg['created_at']) ? date('H:i', strtotime($msg['created_at'])) : '--:--' ?>
                                <?php if ($msg['sender_id'] == $admin_id): ?>
                                    <span class="status-icon"><i class="fas fa-check-double"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="typing-indicator" id="typing-indicator">
            <?= htmlspecialchars($user['name']) ?> đang soạn tin nhắn...
        </div>

        <div class="chat-input">
            <form id="chat-form" class="message-form">
                <textarea 
                    name="message" 
                    class="message-input" 
                    placeholder="Nhập tin nhắn..." 
                    rows="1"
                    oninput="autoResize(this)"
                ></textarea>
                <input type="hidden" name="receiver_id" value="<?= $user_id ?>">
                <button type="submit" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('chat-form');
        const chatBox = document.getElementById('chat-box');
        const typingIndicator = document.getElementById('typing-indicator');
        const messageInput = document.querySelector('.message-input');

        // Auto-resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        }

        // Scroll to bottom of chat
        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Show typing indicator (simulated)
        messageInput.addEventListener('focus', () => {
            // In a real app, you'd send a websocket event here
            typingIndicator.style.display = 'block';
            scrollToBottom();
        });

        messageInput.addEventListener('blur', () => {
            typingIndicator.style.display = 'none';
        });

        // Form submission
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            
            // Show temporary message while sending
            const tempMessage = formData.get('message');
            if (tempMessage.trim() === '') return;
            
            form.reset();
            autoResize(messageInput);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || "Lỗi gửi tin nhắn.");
                }
            });
        };

        // Fetch new messages
        function fetchMessages() {
            const scrollPosition = chatBox.scrollTop;
            const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'fetch_messages=1'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.html !== chatBox.innerHTML) {
                        chatBox.innerHTML = data.html;
                        if (isScrolledToBottom || chatBox.innerHTML !== data.html) {
                            scrollToBottom();
                        }
                    }
                    document.title = `Trò chuyện với ${data.user_name}`;
                }
            });
        }

        // Auto-refresh messages every 2 seconds
        setInterval(fetchMessages, 2000);
        
        // Initial scroll to bottom
        setTimeout(scrollToBottom, 100);
    </script>
</body>
</html>