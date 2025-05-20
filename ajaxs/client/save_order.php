<?php
define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");
session_start();
header('Content-Type: application/json');

global $NNL;

// Lấy dữ liệu
$action = $_POST['action'] ?? '';
$project_id = (int)($_POST['project_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$amount = (int)($_POST['amount'] ?? 0);
$message = trim($_POST['message'] ?? '');
$anonymous = (isset($_POST['anonymous']) && $_POST['anonymous'] == 1) ? 1 : 0;
$payment_method = trim($_POST['payment_method'] ?? 'bank');

if ($action !== 'SAVE_ORDER') {
    echo json_encode(['status' => 0, 'msg' => 'Hành động không hợp lệ!']);
    exit;
}

// Kiểm tra dữ liệu
if ($project_id <= 0 || $amount <= 0 || empty($name) || empty($email)) {
    echo json_encode(['status' => 0, 'msg' => 'Dữ liệu không hợp lệ!']);
    exit;
}

// Kiểm tra dự án
$project = $NNL->get_row("SELECT * FROM projects WHERE id = $project_id");
if (!$project) {
    echo json_encode(['status' => 0, 'msg' => 'Dự án không tồn tại!']);
    exit;
}

$status = 'pending'; // Mặc định
if ($payment_method === 'sodu') {
    $user = $NNL->get_row("SELECT sodu FROM users WHERE email = '$email'");
    if (!$user) {
        echo json_encode(['status' => 0, 'msg' => 'Tài khoản không tồn tại!']);
        exit;
    }
    if ($user['sodu'] < $amount) {
        echo json_encode(['status' => 0, 'msg' => 'Số dư không đủ để ủng hộ!']);
        exit;
    }
    // Nếu đủ tiền => update số dư ngay + gán trạng thái completed
    $new_sodu = $user['sodu'] - $amount;
    $NNL->update('users', ['sodu' => $new_sodu], "email = '$email'");
    $status = 'completed';
}

// Insert đơn hàng
$data = [
    'project_id' => $project_id,
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'amount'     => $amount,
    'message'    => $message,
    'anonymous'  => $anonymous,
    'status'     => $status,
    'payment_method' => $payment_method,
    'created_at' => time()
];

$order_id = $NNL->insert('orders', $data);

if ($order_id) {
    echo json_encode(['status' => 1, 'msg' => 'Ủng hộ thành công!', 'payment_method' => $payment_method]);
} else {
    echo json_encode(['status' => 0, 'msg' => 'Không lưu được đơn hàng!']);
}
exit;
