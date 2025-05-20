<?php
session_start();
require_once __DIR__ . '/../../../../core/helpers.php';
require_once __DIR__ . '/../../../../core/DB.php';
require_once(__DIR__ . '/../../../../core/is_user.php');
CheckLogin();
CheckAdmin();

$db = new DB();
$conn = $db->connect();
$error_message = '';

// Lấy danh sách danh mục
$categories = $db->get_list("SELECT id, name FROM category");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = check_string($_POST['title']);
    $description = check_string($_POST['description']);
    $goal = (int)$_POST['goal'];
    $start_date = date('Y-m-d');
    $end_date = check_string($_POST['end_date']);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $user_id = $getUser['id'] ?? null;
    $address = check_string($_POST['address'] ?? '');

    if (!$user_id) {
        $error_message = "Không xác định được người tạo dự án.";
    } elseif ($category_id <= 0 || !$db->get_row("SELECT id FROM category WHERE id = '$category_id'")) {
        $error_message = "Danh mục không hợp lệ.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Lỗi upload ảnh: Không có file hoặc lỗi khi tải lên.";
    } else {
        $target_dir = "images/";
        $image = basename($_FILES['image']['name']);
        $target_file = $target_dir . $image;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $error_message = "Không thể lưu file ảnh vào máy chủ.";
        } else {
            $data = [
                'title'       => $title,
                'description' => $description,
                'image'       => $image,
                'goal'        => $goal,
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                'user_id'     => $user_id,
                'address'     => $address,
                'category_id' => $category_id
            ];

            $columns = implode(", ", array_keys($data));
            $values  = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_values($data))) . "'";
            $sql     = "INSERT INTO projects ($columns) VALUES ($values)";
            if ($conn->query($sql) === true) {
                header("Location: /admin/projects");
                exit();
            } else {
                $error_message = "Lỗi khi lưu dự án vào database: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm dự án mới | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .form-container { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 12px; }
        .input-field:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .btn-primary { background-color: #4f46e5; }
        .btn-primary:hover { background-color: #4338ca; }
        #image-preview { max-width: 200px; max-height: 200px; object-fit: cover; display: none; border-radius: 8px; }
        .file-upload { border: 2px dashed #d1d5db; transition: all 0.3s ease; }
        .file-upload:hover { border-color: #9ca3af; }
    </style>
</head>
<body class="bg-gray-50">
<header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-indigo-600 flex items-center">
            <i class="fas fa-hand-holding-heart mr-2"></i> ViecTot Admin
        </h1>
        <div class="flex items-center space-x-4">
            <a href="/admin/projects" class="text-gray-600 hover:text-indigo-600">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
            <div class="flex items-center">
                <span class="text-gray-700 mr-2"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="../logout.php" class="text-gray-500 hover:text-red-500">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-extrabold text-gray-900">Thêm dự án mới</h2>
        <p class="mt-2 text-lg text-gray-600">Điền đầy đủ thông tin dự án từ thiện của bạn</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <strong class="font-bold">Lỗi: </strong>
            <span class="block sm:inline"><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white form-container p-8">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề dự án</label>
                <input type="text" id="title" name="title" required class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Ví dụ: Xây cầu cho trẻ em vùng cao">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả chi tiết</label>
                <textarea id="description" name="description" rows="5" required class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Mô tả chi tiết về dự án, mục đích, đối tượng hưởng lợi..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hình ảnh dự án</label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-shrink-0">
                        <img id="image-preview" src="#" alt="Preview" class="w-32 h-32 object-cover rounded-lg border">
                    </div>
                    <div class="flex-1 file-upload mt-1 flex justify-center px-6 pt-5 pb-6 rounded-lg">
                        <div class="space-y-1 text-center">
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>Chọn hình ảnh</span>
                                    <input id="image" name="image" type="file" class="sr-only" accept="image/*" required>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, JPEG, tối đa 5MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="goal" class="block text-sm font-medium text-gray-700 mb-1">Mục tiêu quyên góp (VNĐ)</label>
                <input type="number" id="goal" name="goal" min="0" required class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Ví dụ: 50000000">
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ thực hiện</label>
                <input type="text" id="address" name="address" required class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Ví dụ: Xã ABC, Huyện XYZ, Tỉnh Hà Giang">
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                <input type="text" id="start_date" name="start_date" value="<?= date('Y-m-d') ?>" readonly class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                <input type="date" id="end_date" name="end_date" required class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                <select name="category_id" id="category_id" required class="input-field block w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full flex justify-center py-3 px-4 rounded-lg text-white font-semibold shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i> Thêm dự án
                </button>
            </div>
        </form>
    </div>
</main>

<footer class="bg-white border-t mt-12">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <p class="text-center text-gray-500 text-sm">© 2025 ViecTot. All rights reserved.</p>
    </div>
</footer>

<script>
document.getElementById('image').addEventListener('change', function(event) {
    const preview = document.getElementById('image-preview');
    const file = event.target.files[0];
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>
</body>
</html>