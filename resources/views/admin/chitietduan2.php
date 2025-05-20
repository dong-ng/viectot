<?php
session_start();

// Debug: Kiểm tra toàn bộ mảng $_GET
error_log("Debug GET: " . print_r($_GET, true));

// Kiểm tra đăng nhập và quyền quản trị
require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();

// Kết nối cơ sở dữ liệu
require_once __DIR__ . '/../../../core/DB.php';
$db = new DB();

// Lấy id từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Debug: Ghi log giá trị $_GET['id']
error_log("Debug ID nhận được: " . $id);

// Kiểm tra ID
if ($id <= 0) {
    error_log("ID không hợp lệ: " . (isset($_GET['id']) ? $_GET['id'] : 'Không có ID'));
    die("<div class='error-message'>ID không hợp lệ! Giá trị nhận được: " . (isset($_GET['id']) ? htmlspecialchars($_GET['id']) : 'Không có ID') . "</div>");
}

// Truy vấn thông tin dự án từ bảng projects
$sql = "
    SELECT p.*, u.name as user_name, u.email as user_email
    FROM projects p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
";
$stmt = $db->connect()->prepare($sql);
if (!$stmt) {
    error_log("Lỗi chuẩn bị truy vấn SQL: " . $db->connect()->error);
    die("<div class='error-message'>Lỗi truy vấn cơ sở dữ liệu!</div>");
}
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    error_log("Không tìm thấy dự án với ID: " . $id);
    die("<div class='warning-message'>Không tìm thấy dự án!</div>");
}

$project = $result->fetch_assoc();

// Tính toán progress
$project['progress'] = $project['goal'] > 0 ? round(($project['raised'] / $project['goal']) * 100, 2) : 0;

