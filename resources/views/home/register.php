<?php
define("IN_SITE", true);
require_once __DIR__ . '/../../../core/DB.php';
require_once __DIR__ . '/../../../core/helpers.php';

if (session_status() == PHP_SESSION_NONE) session_start();

// Xử lý đăng ký khi có POST
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = xss($_POST['name'] ?? '');
    $email    = xss($_POST['email'] ?? '');
    $password = xss($_POST['password'] ?? '');

    if (!$name || !$email || !$password) {
        $message = ['type' => 'error', 'text' => 'Vui lòng nhập đầy đủ thông tin'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = ['type' => 'error', 'text' => 'Email không hợp lệ'];
    } elseif ($NNL->num_rows("SELECT * FROM users WHERE email = '$email'") > 0) {
        $message = ['type' => 'error', 'text' => 'Email đã tồn tại'];
    } else {
        $token = bin2hex(random_bytes(16));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        

        $insert = $NNL->insert("users", [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'token' => $token,
            'created_at' => time(),
            'updated_at' => time()
        ]);

        if ($insert) {
    setcookie("token", $token, time() + 3600, "/");
    $_SESSION['login'] = $token;
    header("Location: /home");
    exit;
}
 else {
            $message = ['type' => 'error', 'text' => 'Không thể tạo tài khoản, vui lòng thử lại'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Đăng ký</h2>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700">Họ và tên</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Mật khẩu</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded">
            </div>
            <button type="submit" class="w-full bg-pink-600 text-white py-2 rounded hover:bg-pink-700">
                Đăng ký
            </button>
        </form>
        <p class="text-sm text-center mt-4 text-gray-600">
            Đã có tài khoản? <a href="login.php" class="text-red-600 hover:underline">Đăng nhập</a>
        </p>
    </div>

<?php if ($message): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: '<?= $message['type'] ?>',
            title: '<?= $message['text'] ?>',
            showConfirmButton: false,
            timer: 2000
        });
    });
</script>
<?php endif; ?>
</body>
</html>
