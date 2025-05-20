
<?php
define('IN_SITE', true);

session_start();
require_once(__DIR__ . '/../../core/DB.php');
require_once(__DIR__ . '/../../core/helpers.php');
require_once(__DIR__ . '/../../core/is_user.php');

CheckLogin();
CheckAdmin();

header('Content-Type: application/json');

$log_file = __DIR__ . '/disbursed.log';
$logs = [];

if (is_readable($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // regex bắt cả ngày và giờ: YYYY-MM-DD HH:MM:SS
        if (preg_match(
            '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] Admin ID: (\d+) \((.*?)\) đã giải ngân dự án ID: (\d+) số tiền: ([\d\.,]+) VND/',
            $line,
            $m
        )) {
            // chuyển amount về int
            $amount = (int) str_replace([',', '.'], '', $m[5]);
            // tạo đối tượng DateTime để format
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $m[1]);

            $logs[] = [
                'datetime' => $m[1],               // "2025-04-30 14:23:45"
                'date'     => $dt->format('Y-m-d'),// "2025-04-30"
                'time'     => $dt->format('H:i:s'),// "14:23:45"
                'admin_id'   => (int)$m[2],
                'admin_name' => $m[3],
                'project_id' => (int)$m[4],
                'amount'     => $amount
            ];
        }
    }
}

// Get project_id from POST request
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}
$project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;

// Filter logs by project_id
$filtered_logs = array_filter($logs, function($log) use ($project_id) {
    return (int)$log['project_id'] === $project_id;
});

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => array_values($filtered_logs)
]);
?>
