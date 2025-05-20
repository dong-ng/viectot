<?php
define('IN_SITE', true);

header('Content-Type: application/json');
session_start();
require_once(__DIR__ . '/../../core/DB.php');
require_once(__DIR__ . '/../../core/helpers.php');
require_once(__DIR__ . '/../../core/is_user.php');

CheckLogin();
CheckAdmin();

// Lấy dữ liệu từ JSON body
$data = json_decode(file_get_contents('php://input'), true);
$project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dự án không hợp lệ']);
    exit;
}

try {
$db = new DB();
    $conn = $db->connect();

    // Lấy thông tin dự án
    $query = "SELECT goal, raised, disbursed_amount FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy dự án']);
        exit;
    }

    if ($project['raised'] < $project['goal']) {
        echo json_encode(['success' => false, 'message' => 'Dự án chưa hoàn thành']);
        exit;
    }

    if ($project['disbursed_amount'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Dự án đã được giải ngân']);
        exit;
    }

    // Giải ngân
    $query = "UPDATE projects SET disbursed_amount = goal WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    if ($stmt->execute()) {
    $amount = $project['goal'];
    $admin_id = $getUser['id'];;
    $admin_name = $getUser['name'];
 // Hoặc tùy session lưu tên

    $logMessage = sprintf(
        "[%s] Admin ID: %d (%s) đã giải ngân dự án ID: %d số tiền: %d VND\n",
        date('Y-m-d H:i:s'),
        $admin_id,
        $admin_name,
        $project_id,
        $amount
    );

    file_put_contents(__DIR__ . '/disbursed.log', $logMessage, FILE_APPEND | LOCK_EX);

    echo json_encode(['success' => true, 'message' => 'Giải ngân thành công']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Giải ngân thất bại']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
