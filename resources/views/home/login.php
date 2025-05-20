<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
<div class="bg-white p-8 rounded-lg shadow-lg w-96">
    <h2 class="text-2xl font-bold mb-4 text-center">Đăng nhập</h2>

    <form id="loginForm">
        <div class="mb-4">
            <label class="block text-gray-700">Email</label>
            <input id="email" type="email" name="email" class="w-full px-4 py-2 border rounded-lg" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Mật khẩu</label>
            <input id="password" type="password" name="password" class="w-full px-4 py-2 border rounded-lg" required>
        </div>
        <button id="btnLogin" type="submit" class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Đăng nhập</button>
    </form>
    <p class="text-sm text-gray-600 text-center mt-4">Chưa có tài khoản? <a href="register.php" class="text-pink-600 hover:underline">Đăng ký</a></p>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btnLogin = document.getElementById('btnLogin');
    btnLogin.innerHTML = 'Đang xử lý...';
    btnLogin.disabled = true;

    fetch('/ajaxs/client/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
        })
    })
    .then(response => response.json())
    .then(data => {
        Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.msg,
            timer: 2000,
            showConfirmButton: false
        });
        if (data.status === 'success') setTimeout(() => window.location.href = '/', 1000);
        btnLogin.innerHTML = 'Đăng nhập';
        btnLogin.disabled = false;
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi khi gửi dữ liệu!',
            timer: 2000,
            showConfirmButton: false
        });
        btnLogin.innerHTML = 'Đăng nhập';
        btnLogin.disabled = false;
    });
});
</script>
</body>
</html>