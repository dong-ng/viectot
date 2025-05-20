<?php
// file: delete_request.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/DB.php';

CheckLogin();
$db      = new DB();
$user_id = $getUser['id'];
$id      = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

// Chỉ xóa khi request đang ở trạng thái pending và thuộc về user
$stmt = $db->connect()->prepare("
    DELETE FROM project_requests
    WHERE id = ? 
      AND user_id = ? 
      AND status = 'pending'
");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy hoặc không thể xóa']);
}