// Map danh mục ID sang tên
$categories = [
    1 => 'Trẻ Em',
    2 => 'Cộng Đồng',
    3 => 'Giáo dục',
    4 => 'Hoàn cảnh khó khăn',
    5 => 'Người già neo đơn',
    6 => 'Thiên tai',
];
$categoryName = isset($categories[$project['category_id']]) ? $categories[$project['category_id']] : 'Không xác định';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết dự án #<?php echo $id; ?> | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .project-image-container {
            height: 350px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .project-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            transition: transform 0.5s ease;
        }
        
        .project-image-container:hover .project-image {
            transform: scale(1.05);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(0,0,0,0.1), transparent);
            margin: 1.5rem 0;
        }
        
        .info-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 0.95rem;
            font-weight: 500;
            color: #1e293b;
        }
        
        .back-button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .back-button:hover {
            transform: translateX(-4px);
        }
        
        .error-message, .warning-message {
            padding: 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-align: center;
            max-width: 500px;
            margin: 2rem auto;
        }
        
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
        }
        
        .warning-message {
            background-color: #fef3c7;
            color: #b45309;
            border-left: 4px solid #d97706;
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e2e8f0;
        }
        .progress-value {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #818cf8);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50">
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <div class="flex items-center mb-2">
                        <div class="p-3 rounded-xl bg-white shadow-sm mr-4">
                            <i class="fas fa-project-diagram text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Chi tiết dự án #<?php echo $id; ?></h1>
                            <p class="text-gray-500 mt-1">Xem và quản lý thông tin dự án</p>
                        </div>
                    </div>
                </div>
                <a href="/admin/projects.php" class="back-button inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
                </a>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Project Image -->
                    <div class="project-image-container glass-card">
                        <?php if (!empty($project['image'])): ?>
                            <img src="<?php echo strpos($project['image'], '/images/') === 0 ? htmlspecialchars($project['image']) : '/images/' . htmlspecialchars($project['image']); ?>" alt="Hình ảnh dự án" class="project-image">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-500">
                                <i class="fas fa-image text-5xl mb-3"></i>
                                <p class="text-sm font-medium">Không có hình ảnh</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Project Details -->
                    <div class="glass-card p-6 rounded-xl">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h2 class="text-xl font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($project['title']); ?></h2>
                            <span class="status-badge <?php echo $project['progress'] >= 100 ? 'bg-green-100 text-green-800' : (strtotime($project['end_date']) < time() ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); ?>">
                                <i class="fas <?php echo $project['progress'] >= 100 ? 'fa-check-circle' : (strtotime($project['end_date']) < time() ? 'fa-exclamation-circle' : 'fa-spinner'); ?> mr-2"></i>
                                <?php echo $project['progress'] >= 100 ? 'Hoàn thành' : (strtotime($project['end_date']) < time() ? 'Quá hạn' : 'Đang triển khai'); ?>
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-align-left text-blue-400 mr-2"></i>
                                Mô tả dự án
                            </h3>
                            <div class="text-gray-600 whitespace-pre-line bg-gray-50 p-5 rounded-lg border border-gray-100">
                                <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-8">
                    <!-- Project Info -->
                    <div class="glass-card p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                            Thông tin dự án
                        </h3>
                        
                        <div class="space-y-5">
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-user mr-1"></i>
                                    Người tạo
                                </p>
                                <p class="info-value"><?php echo htmlspecialchars($project['user_name']) ?: 'Không xác định'; ?></p>
                                <p class="text-xs text-gray-400 mt-1">ID: <?php echo $project['user_id']; ?></p>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-envelope mr-1"></i>
                                    Email
                                </p>
                                <p class="info-value"><?php echo htmlspecialchars($project['user_email']) ?: 'Không xác định'; ?></p>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    Địa chỉ nhận ủng hộ
                                </p>
                                <p class="info-value"><?php echo htmlspecialchars($project['address']) ?: 'Không xác định'; ?></p>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-bullseye mr-1"></i>
                                    Mục tiêu
                                </p>
                                <p class="info-value font-semibold text-blue-600"><?php echo number_format($project['goal'], 0, ',', '.') . ' VNĐ'; ?></p>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-money-bill-wave mr-1"></i>
                                    Đã quyên góp
                                </p>
                                <p class="info-value font-semibold text-green-600"><?php echo number_format($project['raised'], 0, ',', '.') . ' VNĐ'; ?></p>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Tiến độ
                                </p>
                                <p class="info-value"><?php echo $project['progress']; ?>%</p>
                                <div class="progress-bar mt-2">
                                    <div class="progress-value" style="width: <?php echo $project['progress']; ?>%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-hand-holding-usd mr-1"></i>
                                    Đã giải ngân
                                </p>
                                <p class="info-value"><?php echo number_format($project['disbursed_amount'], 0, ',', '.') . ' VNĐ'; ?></p>
                            </div>
                            
                            <div>
                                <p class="info-label">
                                    <i class="fas fa-tag mr-1"></i>
                                    Danh mục
                                </p>
                                <p class="info-value"><?php echo htmlspecialchars($categoryName); ?></p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="info-label">
                                        <i class="fas fa-calendar-day mr-1"></i>
                                        Ngày tạo
                                    </p>
                                    <p class="info-value"><?php echo !empty($project['start_date']) && $project['start_date'] !== '0000-00-00' ? date("d/m/Y", strtotime($project['start_date'])) : 'Không xác định'; ?></p>
                                </div>
                                
                                <div>
                                    <p class="info-label">
                                        <i class="fas fa-calendar-times mr-1"></i>
                                        Ngày kết thúc
                                    </p>
                                    <p class="info-value"><?php echo !empty($project['end_date']) ? date("d/m/Y", strtotime($project['end_date'])) : 'Không xác định'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <?php if ($project['progress'] >= 100): ?>
                        <div class="glass-card p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <i class="fas fa-bolt text-blue-400 mr-2"></i>
                                Hành động giải ngân
                            </h3>
                            <a href="/admin/projects/disburse.php?id=<?php echo $id; ?>" class="w-full flex items-center justify-center px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all">
                                <i class="fas fa-hand-holding-usd mr-2"></i>
                                Giải ngân
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="glass-card p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-bolt text-blue-400 mr-2"></i>
                            Hành động quản lý
                        </h3>
                        <div class="flex flex-col space-y-3">
                            <a href="/admin/projects/edit/<?php echo $id; ?>" class="w-full flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all">
                                <i class="fas fa-edit mr-2"></i>
                                Chỉnh sửa
                            </a>
                            <a href="/admin/projects/delete/<?php echo $id; ?>" class="w-full flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all" onclick="return confirm('Bạn chắc chắn muốn xóa dự án này?')">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Xóa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>