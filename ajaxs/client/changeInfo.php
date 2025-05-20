<?php

define("IN_SITE", true);
require_once "../../core/DB.php";
require_once "../../core/helpers.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập']));
    }

    $getUser = $NNL->get_row("SELECT * FROM `users` WHERE `token` = '" . xss($_POST['token']) . "' AND `banned` = '0' ");
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập']));
    }

    $newEmail = isset($_POST['email']) ? trim($_POST['email']) : null;
    $newTele = isset($_POST['username_tele']) ? trim($_POST['username_tele']) : null;

    $hasChanges = false;
    $updateData = [];

    // Kiểm tra nếu có yêu cầu đổi email
    if (!empty($newEmail) && $newEmail !== $getUser['email']) {
        if (!check_email($newEmail)) {
            die(json_encode(['status' => 'error', 'msg' => 'Định dạng email không hợp lệ']));
        }
        if ($NNL->get_row("SELECT * FROM `users` WHERE `email` = '" . xss($newEmail) . "' ")) {
            die(json_encode(['status' => 'error', 'msg' => 'Email đã tồn tại, vui lòng chọn email khác']));
        }
        $updateData['email'] = xss($newEmail);
        $hasChanges = true;
    }

    // Kiểm tra nếu có yêu cầu đổi Telegram username
    if (!empty($newTele) && $newTele !== $getUser['username_tele']) {
        $updateData['username_tele'] = xss($newTele);
        $hasChanges = true;
    }

    // Nếu không có gì thay đổi, trả về null
    if (!$hasChanges) {
        die(json_encode(['status' => 'error', 'msg' => 'Không có thay đổi nào được thực hiện']));
    }

    // LƯU HOẠT ĐỘNG LẠI
    $NNL->insert("logs", [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => $_SERVER['HTTP_USER_AGENT'],
        'create_date' => gettime(),
        'action' => 'Đã thay đổi thông tin tài khoản',
    ]);

    // Thực hiện cập nhật dữ liệu
    $updateData['ip'] = myip();
    $updateData['time_session'] = time();
    $updateData['device'] = $_SERVER['HTTP_USER_AGENT'];

    $isUpdate = $NNL->update("users", $updateData, " `id` = '" . $getUser['id'] . "' ");

    die(json_encode(['status' => 'success', 'msg' => 'Đã thay đổi thông tin thành công']));
} else {
    die('The Request Not Found');
}
