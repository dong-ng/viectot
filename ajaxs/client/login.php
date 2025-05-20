<?php

define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['email'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Username không được để trống'
        ]));
    }
    if (empty($_POST['password'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Mật khẩu không được để trống'
        ]));
    }
   
    $email = xss($_POST['email']);
    $password = xss($_POST['password']);
    
    $getUser = $NNL->get_row("SELECT * FROM `users` WHERE `email` = '$email' ");
    if (!$getUser) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Thông tin đăng nhập không chính xác'
        ]));
    }
    $Check = $NNL->get_row("SELECT * FROM `users` WHERE `email` = '$email' LIMIT 1");

if (!$Check || !password_verify($password, $Check['password'])) {
    die(json_encode([
        'status' => 'error',
        'msg'    => 'Thông tin đăng nhập không chính xác'
    ]));
}

    setcookie("token", $getUser['token'], time() + 3600, "/");
    $_SESSION['login'] = $getUser['token'];
    die(json_encode([
        'status' => 'success',
        'msg'    => 'Đăng nhập thành công'
    ]));
}
