<?php
session_start();
require_once __DIR__ . '/../../../../core/helpers.php';
require_once __DIR__ . '/../../../../core/DB.php';
require_once(__DIR__ . '/../../../../core/is_user.php');
CheckLogin();
CheckAdmin();

// Lấy ID người dùng từ URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin người dùng hiện tại
$user = $NNL->get_row("SELECT * FROM users WHERE id = $user_id");

if (!$user) {
    header('Location: /admin/users');
    exit();
}

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = check_string($_POST['name']);
    $email = check_string($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $password = !empty($_POST['password']) ? password_hash(check_string($_POST['password']), PASSWORD_BCRYPT) : $user['password'];

    // Kiểm tra email không trùng
    $existing_email = $NNL->get_row("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if ($existing_email) {
        echo "<script>alert('Email đã tồn tại!'); window.location.href='/admin/users/edit?id=$user_id';</script>";
        exit();
    }

    $update = $NNL->update("users", [
        'name' => $name,
        'email' => $email,
        'is_admin' => $is_admin,
        'password' => $password
    ], " `id` = '$user_id' ");

    if ($update) {
        header("Location: /admin/users?success=1");
        exit();
    } else {
        header("Location: /admin/users?error=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa người dùng | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; }
        .sidebar { width: 280px; height: 100vh; position: fixed; background: linear-gradient(195deg, #1a237e, #283593); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .main-content { margin-left: 280px; width: calc(100% - 280px); min-height: 100vh; display: flex; flex-direction: column; }
        .header { backdrop-filter: blur(8px); background-color: rgba(255, 255, 255, 0.8); }
        @media (max-width: 1024px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; }
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
                            <i class="fas fa-user-edit mr-2 text-indigo-600"></i>
                            Chỉnh sửa người dùng
                        </h1>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-600 hidden md:inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            <!--<a href="/home/logout.php" class="p-2 rounded-full hover:bg-gray-100 text-gray-600 hover:text-red-500 transition">-->
                            <!--    <i class="fas fa-sign-out-alt"></i>-->
                            <!--</a>-->
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
                                <h2 class="text-xl font-bold text-gray-800">Thông tin người dùng</h2>
                                <p class="text-gray-600 mt-1">Cập nhật thông tin người dùng</p>
                            </div>

                            <form method="POST" class="p-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Tên -->
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên *</label>
                                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                               placeholder="Nhập tên người dùng">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                               placeholder="Nhập email">
                                    </div>

                                    <!-- Mật khẩu -->
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới (bỏ trống để giữ nguyên)</label>
                                        <input type="password" id="password" name="password"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                               placeholder="Nhập mật khẩu mới">
                                    </div>

                                    <!-- Vai trò -->
                                    <div>
                                        <label for="is_admin" class="block text-sm font-medium text-gray-700 mb-1">Vai trò</label>
                                        <input type="checkbox" id="is_admin" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="is_admin" class="ml-2 text-sm text-gray-600">Quản trị viên</label>
                                    </div>

                                    <!-- Số dư -->
                                    <div>
                                        <label for="sodu" class="block text-sm font-medium text-gray-700 mb-1">Số dư (VNĐ)</label>
                                        <input type="number" id="sodu" name="sodu" value="<?= htmlspecialchars($user['sodu']) ?>" readonly
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100">
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-end space-x-3">
                                    <a href="/admin/users" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
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
                <div class="container mx-auto px-6 text-center text-gray-500 text-sm">
                    © <?= date('Y') ?> ViecTot. All rights reserved.
                </div>
            </footer>
        </div>
    </div>
</body>
</html>