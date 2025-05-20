<?php
require_once __DIR__ . '/../../../../core/helpers.php';
require_once __DIR__ . '/../../../../core/DB.php';
require_once(__DIR__ . '/../../../../core/is_user.php');
CheckLogin();
CheckAdmin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin người dùng hiện tại
$user = $NNL->get_row("SELECT * FROM users WHERE id = $user_id");

if (!$user) {
    header('Location: /admin/users');
    exit();
}

$del = $NNL->remove("users", "`id`='$user_id'");
if ($del) {
    header("Location: /admin/users?success=1");
    exit();
} else {
    header("Location: /admin/users?error=1");
    exit();
}
?>