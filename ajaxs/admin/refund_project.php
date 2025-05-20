<?php
define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");
require_once("../../core/is_user.php");

global $NNL;

header('Content-Type: application/json');

// Kiểm tra quyền admin
// if (!CheckLogin() || !CheckAdmin()) {
//     echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này']);
//     exit;
// }

// Lấy project_id từ request
$input = json_decode(file_get_contents('php://input'), true);
$project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dự án không hợp lệ']);
    exit;
}

// Kiểm tra dự án có tồn tại và quá hạn
$current_date = date('Y-m-d');
$project = $NNL->get_row("SELECT * FROM projects WHERE id = $project_id");
if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Dự án không tồn tại, không quá hạn, hoặc đã hoàn thành']);
    exit;
}

// Lấy danh sách đơn hàng cần hoàn tiền
$orders = $NNL->get_list("SELECT email, amount FROM orders WHERE project_id = $project_id AND status = 'completed'");
if (empty($orders)) {
    echo json_encode(['success' => true, 'message' => 'Dự án không có đơn hàng để hoàn tiền']);
    exit;
}

// Hoàn tiền cho từng đơn hàng
$refunded_count = 0;
$total_refunded = 0;

foreach ($orders as $order) {
    $email = $order['email'];
    $amount = $order['amount'];

    // Tìm user dựa trên email
    $user = $NNL->get_row("SELECT id FROM users WHERE email = '$email'");
    if ($user) {
        $user_id = $user['id'];
        // Cộng tiền vào balance
        $NNL->query("UPDATE users SET sodu = sodu + $amount WHERE id = $user_id");
        // Đánh dấu đơn hàng đã hoàn
        $NNL->query("UPDATE orders SET status = 'refunded' WHERE project_id = $project_id AND email = '$email'");
        $refunded_count++;
        $total_refunded += $amount;

        // Ghi log
        // file_put_contents('refund.log', "Hoàn $amount cho email $email (user_id: $user_id) tại dự án $project_id: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    } else {
        // Ghi log nếu không tìm thấy user
        file_put_contents('refund.log', "Không tìm thấy user với email $email tại dự án $project_id: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    }
}

// Ghi log tổng kết
$log_message = "Hoàn $amount cho email $email (user_id: $user_id) tại dự án $project_id: $refunded_count đơn hàng, tổng số tiền $total_refunded VND: " . date('Y-m-d H:i:s') . "\n";
file_put_contents('refund.log', $log_message, FILE_APPEND);

// Trả về kết quả
echo json_encode([
    'success' => true,
    'message' => "Hoàn tiền thành công cho $refunded_count đơn hàng, tổng số tiền: " . number_format($total_refunded) . " VND"
]);
exit;