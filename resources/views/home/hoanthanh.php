<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';
global $NNL;

// Truy vấn lấy các dự án đã hoàn thành (đạt hoặc vượt mục tiêu)
// $completed_projects = $NNL->get_list("
//     SELECT 
//         p.id,
//         p.title,
//         p.description,
//         p.image,
//         p.goal,
//         p.start_date,
//         p.end_date,
//         p.creator_id,
//         u.fullname as creator_name,
//         u.avatar as creator_avatar,
//         IFNULL(SUM(o.amount), 0) AS raised,
//         p.implementation_details,
//         p.disbursement_info,
//         p.completion_date,
//         p.success_images
//     FROM projects p
//     LEFT JOIN orders o ON p.id = o.project_id AND o.status = 'completed'
//     LEFT JOIN users u ON p.creator_id = u.id
//     GROUP BY p.id
//     HAVING raised >= p.goal OR p.status = 'completed'
//     ORDER BY completion_date DESC
// ");

// Kiểm tra lỗi truy vấn
if ($completed_projects === false) {
    die('Lỗi truy vấn dự án: Kiểm tra bảng projects và các cột.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dự án đã hoàn thành | Viectot</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .progress-bar {
            height: 6px;
            background-color: #f3f4f6;
            border-radius: 3px;
            overflow: hidden;
        }
        .progress-value {
            height: 100%;
            background-color: #10b981;
            border-radius: 3px;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .gallery img:hover {
            transform: scale(1.05);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .modal-img {
            max-width: 90%;
            max-height: 90%;
        }
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: #e5e7eb;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -12px;
            background-color: #10b981;
            border: 4px solid #10b981;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        .left {
            left: 0;
        }
        .right {
            left: 50%;
        }
        .left::before {
            content: " ";
            height: 0;
            position: absolute;
            top: 22px;
            width: 0;
            z-index: 1;
            right: 30px;
            border: medium solid #e5e7eb;
            border-width: 10px 0 10px 10px;
            border-color: transparent transparent transparent #e5e7eb;
        }
        .right::before {
            content: " ";
            height: 0;
            position: absolute;
            top: 22px;
            width: 0;
            z-index: 1;
            left: 30px;
            border: medium solid #e5e7eb;
            border-width: 10px 10px 10px 0;
            border-color: transparent #e5e7eb transparent transparent;
        }
        .right::after {
            left: -12px;
        }
        .timeline-content {
            padding: 20px 30px;
            background-color: white;
            position: relative;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'nav.php'?>

    <!-- Main Content -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Dự án đã hoàn thành</h1>
                <div class="mt-4 md:mt-0">
                    <form class="flex">
                        <input type="text" placeholder="Tìm kiếm dự án..." class="px-4 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-r-md hover:bg-green-700 transition duration-200">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($completed_projects)): ?>
                <div class="grid grid-cols-1 gap-8">
                    <?php foreach ($completed_projects as $project): 
                        $progress = ($project['goal'] > 0) ? ($project['raised'] / $project['goal']) * 100 : 0;
                        $progress = min($progress, 100);
                        $success_images = json_decode($project['success_images'] ?? '[]', true);
                    ?>
                        <div class="project-card bg-white rounded-lg overflow-hidden shadow-md transition duration-200">
                            <div class="md:flex">
                                <div class="md:w-1/3">
                                    <img src="<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-64 object-cover">
                                </div>
                                <div class="p-6 md:w-2/3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Đã hoàn thành</span>
                                            <h2 class="text-2xl font-bold mt-2 mb-1"><?php echo htmlspecialchars($project['title']); ?></h2>
                                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($project['description']); ?></p>
                                        </div>
                                        <div class="flex items-center">
                                            <img src="<?php echo htmlspecialchars($project['creator_avatar'] ?? '/assets/images/default-avatar.jpg'); ?>" alt="<?php echo htmlspecialchars($project['creator_name']); ?>" class="w-10 h-10 rounded-full object-cover">
                                            <span class="ml-2 text-sm"><?php echo htmlspecialchars($project['creator_name']); ?></span>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="font-medium">Đã quyên góp: <?php echo number_format($project['raised']); ?>đ</span>
                                            <span>Mục tiêu: <?php echo number_format($project['goal']); ?>đ</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-value" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <div class="bg-gray-100 px-3 py-1 rounded-full text-sm">
                                            <i class="fas fa-calendar-alt text-green-600 mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($project['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                                        </div>
                                        <div class="bg-gray-100 px-3 py-1 rounded-full text-sm">
                                            <i class="fas fa-check-circle text-green-600 mr-1"></i>
                                            Hoàn thành: <?php echo date('d/m/Y', strtotime($project['completion_date'] ?? $project['end_date'])); ?>
                                        </div>
                                    </div>

                                    <!-- Tabs Navigation -->
                                    <div class="mt-6 border-b border-gray-200">
                                        <div class="flex flex-wrap -mb-px">
                                            <button onclick="openTab(event, 'details-<?php echo $project['id']; ?>')" class="tab-button mr-2 py-2 px-4 font-medium text-sm border-b-2 border-green-600 text-green-600">
                                                Chi tiết hoàn thành
                                            </button>
                                            <button onclick="openTab(event, 'implementation-<?php echo $project['id']; ?>')" class="tab-button mr-2 py-2 px-4 font-medium text-sm border-b-2 border-transparent hover:text-green-600 hover:border-green-300">
                                                Triển khai thực hiện
                                            </button>
                                            <button onclick="openTab(event, 'disbursement-<?php echo $project['id']; ?>')" class="tab-button mr-2 py-2 px-4 font-medium text-sm border-b-2 border-transparent hover:text-green-600 hover:border-green-300">
                                                Giải ngân
                                            </button>
                                            <button onclick="openTab(event, 'gallery-<?php echo $project['id']; ?>')" class="tab-button py-2 px-4 font-medium text-sm border-b-2 border-transparent hover:text-green-600 hover:border-green-300">
                                                Hình ảnh
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tabs Content -->
                                    <div id="details-<?php echo $project['id']; ?>" class="tab-content py-4 active">
                                        <h3 class="font-bold text-lg mb-2">Kết quả đạt được</h3>
                                        <p class="text-gray-700">Dự án đã hoàn thành vượt mục tiêu đề ra với <?php echo round($progress); ?>% số tiền quyên góp.</p>
                                        <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                    </div>

                                    <div id="implementation-<?php echo $project['id']; ?>" class="tab-content py-4">
                                        <h3 class="font-bold text-lg mb-2">Quá trình triển khai</h3>
                                        <?php if (!empty($project['implementation_details'])): ?>
                                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($project['implementation_details'])); ?></p>
                                            
                                            <!-- Timeline -->
                                            <div class="timeline mt-6">
                                                <div class="timeline-item left">
                                                    <div class="timeline-content">
                                                        <h4 class="font-bold">Bắt đầu dự án</h4>
                                                        <p class="text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($project['start_date'])); ?></p>
                                                        <p class="mt-2">Khởi động chiến dịch gây quỹ</p>
                                                    </div>
                                                </div>
                                                <div class="timeline-item right">
                                                    <div class="timeline-content">
                                                        <h4 class="font-bold">Đạt mục tiêu</h4>
                                                        <p class="text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($project['end_date'])); ?></p>
                                                        <p class="mt-2">Hoàn thành mục tiêu gây quỹ</p>
                                                    </div>
                                                </div>
                                                <div class="timeline-item left">
                                                    <div class="timeline-content">
                                                        <h4 class="font-bold">Triển khai thực tế</h4>
                                                        <p class="text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($project['completion_date'] ?? $project['end_date'])); ?></p>
                                                        <p class="mt-2">Sử dụng số tiền quyên góp để thực hiện dự án</p>
                                                    </div>
                                                </div>
                                                <div class="timeline-item right">
                                                    <div class="timeline-content">
                                                        <h4 class="font-bold">Hoàn thành</h4>
                                                        <p class="text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($project['completion_date'] ?? $project['end_date'])); ?></p>
                                                        <p class="mt-2">Dự án đã hoàn thành và báo cáo đến nhà tài trợ</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-500">Đang cập nhật thông tin triển khai...</p>
                                        <?php endif; ?>
                                    </div>

                                    <div id="disbursement-<?php echo $project['id']; ?>" class="tab-content py-4">
                                        <h3 class="font-bold text-lg mb-2">Thông tin giải ngân</h3>
                                        <?php if (!empty($project['disbursement_info'])): ?>
                                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($project['disbursement_info'])); ?></p>
                                            
                                            <div class="mt-4 bg-green-50 p-4 rounded-lg">
                                                <h4 class="font-bold text-green-800 mb-2">Tổng số tiền đã giải ngân: <?php echo number_format($project['raised']); ?>đ</h4>
                                                <p class="text-green-700">Số tiền đã được sử dụng đúng mục đích và minh bạch.</p>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-500">Đang cập nhật thông tin giải ngân...</p>
                                        <?php endif; ?>
                                    </div>

                                    <div id="gallery-<?php echo $project['id']; ?>" class="tab-content py-4">
                                        <h3 class="font-bold text-lg mb-2">Hình ảnh dự án</h3>
                                        <?php if (!empty($success_images)): ?>
                                            <div class="gallery">
                                                <?php foreach ($success_images as $image): ?>
                                                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Hình ảnh dự án" onclick="openModal('<?php echo htmlspecialchars($image); ?>')">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-500">Chưa có hình ảnh nào được cập nhật.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-700 mb-2">Chưa có dự án nào hoàn thành</h3>
                    <p class="text-gray-500 mb-4">Hiện tại không có dự án nào đã hoàn thành để hiển thị.</p>
                    <a href="/home/du-an" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200 inline-block">
                        Xem các dự án đang gây quỹ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImage" class="modal-img" src="">
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'?>

    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            const tabButtons = document.getElementsByClassName("tab-button");
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("border-green-600", "text-green-600");
                tabButtons[i].classList.add("border-transparent");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("border-green-600", "text-green-600");
            evt.currentTarget.classList.remove("border-transparent");
        }

        // Modal functionality
        function openModal(src) {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            modal.style.display = "block";
            modalImg.src = src;
        }

        function closeModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Close modal when clicking outside of image
        window.onclick = function(event) {
            const modal = document.getElementById("imageModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Mobile Menu Toggle (from your original code)
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

        // Back to Top Button
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