<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

global $NNL;

if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    $url = $_SERVER['REQUEST_URI'] ?? '';
    $method = strtolower($_SERVER['REQUEST_METHOD']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // 🔐 Lấy user_id từ cả user và admin
    $user_id = 0;
    if (isset($getUser['id'])) {
        $user_id = (int)$getUser['id'];
    } elseif (isset($_SESSION['admin_login'])) {
        $adminData = $NNL->get_row("SELECT * FROM users WHERE token = '" . check_string($_SESSION['admin_login']) . "'");
        if ($adminData) {
            $user_id = (int)$adminData['id'];
        }
    }

    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    $postData = [];

    if (strpos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true) ?? [];
    } else {
        $postData = $_POST;
    }

    $content = json_encode([
        'url' => $url,
        'data' => $postData
    ], JSON_UNESCAPED_UNICODE);

    $NNL->insert('logs', [
        'user_id' => $user_id,
        'action' => $method,
        'target_table' => '',
        'target_id' => NULL,
        'content' => $content,
        'ip_address' => $ip,
        'user_agent' => $userAgent
    ]);
}
?>
