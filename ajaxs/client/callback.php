<?php
define("IN_SITE", true);
require_once "../../core/DB.php";
require_once "../../core/helpers.php";
require_once '../../core/class/class.smtp.php';
require_once '../../core/class/PHPMailerAutoload.php';
require_once '../../core/class/class.phpmailer.php';

// Kiểm tra nếu có yêu cầu POST và các tham số cần thiết được truyền
if (isset($_POST['action']) && $_POST['action'] == 'DELETECALLBACK') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => '1', 'msg' => 'Vui lòng đăng nhập']));
    }
    
    $token = xss($_POST['token']);
    $callback = xss($_POST['callback']);
    $user = $NNL->get_row("SELECT * FROM `users` WHERE `token` = '" . xss($token) . "' AND `banned` = '0'");
    

                                    
    
    
    if (!$user) {
        die(json_encode(['status' => '1', 'msg' => 'Vui lòng đăng nhập']));
    }
    if($user['callback_card'] == $callback){
        
          $NNL->query("UPDATE users SET callback_card = NULL WHERE token = '$token'");
    exit(json_encode(array('status' => '2', 'msg' => 'Đã xóa thành công!'))); 
    }  
   
}





if (isset($_POST['action']) && $_POST['action'] == 'ADDCALLBACK') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => '1', 'msg' => 'Vui lòng đăng nhập']));
    }
    if (!$getUser = $NNL->get_row("SELECT * FROM `users` WHERE `token` = '" . xss($_POST['token']) . "' AND `banned` = '0' ")) {
        die(json_encode(['status' => '1', 'msg' => 'Vui lòng đăng nhập']));
    }
    $token = xss($_POST['token']);
    $callback = xss($_POST['callback']);
    if (empty($token)) {
        exit(json_encode(array('status' => '1', 'msg' => 'Không được!')));
    }
    $tool = $NNL->get_row(" SELECT * FROM `users` WHERE `token` = '$token' ");
    if (!$tool) {
        exit(json_encode(array('status' => '1', 'msg' => 'Định hack à không dễ vậy đâu!')));
    }
    if($getUser['callback_card'] == NULL){
    if (!$getUser = $NNL->get_row("SELECT * FROM `users` WHERE `callback_card` = '" . xss($_POST['callback']) . "' AND `banned` = '0' ")) {
        $NNL->query("UPDATE users SET callback_card = '$callback' WHERE token = '$token'");
    exit(json_encode(array('status' => '2', 'msg' => 'Đã thêm callback_card thành công!')));
        
    }
    else{
      die(json_encode(['status' => '1', 'msg' => 'callback_card đã được lưu ở tài khoản khác']));
        }   
    }
    else{
      die(json_encode(['status' => '1', 'msg' => 'chỉ thêm 1 callback_card']));
        }   
    
    
    
}


//ĐỔI TOKEN
if (isset($_POST['action']) && $_POST['action'] == 'CHANGETOKEN') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => '1', 'msg' => 'Vui lòng đăng nhập']));
    }
    
    $token = xss($_POST['token']);
    $user = $NNL->get_row("SELECT * FROM `users` WHERE `token` = '" . xss($token) . "' AND `banned` = '0'");
    
    if (!$user) {
        die(json_encode(['status' => '1', 'msg' => 'Vui lòng đăng nhập']));
    }
    
     $tokennew = md5(random('QWERTYUIOPASDGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789', 6) . time());
   
      
     if($NNL->query("UPDATE users SET token = '$tokennew' WHERE id = '" . $user['id'] . "'"))
    exit(json_encode(array('status' => '2', 'msg' => 'Đã đổi thành công!'))); 
       else
        exit(json_encode(array('status' => '1', 'msg' => 'Lỗi!'))); 
    
   
}


?>











