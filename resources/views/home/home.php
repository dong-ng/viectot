<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';
global $NNL;

// Hàm cắt ngắn văn bản
function truncate($text, $length = 150) {
    $text = trim((string) $text); // Ép kiểu và loại bỏ khoảng trắng
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
}


// Truy vấn lấy 3 chiến dịch gần đến hạn nhất, chưa đạt 100%, và chưa hết hạn
$projects = $NNL->get_list("
    SELECT 
        p.id,
        p.title,
        p.description,
        p.image,
        p.goal,
        p.end_date,
        p.raised AS raised
    FROM projects p
    WHERE p.end_date IS NOT NULL 
    AND (p.end_date > CURDATE() OR p.end_date = CURDATE())
    AND (p.raised / p.goal) < 1
    ORDER BY p.end_date ASC
    LIMIT 3
");
if ($projects === false) {
    die('Lỗi truy vấn dự án: Kiểm tra bảng projects và các cột.');
}

// Truy vấn lấy 3 dự án đã đạt 100% cho mục Câu chuyện thành công
$successStories = $NNL->get_list("
    SELECT 
        p.id,
        p.title,
        p.description,
        p.image,
        p.goal,
        p.raised AS raised
    FROM projects p
    WHERE (p.raised / p.goal) >= 1
    ORDER BY p.raised DESC
    LIMIT 3
");
if ($successStories === false) {
    die('Lỗi truy vấn câu chuyện thành công: Kiểm tra bảng projects và các cột.');
}

// Truy vấn lấy 2 dự án mới nhất từ bảng projects (dựa trên ID lớn nhất)
$newProjects = $NNL->get_list("
    SELECT 
        id,
        title,
        description,
        image,
        goal,
        end_date,
        raised AS raised
    FROM projects
    ORDER BY id DESC
    LIMIT 2
");
if ($newProjects === false) {
    die('Lỗi truy vấn dự án mới: Kiểm tra bảng projects và các cột.');
}

// Lấy tháng hiện tại để hiển thị tiêu đề động
$monthNames = [
    1 => 'tháng 1',
    2 => 'tháng 2',
    3 => 'tháng 3',
    4 => 'tháng 4',
    5 => 'tháng 5',
    6 => 'tháng 6',
    7 => 'tháng 7',
    8 => 'tháng 8',
    9 => 'tháng 9',
    10 => 'tháng 10',
    11 => 'tháng 11',
    12 => 'tháng 12'
];
$currentMonth = (int)date('m');
$monthDisplay = $monthNames[$currentMonth];

$bannerImage = '';
if (!empty($projects)) {
    $randomProject = $projects[array_rand($projects)];
    $bannerImage = '/images/' . htmlspecialchars($randomProject['image']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viectot - Nền tảng gây quỹ cộng đồng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        .progress-bar { height: 6px; background-color: #f3f4f6; border-radius: 3px; overflow: hidden; }
        .progress-value { height: 100%; background-color: #10b981; border-radius: 3px; position: relative; overflow: hidden; transition: width 1s ease-in-out; }
        .progress-value::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%); animation: shimmer 2s infinite; }
        .campaign-card { transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24); }
        .campaign-card:hover { transform: translateY(-10px); box-shadow: 0 14px 28px rgba(0,0,0,0.12), 0 10px 10px rgba(0,0,0,0.10); }
        .campaign-card img { transition: transform 0.5s ease; }
        .campaign-card:hover img { transform: scale(1.05); }
        .category-item:hover { background: #f0fdf4; border-color: #10b981; transform: translateY(-3px); transition: all 0.3s ease; }
        .mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .mobile-menu.active { max-height: 500px; }
        .btn-donate { position: relative; overflow: hidden; transition: all 0.3s ease; }
        .btn-donate::after { content: ''; position: absolute; top: 50%; left: 50%; width: 5px; height: 5px; background: rgba(255, 255, 255, 0.5); opacity: 0; border-radius: 100%; transform: scale(1, 1) translate(-50%); transform-origin: 50% 50%; }
        .btn-donate:hover::after { animation: ripple 1s ease-out; }
        .fade-banner { transition: opacity 1s ease-in-out; opacity: 1; }
        .fade-banner.fade-out { opacity: 0; }
        .success-story-card { transition: all 0.3s ease; }
        .success-story-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        section { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
        section.visible { opacity: 1; transform: translateY(0); }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes ripple { 0% { transform: scale(0, 0); opacity: 1; } 100% { transform: scale(20, 20); opacity: 0; } }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .honor-row { transition: all 0.3s ease; animation: fadeInUp 0.5s ease-out forwards; opacity: 0; }
        .honor-row:nth-child(1) { animation-delay: 0.1s; }
        .honor-row:nth-child(2) { animation-delay: 0.2s; }
        .honor-row:nth-child(3) { animation-delay: 0.3s; }
        .honor-row:nth-child(4) { animation-delay: 0.4s; }
        .honor-row:nth-child(5) { animation-delay: 0.5s; }
        .honor-row:nth-child(6) { animation-delay: 0.6s; }
        .honor-row:nth-child(7) { animation-delay: 0.7s; }
        .honor-row:nth-child(8) { animation-delay: 0.8s; }
        .honor-row:nth-child(9) { animation-delay: 0.9s; }
        .honor-row:nth-child(10) { animation-delay: 1s; }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    <script>
    const bannerImages = <?php echo json_encode(array_map(function($p) {
        return '/images/' . $p['image'];
    }, $projects)); ?>;
    </script>
</head>
<body class="bg-gray-50">
    <div id="pageLoader" class="fixed inset-0 bg-white z-50 flex items-center justify-center transition-opacity duration-500">
        <div class="loader animate-spin rounded-full border-t-4 border-green-500 border-solid h-12 w-12"></div>
    </div>
    <?php include 'nav.php'?>
    <section id="heroBanner" class="relative text-white h-[500px] bg-cover bg-center fade-banner">
        <div class="absolute inset-0 bg-green-600 opacity-30"></div>
        <div class="container mx-auto px-4 h-full flex flex-col items-center justify-center text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 drop-shadow-[2px_2px_4px_rgba(0,0,0,0.7)]">
                Kết nối yêu thương <br>lan tỏa hạnh phúc
            </h1>
            <p class="text-lg mb-6 drop-shadow-[1px_1px_3px_rgba(0,0,0,0.5)]">
                Trang web phục vụ việc học tập, không có mục đích thương mại
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="/home/du-an" class="px-6 py-3 bg-white text-green-600 rounded-md font-medium hover:bg-gray-100 transition btn-donate">
                    Ủng hộ ngay

                </a>
                <a href="/home/createcamp" class="px-6 py-3 border border-white text-white rounded-md font-medium hover:bg-white hover:text-green-600 transition btn-donate">
                    Tạo chiến dịch
                </a>
            </div>
        </div>
    </section>
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-center mb-8">Danh mục gây quỹ</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($categories as $cat): ?>
                    <a href="/home/category/<?= $cat['id'] ?>" class="category-item border rounded-lg p-4 text-center transition duration-200">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="<?= $cat['image_avt'] ?> text-green-600 text-xl"></i>
                        </div>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($cat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Chiến dịch nổi bật</h2>
                <a href="<?php BASE_URL('')?>/home/du-an" class="text-green-600 hover:text-green-700 font-medium">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <?php
            if (!empty($projects)) {
                echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
                foreach ($projects as $project) {
                    $progress = ($project['goal'] > 0) ? ($project['raised'] / $project['goal']) * 100 : 0;
                    $progress = min($progress, 100);
                    $remaining_percent = 100 - $progress;
                    $remaining_amount = max(0, $project['goal'] - $project['raised']);
            ?>
                    <div class="campaign-card bg-white rounded-lg overflow-hidden shadow-sm">
                        <div class="relative overflow-hidden">
                            <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                        </div>
                        <div class="p-5">
                            <div class="flex items-center mb-2">
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Chiến dịch</span>
                                <span class="ml-2 text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i> Việt Nam</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars(truncate($project['description'], 150)); ?></p>
                            <div class="mb-3">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium">Đã quyên góp <?php echo number_format($project['raised']); ?>đ</span>
                                    <span><?php echo round($progress); ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-value" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 mb-2">
                                <span>Còn lại <?php echo number_format($remaining_amount); ?>đ</span>
                                <span><?php echo round($remaining_percent); ?>% nữa</span>
                            </div>
                            <a href="/home/chitiet/<?php echo $project['id']; ?>" class="mt-4 block w-full py-2 bg-green-600 hover:bg-green-700 text-white text-center rounded-md font-medium transition duration-200 btn-donate">
                                Ủng hộ ngay
                            </a>
                        </div>
                    </div>
            <?php
                }
                echo '</div>';
            } else {
                echo '<p class="text-center text-gray-500">Hiện tại chưa có chiến dịch nào.</p>';
            }
            ?>
        </div>
    </section>
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="md:w-2/3">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Dự án mới</h2>
                        <div class="w-20 h-1 bg-green-500 mx-auto"></div>
                        <p class="text-gray-600 mt-4 max-w-2xl mx-auto">Khám phá các dự án mới nhất vừa được phê duyệt</p>
                    </div>
                    <?php
                    if (!empty($newProjects)) {
                        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
                        foreach ($newProjects as $project) {
                            $progress = ($project['goal'] > 0) ? ($project['raised'] / $project['goal']) * 100 : 0;
                            $progress = min($progress, 100);
                            $remaining_percent = 100 - $progress;
                            $remaining_amount = max(0, $project['goal'] - $project['raised']);
                    ?>
                            <div class="campaign-card bg-gray-50 rounded-lg overflow-hidden shadow-sm">
                                <div class="relative overflow-hidden">
                                    <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                                </div>
                                <div class="p-5">
                                    <div class="flex items-center mb-2">
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Dự án mới</span>
                                        <span class="ml-2 text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i> Việt Nam</span>
                                    </div>
                                    <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars(truncate($project['description'], 150)); ?></p>
                                    <div class="mb-3">
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="font-medium">Đã quyên góp <?php echo number_format($project['raised']); ?>đ</span>
                                            <span><?php echo round($progress); ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-value" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-500 mb-2">
                                        <span>Còn lại <?php echo number_format($remaining_amount); ?>đ</span>
                                        <span>Hết hạn: <?php echo date('d/m/Y', strtotime($project['end_date'])); ?></span>
                                    </div>
                                    <a href="/home/chitiet/<?php echo $project['id']; ?>" class="mt-4 block w-full py-2 bg-green-600 hover:bg-green-700 text-white text-center rounded-md font-medium transition duration-200 btn-donate">
                                        Ủng hộ ngay
                                    </a>
                                </div>
                            </div>
                    <?php
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="text-center text-gray-500 py-8">Chưa có dự án mới nào được phê duyệt.</p>';
                    }
                    ?>
                </div>
                <div class="md:w-1/3">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Ghi nhận những đóng góp từ cộng đồng <?php echo $monthDisplay; ?></h2>
                        <div class="w-20 h-1 bg-green-500 mx-auto"></div>
                    </div>
                    <?php
                    $topDonors = $NNL->get_list("
                        SELECT 
                            MIN(name) AS name,
                            anonymous,
                            SUM(amount) AS total_amount,
                            MIN(created_at) AS first_donation_time
                        FROM orders
                        WHERE status = 'completed'
                        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                        GROUP BY email, anonymous
                        ORDER BY total_amount DESC, first_donation_time ASC
                        LIMIT 8
                    ");
                    if (!empty($topDonors)) {
                        echo '<div class="bg-white rounded-xl shadow-md overflow-hidden">';
                        echo '<div class="grid grid-cols-12 bg-green-600 text-white font-medium p-4 hidden md:grid">';
                        echo '<div class="col-span-1 text-center">#</div>';
                        echo '<div class="col-span-7">Nhà hảo tâm</div>';
                        echo '<div class="col-span-4 text-right">Tổng tiền</div>';
                        echo '</div>';
                        $rank = 1;
                        foreach ($topDonors as $donor) {
                            $rowClass = $rank <= 3 ? 'border-l-4' : '';
                            $medal = '';
                            if ($rank === 1) {
                                $rowClass .= ' border-l-yellow-500 bg-yellow-50';
                                $medal = '<span class="ml-2 text-yellow-500"><i class="fas fa-crown"></i></span>';
                            } elseif ($rank === 2) {
                                $rowClass .= ' border-l-gray-400 bg-gray-50';
                                $medal = '<span class="ml-2 text-gray-400"><i class="fas fa-medal"></i></span>';
                            } elseif ($rank === 3) {
                                $rowClass .= ' border-l-amber-600 bg-amber-50';
                                $medal = '<span class="ml-2 text-amber-600"><i class="fas fa-medal"></i></span>';
                            } else {
                                $rowClass .= ' border-l-transparent';
                            }
                            if ($donor['anonymous'] == 1 || trim($donor['name']) === '') {
                                $displayName = '<i class="text-gray-400">Nhà hảo tâm ẩn danh</i>';
                            } else {
                                $displayName = htmlspecialchars($donor['name']);
                            }
                            echo '<div class="honor-row grid grid-cols-12 items-center p-4 hover:bg-green-50 transition duration-200 '.$rowClass.'">';
                            echo '<div class="col-span-1 text-center font-bold text-gray-700">'.$rank.'</div>';
                            echo '<div class="col-span-7 font-medium text-gray-800">';
                            echo '<div class="flex items-center">'.$displayName.$medal.'</div>';
                            echo '</div>';
                            echo '<div class="col-span-4 text-right font-bold text-green-600">'.number_format($donor['total_amount']).'đ</div>';
                            echo '</div>';
                            $rank++;
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="text-center text-gray-500 py-8">Chưa có dữ liệu nhà hảo tâm trong khoảng thời gian này.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-center mb-12">Viectot hoạt động như thế nào?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-search-dollar text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">1. Tìm chiến dịch</h3>
                    <p class="text-gray-600">Tìm kiếm các chiến dịch gây quỹ mà bạn quan tâm</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-hand-holding-heart text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">2. Ủng hộ</h3>
                    <p class="text-gray-600">Chọn mức ủng hộ và thực hiện thanh toán an toàn</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-heart text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">3. Theo dõi</h3>
                    <p class="text-gray-600">Nhận cập nhật về tình hình chiến dịch bạn đã ủng hộ</p>
                </div>
            </div>
        </div>
    </section>
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Câu chuyện thành công</h2>
                <a href="<?php BASE_URL('')?>/home/du-an" class="text-green-600 hover:text-green-700 font-medium">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <?php
            if (!empty($successStories)) {
                echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
                foreach ($successStories as $story) {
            ?>
                    <div class="success-story-card bg-gray-50 rounded-lg overflow-hidden">
                        <img src="/images/<?php echo htmlspecialchars($story['image']); ?>" alt="<?php echo htmlspecialchars($story['title']); ?>" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($story['title']); ?></h3>
                            <p class="text-gray-600 mb-4 line-clamp-3"><?php echo htmlspecialchars(truncate($story['description'], 150)); ?></p>
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <span class="font-medium">Đã hoàn thành</span>
                            </div>
                            <a href="/home/chitiet/<?php echo $story['id']; ?>" class="mt-4 block w-full py-2 bg-gray-600 hover:bg-gray-700 text-white text-center rounded-md font-medium transition duration-200">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
            <?php
                }
                echo '</div>';
            } else {
                echo '<p class="text-center text-gray-500">Hiện tại chưa có câu chuyện thành công nào.</p>';
            }
            ?>
        </div>
    </section>
    <?php include 'footer.php'?>
    <button id="backToTop" class="hidden fixed bottom-6 right-6 bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700 transition duration-200 z-40">
        <i class="fas fa-arrow-up"></i>
    </button>
    <script>
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');
        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            mobileMenu.classList.toggle('active');
        });
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && e.target !== mobileMenuButton) {
                mobileMenu.classList.remove('active');
            }
        });
        userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
});
        document.addEventListener('click', (e) => {
            if (!userDropdown.contains(e.target) && e.target !== userMenuButton) {
                userDropdown.classList.add('hidden');
            }
        });
        const backToTopButton = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('hidden');
            } else {
                backToTopButton.classList.add('hidden');
            }
        });
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        const heroBanner = document.getElementById("heroBanner");
        if (bannerImages.length > 0 && heroBanner) {
            let index = Math.floor(Math.random() * bannerImages.length);
            heroBanner.style.backgroundImage = `url('${bannerImages[index]}')`;
            setInterval(() => {
                heroBanner.classList.add("fade-out");
                setTimeout(() => {
                    index = (index + 1) % bannerImages.length;
                    heroBanner.style.backgroundImage = `url('${bannerImages[index]}')`;
                    heroBanner.classList.remove("fade-out");
                }, 1000);
            }, 5000);
        }
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });
            sections.forEach(section => {
                observer.observe(section);
            });
        });
        window.addEventListener('load', () => {
            const loader = document.getElementById('pageLoader');
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }, 500);
        });
    </script>
</body>
</html>