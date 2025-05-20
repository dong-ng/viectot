<?php
define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");

// Khởi tạo session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra dữ liệu đầu vào
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password'])) {
        die(json_encode([
            'status' => 'error',
            'msg' => 'Vui lòng nhập đầy đủ họ tên, email và mật khẩu'
        ]));
    }

    $name     = xss($_POST['name']);
    $email    = xss($_POST['email']);
    $password = xss($_POST['password']);

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode([
            'status' => 'error',
            'msg' => 'Email không hợp lệ'
        ]));
    }

    // Kiểm tra trùng email
    if ($NNL->num_rows("SELECT * FROM `users` WHERE `email` = '$email'") > 0) {
        die(json_encode([
            'status' => 'error',
            'msg' => 'Email đã tồn tại trong hệ thống'
        ]));
    }

    // Tạo token và hash mật khẩu
    $token = bin2hex(random_bytes(16));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');

    // Thêm vào bảng users
    $insert = $NNL->insert("users", [
        'name'           => $name,
        'email'          => $email,
        'password'       => $hashedPassword,
        'token'          => $token,
        'created_at'     => $now,
        'updated_at'     => $now
    ]);

    if ($insert) {
        // Set cookie & session nếu muốn login ngay
        setcookie("token", $token, time() + 3600, "/");
        $_SESSION['login'] = $token;

        die(json_encode([
            'status' => 'success',
            'msg' => 'Đăng ký thành công!'
        ]));
    } else {
        die(json_encode([
            'status' => 'error',
            'msg' => 'Không thể tạo tài khoản, vui lòng thử lại'
        ]));
    }
}
?>
