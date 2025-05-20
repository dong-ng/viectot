<?php
session_start();

define("IN_SITE", true);
require_once("../../core/DB.php");
require_once("../../core/helpers.php");
require_once("../../core/is_user.php");

$db = new DB();

// Lấy tháng/năm từ request hoặc dùng hiện tại
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$daysInMonth = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

// --- Doanh thu theo ngày ---
$dailyRevenue = $db->get_list("
    SELECT 
        DAY(FROM_UNIXTIME(created_at)) as day, 
        SUM(amount) as revenue
    FROM orders
    WHERE status = 'completed'
      AND MONTH(FROM_UNIXTIME(created_at)) = $currentMonth
      AND YEAR(FROM_UNIXTIME(created_at)) = $currentYear
    GROUP BY day
    ORDER BY day ASC
");

$revenueData = array_fill(1, $daysInMonth, 0);
foreach ($dailyRevenue as $row) {
    $revenueData[(int)$row['day']] = $row['revenue'];
}

// --- Top 5 dự án ---
$topProjects = $db->get_list("
    SELECT 
        p.title, 
        IFNULL(SUM(o.amount), 0) AS raised
    FROM projects p
    LEFT JOIN orders o ON p.id = o.project_id AND o.status = 'completed'
    GROUP BY p.id
    ORDER BY raised DESC
    LIMIT 5
");

$projectTitles = array_column($topProjects, 'title');
$projectRaised = array_column($topProjects, 'raised');

// --- Top 5 người ủng hộ ---
$topDonors = $db->get_list("
    SELECT 
        IF(anonymous = 1, 'Ẩn danh', name) AS name,
        email,
        SUM(amount) AS total_amount,
        COUNT(*) AS donation_count
    FROM orders
    WHERE status = 'completed'
    GROUP BY email
    ORDER BY total_amount DESC
    LIMIT 5
");

$topDonorsData = array_map(function($row) {
    return [
        'name' => $row['name'],
        'total_amount' => (int)$row['total_amount'],
        'donation_count' => (int)$row['donation_count']
    ];
}, $topDonors);

// --- Thống kê tổng quan ---
$stats = $db->get_row("
    SELECT 
        (SELECT COUNT(*) FROM projects) as totalProjects,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as totalDonations,
        (SELECT COUNT(*) FROM users) as totalUsers,
        (SELECT IFNULL(SUM(raised), 0) FROM projects) as totalRevenue
");

// --- Lịch sử ủng hộ gần đây ---
$recentDonations = $db->get_list("
    SELECT 
        o.*, 
        p.title as project_title,
        IF(o.anonymous = 1, 'Ẩn danh', o.name) as display_name
    FROM orders o
    LEFT JOIN projects p ON o.project_id = p.id
    WHERE o.status = 'completed'
    ORDER BY o.created_at DESC
    LIMIT 10
");

$activities = [];
foreach ($recentDonations as $donation) {
    $projectName = $donation['project_title'] ?: 'Dự án chung';
    $timeAgo = time() - $donation['created_at'];
    
    if ($timeAgo < 60) $timeText = 'Vừa xong';
    elseif ($timeAgo < 3600) $timeText = floor($timeAgo/60) . ' phút trước';
    elseif ($timeAgo < 86400) $timeText = floor($timeAgo/3600) . ' giờ trước';
    else $timeText = floor($timeAgo/86400) . ' ngày trước';
    
    $activities[] = [
        'time' => $timeText,
        'user' => $donation['display_name'],
        'action' => 'Ủng hộ',
        'details' => number_format($donation['amount']) . ' VNĐ cho "' . $projectName . '"'
    ];
}

// --- Dữ liệu cho biểu đồ tròn: tổng raised, giải ngân, hoàn tiền ---
$fundStats = $db->get_row("
    SELECT 
        SUM(raised) AS totalRaised,
        SUM(disbursed_amount) AS totalDisbursed
    FROM projects
");

// Tính tổng hoàn tiền từ sodu trong bảng users
$totalRefunded = $db->get_row("SELECT IFNULL(SUM(sodu), 0) AS totalRefunded FROM users")['totalRefunded'];

$fundByProject = $db->get_list("
    SELECT 
        title,
        raised,
        disbursed_amount
    FROM projects
    WHERE raised > 0 OR disbursed_amount > 0
");

$fundProjects = array_map(function($row) {
    return [
        'title' => $row['title'],
        'raised' => (int)$row['raised'],
        'disbursed' => (int)$row['disbursed_amount']
    ];
}, $fundByProject);

// --- Trả về JSON ---
echo json_encode([
    'success' => true,
    'daysInMonth' => $daysInMonth,
    'revenueData' => array_values($revenueData),
    'projectTitles' => $projectTitles,
    'projectRaised' => $projectRaised,
    'totalProjects' => $stats['totalProjects'],
    'totalDonations' => $stats['totalDonations'],
    'totalUsers' => $stats['totalUsers'],
    'totalRevenue' => $stats['totalRevenue'],
    'activities' => $activities,
    'topDonors' => $topDonorsData,
    'fundBreakdown' => [
        'totalRaised' => (int)$fundStats['totalRaised'],
        'totalDisbursed' => (int)$fundStats['totalDisbursed'],
        'totalRefunded' => (int)$totalRefunded,
        'projects' => $fundProjects
    ]
]);
?>