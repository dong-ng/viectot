<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

global $NNL;

// Truy vấn dự án đang triển khai (chưa hết hạn)
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
    AND p.raised < p.goal
    ORDER BY p.end_date ASC
");

// Truy vấn dự án đã hoàn thành
$completedProjects = $NNL->get_list("
    SELECT 
        p.id,
        p.title,
        p.description,
        p.image,
        p.goal,
        p.end_date,
        p.raised AS raised
    FROM projects p
    WHERE p.raised >= p.goal
    ORDER BY p.end_date DESC
");

// Truy vấn dự án quá hạn
$overdueProjects = $NNL->get_list("
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
    AND p.end_date < CURDATE()
    AND p.raised < p.goal
    ORDER BY p.end_date DESC
");

if ($projects === false || $completedProjects === false || $overdueProjects === false) {
    die('Lỗi truy vấn dự án: Kiểm tra bảng projects và các cột.');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tất cả dự án</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font + CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .progress-bar { height: 6px; background-color: #f3f4f6; border-radius: 3px; overflow: hidden; }
        .progress-value { height: 100%; background-color: #10b981; border-radius: 3px; }
        .campaign-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'nav.php'?>

    <!-- Projects Section with Tabs -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4" x-data="{ tab: 'all' }">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Dự án</h2>
                <div class="flex gap-4">
                    <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-md font-medium transition">Tất cả</button>
                    <button @click="tab = 'completed'" :class="tab === 'completed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-md font-medium transition">Đã hoàn thành</button>
                    <button @click="tab = 'overdue'" :class="tab === 'overdue' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-md font-medium transition">Quá hạn</button>
                </div>
            </div>

            <!-- Đang triển khai -->
            <div x-show="tab === 'all'" x-cloak>
                <?php if (!empty($projects)) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($projects as $project): 
                            $progress = ($project['goal'] > 0) ? ($project['raised'] / $project['goal']) * 100 : 0;
                            $progress = min($progress, 100);
                            $remaining_percent = 100 - $progress;
                            $remaining_amount = max(0, $project['goal'] - $project['raised']);
                        ?>
                        <div class="campaign-card bg-white rounded-lg overflow-hidden shadow-sm">
                            <div class="relative">
                                <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                            </div>
                            <div class="p-5">
                                <div class="flex items-center mb-2">
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Dự án</span>
                                    <span class="ml-2 text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i> Việt Nam</span>
                                </div>
                                <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
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
                                <a href="/home/chitiet/<?php echo $project['id']; ?>" class="mt-4 block w-full py-2 bg-green-600 hover:bg-green-700 text-white text-center rounded-md font-medium transition duration-200">
                                    Ủng hộ ngay
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="text-center text-gray-500">Hiện tại chưa có dự án nào đang hoạt động.</p>
                <?php endif; ?>
            </div>

            <!-- Hoàn thành -->
            <div x-show="tab === 'completed'" x-cloak>
                <?php if (!empty($completedProjects)) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($completedProjects as $project): 
                            $progress = ($project['goal'] > 0) ? ($project['raised'] / $project['goal']) * 100 : 0;
                            $progress = min($progress, 100);
                        ?>
                        <div class="campaign-card bg-white rounded-lg overflow-hidden shadow-sm">
                            <div class="relative">
                                <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                                <div class="absolute top-2 right-2 bg-green-600 text-white text-xs font-medium px-2.5 py-0.5 rounded">Đã hoàn thành</div>
                            </div>
                            <div class="p-5">
                                <div class="flex items-center mb-2">
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Dự án</span>
                                    <span class="ml-2 text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i> Việt Nam</span>
                                </div>
                                <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <div class="mb-3">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium">Đã quyên góp <?php echo number_format($project['raised']); ?>đ</span>
                                        <span><?php echo round($progress); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-value" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                                <a href="/home/chitiet/<?php echo $project['id']; ?>" class="mt-4 block w-full py-2 bg-gray-600 hover:bg-gray-700 text-white text-center rounded-md font-medium transition duration-200">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="text-center text-gray-500">Hiện tại chưa có dự án nào hoàn thành.</p>
                <?php endif; ?>
            </div>

            <!-- Quá hạn -->
            <div x-show="tab === 'overdue'" x-cloak>
                <?php if (!empty($overdueProjects)) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($overdueProjects as $project): 
                            $progress = ($project['goal'] > 0) ? ($project['raised'] / $project['goal']) * 100 : 0;
                            $progress = min($progress, 100);
                            $remaining_amount = max(0, $project['goal'] - $project['raised']);
                        ?>
                        <div class="campaign-card bg-white rounded-lg overflow-hidden shadow-sm">
                            <div class="relative">
                                <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                                <div class="absolute top-2 right-2 bg-red-600 text-white text-xs font-medium px-2.5 py-0.5 rounded">Quá hạn</div>
                            </div>
                            <div class="p-5">
                                <div class="flex items-center mb-2">
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Dự án</span>
                                    <span class="ml-2 text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i> Việt Nam</span>
                                </div>
                                <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
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
                                    <span>Thiếu <?php echo number_format($remaining_amount); ?>đ</span>
                                    <span>Hết hạn: <?php echo date('d/m/Y', strtotime($project['end_date'])); ?></span>
                                </div>
                                <a href="/home/chitiet/<?php echo $project['id']; ?>" class="mt-4 block w-full py-2 bg-gray-600 hover:bg-gray-700 text-white text-center rounded-md font-medium transition duration-200">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="text-center text-gray-500">Hiện tại chưa có dự án nào quá hạn.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'?>

    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-6 right-6 bg-green-600 text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center hover:bg-green-700 transition duration-200 hidden">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Script -->
    <script>
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
    </script>
</body>
</html>