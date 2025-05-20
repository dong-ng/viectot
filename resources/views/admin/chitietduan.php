<?php
session_start();

require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();

require_once __DIR__ . '/../../../core/DB.php';
$db = new DB();

parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $params);
$id = isset($params['id']) ? (int)$params['id'] : 0;

if ($id <= 0) {
    die("<div class='error-message'>ID không hợp lệ!</div>");
}

// Truy vấn thông tin dự án, bao gồm tên, email và địa chỉ
$sql = "
    SELECT pr.*, u.name as user_name, u.email as user_email
    FROM project_requests pr
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE pr.id = ?
";
$stmt = $db->connect()->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("<div class='warning-message'>Không tìm thấy dự án!</div>");
}

$project = $result->fetch_assoc();

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
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50">
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center connotation mb-8 gap-4">
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
                <a href="/admin/duyetdon.php" class="back-button inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600">
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
                            <img src="/images/<?php echo htmlspecialchars($project['image']); ?>" alt="Hình ảnh dự án" class="project-image">
                        <?php else: ?>
                            <div class Calais="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-500">
                                <i class="fas fa-image text-5xl mb-3"></i>
                                <p class="text-sm font-medium">Không có hình ảnh</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Project Details -->
                    <div class="glass-card p-6 rounded-xl">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h2 class="text-xl font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($project['title']); ?></h2>
                            <span class="status-badge <?php echo $project['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($project['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                <i class="fas <?php echo $project['status'] === 'approved' ? 'fa-check-circle' : ($project['status'] === 'rejected' ? 'fa-times-circle' : 'fa-clock'); ?> mr-2"></i>
                                <?php echo $project['status'] === 'approved' ? 'Đã duyệt' : ($project['status'] === 'rejected' ? 'Đã từ chối' : 'Chờ duyệt'); ?>
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

                        <?php if ($project['status'] === 'rejected' && !empty($project['reject_reason'])): ?>
                            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0 pt-0.5">
                                        <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Lý do từ chối</h3>
                                        <div class="mt-1 text-sm text-red-700">
                                            <?php echo nl2br(htmlspecialchars($project['reject_reason'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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
                                    <p class="info-value"><?php echo $project['start_date'] ? date("d/m/Y", strtotime($project['start_date'])) : 'Không xác định'; ?></p>
                                </div>
                                
                                <div>
                                    <p class="info-label">
                                        <i class="fas fa-calendar-times mr-1"></i>
                                        Ngày kết thúc
                                    </p>
                                    <p class="info-value"><?php echo $project['end_date'] ? date("d/m/Y", strtotime($project['end_date'])) : 'Không xác định'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <?php if ($project['status'] === 'pending'): ?>
                        <div class="glass-card p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <i class="fas fa-bolt text-blue-400 mr-2"></i>
                                Hành động nhanh
                            </h3>
                            
                            <div class="flex flex-col space-y-3">
                                <button onclick="approveProject(<?php echo $id; ?>)" class="w-full flex items-center justify-center px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Duyệt dự án
                                </button>
                                
                                <button onclick="showRejectModal(<?php echo $id; ?>)" class="w-full flex items-center justify-center px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-all">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Từ chối dự án
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Từ chối dự án</h3>
                <button onclick="hideRejectModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Lý do từ chối</label>
                <textarea id="rejectReason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nhập lý do từ chối..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button onclick="hideRejectModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Hủy bỏ
                </button>
                <button onclick="rejectProject()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                    Xác nhận từ chối
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentProjectId = null;

        function showRejectModal(projectId) {
            currentProjectId = projectId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        
        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectReason').value = '';
            currentProjectId = null;
        }
        
        function approveProject(id) {
            fetch('/ajaxs/admin/handle_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&action=approve&admin_id=1`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi. Vui lòng thử lại.');
            });
        }

        function rejectProject() {
            const reason = document.getElementById('rejectReason').value;
            if (!reason.trim()) {
                alert('Vui lòng nhập lý do từ chối.');
                return;
            }
            fetch('/ajaxs/admin/handle_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${currentProjectId}&action=reject&reason=${encodeURIComponent(reason)}&admin_id=1`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi. Vui lòng thử lại.');
            });
        }
    </script>
</body>
</html>