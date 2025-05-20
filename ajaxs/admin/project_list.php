<?php
define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");
require_once("../../core/is_user.php");

$db = new DB();
header('Content-Type: application/json');

// Lấy từ bảng projects
$projects = $db->get_list("
    SELECT 
        p.id, p.title, p.description, p.image, p.goal, 
        p.start_date, p.end_date, p.disbursed_amount, p.refund,
        p.raised AS raised,
        ROUND(IFNULL(p.raised / p.goal * 100, 0)) AS progress,
        'project' AS source
    FROM projects p
    GROUP BY p.id
");

// Lấy từ project_requests (giữ nguyên)
$requests = $db->get_list("
    SELECT 
        id, title, description, image, goal,
        start_date, end_date,
        0 AS raised, 0 AS progress, 0 AS disbursed_amount,
        'request' AS source
    FROM project_requests
    WHERE status = 'pending'
");

// Gộp dữ liệu lại
$data = array_merge($projects, $requests);

echo json_encode([
    'success' => true,
    'data' => $data
]);
?>