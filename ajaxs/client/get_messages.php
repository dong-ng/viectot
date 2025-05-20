<?php
require_once __DIR__ . '/../../../core/DB.php';

$db = new DB();

$user_id = (int)($_GET['user_id'] ?? 0);
$admin_id = (int)($_GET['admin_id'] ?? 1);

$messages = $db->get_list("
    SELECT * FROM messages 
    WHERE (sender_id = $user_id AND receiver_id = $admin_id)
       OR (sender_id = $admin_id AND receiver_id = $user_id)
    ORDER BY created_at ASC
");

echo json_encode(['messages' => $messages]);
