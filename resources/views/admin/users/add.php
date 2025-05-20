<?php
session_start();
require_once __DIR__ . '/../../../../core/helpers.php';
require_once __DIR__ . '/../../../../core/DB.php';
require_once(__DIR__ . '/../../../../core/is_user.php');
CheckLogin();
CheckAdmin();

$db = new DB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = check_string($_POST['name']);
    $email = check_string($_POST['email']);
    $password = password_hash(check_string($_POST['password']), PASSWORD_BCRYPT);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $created_at = time();
    $token = md5(uniqid());

    // Kiểm tra email không trùng
    $existing_email = $db->get_row("SELECT id FROM users WHERE email = '$email'");
    if ($existing_email) {
        echo "<script>alert('Email đã tồn tại!'); window.location.href='/admin/users/add';</script>";
        exit();
    }

    $data = [
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'is_admin' => $is_admin,
        'created_at' => $created_at,
        'updated_at' => $created_at,
        'token' => $token,
        'sodu' => 0
    ];

    if ($db->insert("users", $data)) {
        header("Location: /admin/users");
        exit();
    } else {
        header("Location: /admin/users/add?error=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm người dùng mới | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .form-container {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 12px;
        }
        .input-field:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn-primary {
            background-color: #4f46e5;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #4338ca;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-indigo-600 flex items-center">
                <i class="fas fa-users mr-2"></i> ViecTot Admin
            </h1>
            <div class="flex items-center space-x-4">
                <a href="/admin/users" class="text-gray-600 hover:text-indigo-600 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="/home/logout.php" class="text-gray-500 hover:text-red-500 transition">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900">Thêm người dùng mới</h2>
            <p class="mt-2 text-lg text-gray-600">Điền đầy đủ thông tin người dùng</p>
        </div>

        <div class="bg-white form-container p-8">
            <form method="POST" class="space-y-6">
                <!-- Tên -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên người dùng</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="name" name="name" required
                            class="pl-10 input-field block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            placeholder="Nhập tên người dùng">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" required
                            class="pl-10 input-field block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            placeholder="Nhập địa chỉ email">
                    </div>
                </div>

                <!-- Mật khẩu -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="pl-10 input-field block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            placeholder="Nhập mật khẩu">
                    </div>
                </div>

                <!-- Vai trò -->
                <div>
                    <label for="is_admin" class="block text-sm font-medium text-gray-700 mb-1">Vai trò</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="is_admin" name="is_admin"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_admin" class="ml-2 text-sm text-gray-600">Quản trị viên</label>
                    </div>
                </div>

                <!-- Nút submit -->
                <div class="pt-2">
                    <button type="submit" class="btn-primary w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus-circle mr-2"></i> Thêm người dùng
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                © <?= date('Y') ?> ViecTot. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>