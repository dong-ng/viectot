<?php
define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");
require_once("../../core/is_user.php");

session_start();

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;

    // Kiểm tra dữ liệu đầu vào
    if (!$id || !in_array($action, ['approve', 'reject']) || !$admin_id) {
        error_log("Invalid data - id: $id, action: $action, admin_id: $admin_id");
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    $NNL = new DB();
    $get_pro_sq = $NNL->get_row("SELECT * FROM `project_requests` WHERE `id` = '$id'");

    // Thiết lập múi giờ
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $current_time = date('Y-m-d H:i:s'); // Thời gian thực

    if ($action == 'approve') {
        $end_date = $get_pro_sq['end_date']; // Kiểu DATE: 2025-04-25
        $today = date('Y-m-d');

        // Kiểm tra ngày hết hạn
        if ($end_date < $today) {
            echo json_encode(['success' => false, 'message' => 'Dự án đã quá hạn, không thể duyệt']);
            exit;
        }

        // Thêm dự án vào bảng projects
        $NNL->insert("projects", [
            'title'       => $get_pro_sq['title'],
            'description' => $get_pro_sq['description'],
            'image'       => $get_pro_sq['image'],
            'goal'        => $get_pro_sq['goal'],
            'category_id' => $get_pro_sq['category_id'],
            'user_id'     => $get_pro_sq['user_id'],
            'start_date'  => $get_pro_sq['start_date'],
            'end_date'    => $get_pro_sq['end_date'],
            'address'     => $get_pro_sq['address'],
        ]);

        // Cập nhật trạng thái project_requests
        $result = $NNL->update("project_requests", [
            'status'           => 'approved',
            'admin_id'         => $admin_id,
            'action_timestamp' => $current_time
        ], " `id` = '$id' ");

        // Gỡ lỗi: Ghi log kết quả cập nhật
        error_log("admin_id: $admin_id, current_time: $current_time");
        error_log("Update result (approve): " . ($result ? 'Success' : 'Failed'));

        echo json_encode(['success' => true, 'message' => 'Đã duyệt thành công']);
        exit;
    } elseif ($action == 'reject') {
        // Cập nhật trạng thái project_requests với lý do từ chối
        $result = $NNL->update("project_requests", [
            'status'           => 'rejected',
            'reject_reason'    => $reason,
            'admin_id'         => $admin_id,
            'action_timestamp' => $current_time
        ], " `id` = '$id' ");

        // Gỡ lỗi: Ghi log kết quả cập nhật
        error_log("admin_id: $admin_id, current_time: $current_time");
        error_log("Update result (reject): " . ($result ? 'Success' : 'Failed'));

        echo json_encode(['success' => true, 'message' => 'Đã từ chối vì ' . htmlspecialchars($reason)]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
?>