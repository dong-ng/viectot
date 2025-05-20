<?php
session_start();
// if (!isset($_SESSION['login'])) {
//     header('Location: ../login');
//     exit();
// }
require_once __DIR__ . '/../../../../core/helpers.php';
require_once __DIR__ . '/../../../../core/DB.php';
require_once(__DIR__ . '/../../../../core/is_user.php');
CheckLogin();
CheckAdmin();

// Lấy ID dự án từ URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin dự án hiện tại
$project = $NNL->get_row("SELECT * FROM projects WHERE id = $project_id");

if (!$project) {
    header('Location: /admin/projects');
    exit();
}

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = check_string($_POST['title']);
    $description = check_string($_POST['description']);
    $goal = (int)$_POST['goal'];
    $end_date = check_string($_POST['end_date']);
    
    // Validate end_date is not before current date
    $current_date = date('Y-m-d');
    if (strtotime($end_date) < strtotime($current_date)) {
        echo "<script>alert('Ngày kết thúc không thể sớm hơn ngày hiện tại!'); window.location.href='/admin/projects/edit?id=$project_id';</script>";
        exit();
    }

    // Xử lý upload hình ảnh mới (nếu có)
    $image = $project['image']; // Giữ nguyên ảnh cũ nếu không upload mới
    
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "images/";
        $target_file = $target_dir . basename($image);
        
        // Di chuyển file upload vào thư mục đích
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $project['image']; // Giữ nguyên ảnh cũ nếu upload thất bại
        }
    }
    
    $update = $NNL->update("projects", [
        'title' => $title,
        'description' => $description,
        'image' => $image,
        'goal' => $goal,
        'end_date' => $end_date
    ], " `id` = '".$project_id."' ");
    
    if ($update) {
        header("Location: /admin/projects?success=1");
        exit();
    } else {
        header("Location: /admin/projects?error=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chỉnh sửa dự án | Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --primary: #4f46e5;
      --primary-hover: #4338ca;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f9fafb;
    }
    .sidebar {
      width: 280px;
      height: 100vh;
      position: fixed;
      background: linear-gradient(195deg, #1a237e, #283593);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .main-content {
      margin-left: 280px;
      width: calc(100% - 280px);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .header {
      backdrop-filter: blur(8px);
      background-color: rgba(255, 255, 255, 0.8);
    }
    .file-upload {
      border: 2px dashed #d1d5db;
      transition: all 0.3s ease;
    }
    .file-upload:hover {
      border-color: #9ca3af;
    }
    @media (max-width: 1024px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }
      .main-content {
        margin-left: 0;
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="flex flex-col lg:flex-row">
    <!-- SIDEBAR -->
    <div class="sidebar text-white">
      <?php require_once __DIR__ . '/../sidebar.php'; ?>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <!-- HEADER -->
      <header class="header sticky top-0 z-10 border-b">
        <div class="container mx-auto px-6 py-3">
          <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800 flex items-center">
              <i class="fas fa-edit mr-2 text-indigo-600"></i>
              Chỉnh sửa dự án
            </h1>
            <div class="flex items-center space-x-4">
              <div class="hidden md:flex items-center space-x-2">
                <span class="text-gray-600"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                  <i class="fas fa-user text-indigo-600"></i>
                </div>
              </div>
              <a href="../logout.php" class="p-2 rounded-full hover:bg-gray-100 text-gray-600 hover:text-red-500 transition">
                <i class="fas fa-sign-out-alt"></i>
              </a>
            </div>
          </div>
        </div>
      </header>

      <!-- CONTENT -->
      <main class="flex-1 bg-gray-50">
        <div class="container mx-auto px-6 py-8">
          <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
              <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">Thông tin dự án</h2>
                <p class="text-gray-600 mt-1">Cập nhật thông tin dự án từ thiện</p>
              </div>
              
              <form method="POST" enctype="multipart/form-data" class="p-6">
                <div class="grid grid-cols-1 gap-6">
                  <!-- Tiêu đề -->
                  <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề dự án *</label>
                    <input type="text" id="title" name="title" required
                           value="<?= htmlspecialchars($project['title']) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                           placeholder="Nhập tiêu đề dự án">
                  </div>
                  
                  <!-- Mô tả -->
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả dự án *</label>
                    <textarea id="description" name="description" rows="4" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                              placeholder="Mô tả chi tiết về dự án"><?= htmlspecialchars($project['description']) ?></textarea>
                  </div>
                  
                  <!-- Hình ảnh -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hình ảnh dự án</label>
                    <div class="flex flex-col sm:flex-row gap-4">
                      <div class="flex-shrink-0">
                        <img id="image-preview" src="/images/<?= htmlspecialchars($project['image']) ?>" 
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
                             value="<?= htmlspecialchars($project['goal']) ?>"
                             class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                             placeholder="Nhập số tiền mục tiêu">
                    </div>
                  </div>

                  <!-- Ngày bắt đầu (read-only) -->
                  <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                    <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar-alt text-gray-400"></i>
                      </div>
                      <input type="text" id="start_date" name="start_date" value="<?= htmlspecialchars($project['start_date']) ?>" readonly
                             class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100">
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
                             value="<?= htmlspecialchars($project['end_date']) ?>"
                             class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                  </div>
                </div>
                
                <div class="mt-8 flex justify-end space-x-3">
                  <a href="/admin/projects" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
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
      </main>

      <!-- FOOTER -->
      <footer class="bg-white border-t py-4">
        <div class="container mx-auto px-6">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <p class="text-grayirer-500 text-sm">© 2025 ViecTot. All rights reserved.</p>
          </div>
        </div>
      </footer>
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

        // Đặt giá trị tối thiểu cho ngày kết thúc là ngày hiện tại
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