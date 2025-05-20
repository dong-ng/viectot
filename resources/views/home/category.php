<?php
session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

global $NNL;

// Lấy ID danh mục từ URL
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin danh mục
$category = $NNL->get_row("SELECT * FROM category WHERE id = $categoryId");

// Lấy danh sách dự án thuộc danh mục đó, kèm số tiền đã ủng hộ từ bảng orders
$projects = $NNL->get_list("SELECT 
    p.*, 
    IFNULL(SUM(o.amount), 0) AS raised 
FROM projects p 
LEFT JOIN orders o ON p.id = o.project_id AND o.status = 'completed' 
WHERE p.category_id = $categoryId 
GROUP BY p.id 
ORDER BY p.id DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dự án - <?= $category ? htmlspecialchars($category['name']) : 'Không tìm thấy' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'nav.php'; ?>

<div class="container mx-auto px-4 py-6">
    <?php if ($category): ?>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Dự Án Về <?= htmlspecialchars($category['name']) ?></h1>

        <?php if (!empty($projects)): ?>
            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($projects as $p): ?>
                    <?php
                        $goal = (float)$p['goal'];
                        $raised = (float)$p['raised'];
                        $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100)) : 0;
                        $remaining = max(0, $goal - $raised);
                    ?>
                    <div class="bg-white rounded-lg shadow-md p-4 campaign-card">
                        <img src="/images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-40 object-cover rounded mb-3">
                        <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($p['title']) ?></h2>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($project['description']); ?></p>

                        <div class="mb-2">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Tiến độ</span>
                                <span class="text-gray-600"><?= $progress ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-value" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500 mt-2">
                            <div><strong><?= number_format($raised) ?>đ</strong> / <?= number_format($goal) ?>đ</div>
                            <div>Còn lại: <?= number_format($remaining) ?>đ</div>
                        </div>

                        <a href="/home/chitiet/<?php echo $p['id']; ?>" class="mt-4 block w-full py-2 bg-green-600 hover:bg-green-700 text-white text-center rounded-md font-medium transition duration-200">
                            Ủng hộ ngay
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Chưa có dự án nào trong danh mục này.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-red-500">Danh mục không tồn tại.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>