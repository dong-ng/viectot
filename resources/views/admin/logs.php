<?php
session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/DB.php';
require_once __DIR__ . '/../../../core/helpers.php';
CheckLogin();
CheckAdmin();
global $NNL;

$db = new DB();

// Lấy dữ liệu từ bảng logs
$limit = 10; // Giới hạn 10 bản ghi cho "Hoạt động gần đây"
$logs = $db->get_list("
    SELECT l.*, u.name AS user_name
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT $limit
");

// Xử lý dữ liệu để trả về dưới dạng phù hợp với "Hoạt động gần đây"
$activities = [];
foreach ($logs as $log) {
    // Tính thời gian "Vừa xong", "X phút trước", v.v.
    $timeAgo = time() - strtotime($log['created_at']);
    if ($timeAgo < 60) $timeText = 'Vừa xong';
    elseif ($timeAgo < 3600) $timeText = floor($timeAgo / 60) . ' phút trước';
    elseif ($timeAgo < 86400) $timeText = floor($timeAgo / 3600) . ' giờ trước';
    else $timeText = floor($timeAgo / 86400) . ' ngày trước';

    // Việt hóa hành động
    $actionText = '';
    switch (strtolower($log['action'])) {
        case 'post':
            $actionText = 'Thêm mới';
            break;
        case 'put':
            $actionText = 'Cập nhật';
            break;
        case 'delete':
            $actionText = 'Xóa';
            break;
        case 'get':
            $actionText = 'Xem';
            break;
        default:
            $actionText = strtoupper($log['action']);
    }

    // Việt hóa tên bảng
    $tableText = $log['target_table'];
    switch ($log['target_table']) {
        case 'users':
            $tableText = 'Người dùng';
            break;
        case 'orders':
            $tableText = 'Đơn hàng';
            break;
        case 'projects':
            $tableText = 'Dự án';
            break;
    }

    $activities[] = [
        'time' => $timeText,
        'user' => $log['user_name'] ?: 'Người dùng #' . $log['user_id'],
        'action' => $actionText,
        'details' => "Dữ liệu: $tableText - " . htmlspecialchars(substr($log['content'], 0, 80)) . (strlen($log['content']) > 80 ? '...' : '')
    ];
}

// Trả về JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'activities' => $activities
]);
?>