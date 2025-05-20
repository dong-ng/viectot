<?php
session_start();

// Giả định BASE_URL và $NNL đã được định nghĩa trước đó
// define('BASE_URL', 'http://yourdomain.com/'); // Thay bằng domain thực tế của bạn

// Kiểm tra đăng nhập bằng cookie và session
if (isset($_COOKIE["token"])) {
    $getUser = $NNL->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_COOKIE['token']) . "'");
    if (!$getUser) {
        header("Location: " . BASE_URL . "home/logout");
        exit();
    }
    $_SESSION['login'] = $getUser['token'];
}

if (!isset($_SESSION['login'])) {
    $my_username = false;
    $my_level = null;
} else {
    $getUser = $NNL->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_SESSION['login']) . "'");
    if (!$getUser) {
        header("Location: " . BASE_URL . "home/login");
        exit();
    }
    $my_username = $getUser['username']; // Lấy username từ database
    $my_admin = $getUser['is_admin']; // Lấy level từ database (giả định cột level tồn tại)
}

// Hàm kiểm tra đăng nhập
function CheckLogin()
{
    global $my_username;
    return $my_username !== false;
}

// Hàm kiểm tra admin
function CheckAdmin()
{
    global $my_admin;
    if ($my_admin !== '1') {
        die('<script type="text/javascript">setTimeout(function(){ location.href = "/"; }, 0);</script>');
    }
}
require_once __DIR__ . '/../middleware/logger.php';

?>