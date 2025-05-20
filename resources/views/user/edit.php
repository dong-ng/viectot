<?php
// file: edit.php
if (!defined('IN_SITE')) define('IN_SITE', true);

require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

CheckLogin();
$db = new DB();
$user_id = $getUser['id'];

// Lấy id từ query string
$params = [];
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $params);
$id = isset($params['id']) ? (int)$params['id'] : 0;

if ($id <= 0) {
    die('ID không hợp lệ.');
}

// Load bản ghi pending của chính user này
$request = $db->get_row("
    SELECT * 
    FROM project_requests
    WHERE id = $id 
      AND user_id = $user_id 
      AND status = 'pending'
");
if (!$request) {
    die('Không tìm thấy request hoặc không được phép chỉnh sửa.');
}

// Danh sách danh mục cố định
$categories = [
    ['id' => 1, 'name' => 'Trẻ em'],
    ['id' => 2, 'name' => 'Cộng đồng'],
    ['id' => 3, 'name' => 'Giáo dục'],
    ['id' => 4, 'name' => 'Hoàn cảnh khó khăn'],
    ['id' => 5, 'name' => 'Người già leo đơn'],
    ['id' => 6, 'name' => 'Thiên tai']
];

// Xử lý form khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = check_string($_POST['title'] ?? '');
    $description = check_string($_POST['description'] ?? '');
    $goal = (int)($_POST['goal'] ?? 0);
    $end_date = check_string($_POST['end_date'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    // Validate end_date
    $current_date = date('Y-m-d');
    if (strtotime($end_date) < strtotime($current_date)) {
        echo "<script>alert('Ngày kết thúc không thể sớm hơn ngày hiện tại!'); window.location.href='edit.php?id=$id';</script>";
        exit();
    }

    // Validate category_id
    $valid_category_ids = array_column($categories, 'id');
    if (!in_array($category_id, $valid_category_ids)) {
        echo "<script>alert('Danh mục không hợp lệ!'); window.location.href='edit.php?id=$id';</script>";
        exit();
    }

    // Xử lý upload hình ảnh
    $image = $request['image'];
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "images/";
        $target_file = $target_dir . basename($image);

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $request['image']; // Giữ ảnh cũ nếu upload thất bại
        }
    }

    // Cập nhật bản ghi
    $stmt = $db->connect()->prepare("
        UPDATE project_requests
        SET title = ?,
            description = ?,
            image = ?,
            goal = ?,
            end_date = ?,
            category_id = ?,
            updated_at = UNIX_TIMESTAMP()
        WHERE id = ? 
          AND user_id = ? 
          AND status = 'pending'
    ");
    $stmt->bind_param('sssisiii', $title, $description, $image, $goal, $end_date, $category_id, $id, $user_id);
    $stmt->execute();

    // Chuyển hướng sau khi cập nhật
    header('Location: duancuatoi.php?success=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sửa chiến dịch</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
  <style>
    .file-upload {
      border: 2px dashed #d1d5db;
      transition: all 0.3s ease;
    }
    .file-upload:hover {
      border-color: #9ca3af;
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b">
          <h2 class="text-xl font-bold text-gray-800">Sửa chiến dịch</h2>
          <p class="text-gray-600 mt-1">Cập nhật thông tin chiến dịch của bạn</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-6">
          <div class="grid grid-cols-1 gap-6">
            <!-- Tiêu đề -->
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề dự án *</label>
              <input type="text" id="title" name="title" required
                     value="<?= htmlspecialchars($request['title']) ?>"
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                     placeholder="Nhập tiêu đề dự án">
            </div>

            <!-- Mô tả -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả dự án *</label>
              <textarea id="description" name="description" rows="4" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        placeholder="Mô tả chi tiết về dự án"><?= htmlspecialchars($request['description']) ?></textarea>
            </div>

            <!-- Hình ảnh -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Hình ảnh dự án</label>
              <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-shrink-0">
                  <img id="image-preview" src="/images/<?= htmlspecialchars($request['image']) ?>" 
                       alt="Preview" class="w-32 h-32 object-cover rounded-lg border">
                </div>
                <div class="flex-1 file-upload mt-1 flex justify-center px-6 pt-5 pb-6 rounded-lg">
                  <div class="space-y-1 text-center">
                    <div class="flex text-sm text-gray-600 justify-center">
                      <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                        <span>Chọn hình ảnh mới</span>
                        <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                      </label>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG, JPEG tối đa 5MB</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mục tiêu -->
            <div>
              <label for="goal" class="block text-sm font-medium text-gray-700 mb-1">Mục tiêu quyên góp (VNĐ) *</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₫</span>
                </div>
                <input type="number" id="goal" name="goal" min="0" required
                       value="<?= htmlspecialchars($request['goal']) ?>"
                       class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                       placeholder="Nhập số tiền mục tiêu">
              </div>
            </div>

            <!-- Ngày kết thúc -->
            <div>
              <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc *</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-calendar-alt text-gray-400"></i>
                </div>
                <input type="date" id="end_date" name="end_date" required
                       value="<?= htmlspecialchars($request['end_date']) ?>"
                       class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
              </div>
            </div>

            <!-- Danh mục -->
            <div>
              <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Danh mục *</label>
              <select id="category_id" name="category_id" required
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Chọn danh mục</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= $category['id'] ?>" <?= $request['category_id'] == $category['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mt-8 flex justify-end space-x-3">
            <a href="duancuatoi.php" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
              Hủy bỏ
            </a>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition flex items-center">
              <i class="fas fa-save mr-2"></i> Lưu thay đổi
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Xem trước hình ảnh khi chọn file
    document.getElementById('image').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('image-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
      }
    });

    // Kiểm tra ngày kết thúc không nhỏ hơn ngày hiện tại
    document.addEventListener('DOMContentLoaded', function () {
      const endDateInput = document.getElementById('end_date');
      const today = new Date();
      const minDate = today.toISOString().split('T')[0]; // Lấy ngày hiện tại dạng YYYY-MM-DD

      // Đặt giá trị tối thiểu cho ngày kết thúc
      endDateInput.setAttribute('min', minDate);

      // Kiểm tra khi người dùng thay đổi ngày
      endDateInput.addEventListener('change', function () {
        const selectedDate = new Date(this.value);
        if (selectedDate < today) {
          alert('Ngày kết thúc không thể sớm hơn ngày hiện tại!');
          this.value = ''; // Xóa giá trị không hợp lệ
        }
      });
    });
  </script>
</body>
</html>