<?php
define("IN_SITE", true);
require_once(__DIR__ . '/../../core/DB.php');
require_once(__DIR__ . '/../../core/helpers.php');
require_once(__DIR__ . '/../../core/is_user.php');

global $NNL;

header('Content-Type: application/json');

// Kiểm tra quyền admin



// Đọc file refund.log
$log_file = __DIR__ . '/refund.log';
$data = [];

if (file_exists($log_file)) {
    $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($logs as $log) {
        // Giả sử log có định dạng: "Hoàn [amount] cho email [email] (user_id: [user_id]) tại dự án [project_id]: [datetime]"
        // hoặc "Hoàn tiền dự án [project_id]: [count] đơn hàng, tổng số tiền [amount] VND: [datetime]"
        preg_match('/(.+): (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $log, $matches);
        if ($matches) {
            $message = trim($matches[1]);
            $datetime = $matches[2];
            
            // Phân tích log để lấy thông tin chi tiết
            $log_entry = ['datetime' => $datetime, 'message' => $message];
            
            if (preg_match('/Hoàn (\d+) cho email (.+) \(user_id: (\d+)\) tại dự án (\d+)/', $message, $detail)) {
                $log_entry['type'] = 'refund';
                $log_entry['amount'] = (int)$detail[1];
                $log_entry['email'] = $detail[2];
                $log_entry['user_id'] = (int)$detail[3];
                $log_entry['project_id'] = (int)$detail[4];
                // Lấy tiêu đề dự án
                $project = $NNL->get_row("SELECT title FROM projects WHERE id = {$log_entry['project_id']}");
                $log_entry['project_title'] = $project ? $project['title'] : 'Không xác định';
            } elseif (preg_match('/Hoàn tiền dự án (\d+): (\d+) đơn hàng, tổng số tiền (\d+) VND/', $message, $detail)) {
                $log_entry['type'] = 'summary';
                $log_entry['project_id'] = (int)$detail[1];
                $log_entry['order_count'] = (int)$detail[2];
                $log_entry['total_amount'] = (int)$detail[3];
                $project = $NNL->get_row("SELECT title FROM projects WHERE id = {$log_entry['project_id']}");
                $log_entry['project_title'] = $project ? $project['title'] : 'Không xác định';
            } elseif (preg_match('/Không tìm thấy user với email (.+) tại dự án (\d+)/', $message, $detail)) {
                $log_entry['type'] = 'error';
                $log_entry['email'] = $detail[1];
                $log_entry['project_id'] = (int)$detail[2];
                $project = $NNL->get_row("SELECT title FROM projects WHERE id = {$log_entry['project_id']}");
                $log_entry['project_title'] = $project ? $project['title'] : 'Không xác định';
            }

            $data[] = $log_entry;
        }
    }
}

echo json_encode([
    'success' => true,
    'data' => array_reverse($data) // Đảo ngược để hiển thị log mới nhất trước
]);
exit;