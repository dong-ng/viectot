<?php
session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';
// CheckLogin();
global $NNL;

// Lấy số điện thoại từ query string
$phone = isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : '';
if (empty($phone)) {
    die('Vui lòng cung cấp số điện thoại để kiểm tra đơn hàng!');
}

$order = $NNL->get_row("SELECT * FROM orders WHERE phone = '$phone' ORDER BY created_at DESC LIMIT 1");
if (!$order) {
    die('Không tìm thấy đơn hàng nào với số điện thoại này!');
}

// Nếu đơn đã được xác nhận thì chuyển về /home
if ($order['status'] === 'completed' || $order['status'] === 'failed') {
    header("Location: /home");
    exit();
}

// Lấy thông tin dự án
$project_id = (int)$order['project_id'];
$project = $NNL->get_row("SELECT * FROM projects WHERE id = $project_id");
if (!$project) {
    die('Dự án không tồn tại hoặc đã bị xóa.');
}

// Thông tin ngân hàng
$bankInfo = [
    'name' => 'Ngo Anh Dong',
    'bank' => 'ACB',
    'account' => '16231611',
    'branch' => 'Chi nhánh HN',
    'bankid' => "970416",
    'keyword' => "ungho"
];
// Nếu người dùng bấm hủy đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = (int)$order['id'];
    $NNL->update("orders", [
        'status' => 'failed'
    ], " `id` = '".$order_id."' ");
    header("Location: /home");
    exit();
}

// Tính thời gian còn lại (30 phút = 1800 giây)
$time_remaining = 1800 - (time() - $order['created_at']);
if ($time_remaining <= 0) {
    $NNL->update("orders", [
        'status' => 'failed',
        'updated_at' => time()
    ], " `id` = '" . $order['id'] . "' ");
    header("Location: /home");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán - <?php echo htmlspecialchars($project['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .payment-container {
            max-width: 900px;
            margin: 2rem auto;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .info-card {
            border-left: 4px solid #10b981;
            background: linear-gradient(to right, #f0fdf4, #ffffff);
        }
        . HIVệT QR-container {
            display: flex;
            justify-content: center;
            margin: 1.5rem 0;
        }
        .qr-code {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .highlight {
            color: #065f46;
            font-weight: 600;
        }
        .section-title {
            position: relative;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .section-title:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: #10b981;
            border-radius: 3px;
        }
        .upload-area {
            border: 2px dashed #cbd5e1;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #b91c1c;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="payment-container bg-white rounded-xl overflow-hidden">
            <!-- Header -->
            <div class="header py-5 px-6 text-center">
                <h1 class="text-2xl font-bold">XÁC NHẬN THANH TOÁN</h1>
                <div id="countdown" class="countdown mt-4"></div>
            </div>
            
            <div class="p-6 md:p-8">
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Thông tin đơn hàng -->
                    <div class="md:w-1/2">
                        <h3 class="section-title text-xl font-semibold text-center">THÔNG TIN ĐƠN HÀNG</h3>
                        
                        <div class="space-y-4 text-gray-700">
                            <div class="flex justify-between">
                                <span class="font-medium">Dự án:</span>
                                <span><?php echo htmlspecialchars($project['title']); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium">Họ tên:</span>
                                <span><?php echo $order['anonymous'] ? 'Ẩn danh' : htmlspecialchars($order['name']); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium">Số điện thoại:</span>
                                <span><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium">Số tiền:</span>
                                <span class="highlight text-lg"><?php echo number_format($order['amount']); ?> VNĐ</span>
                            </div>
                            
                            <?php if (!empty($order['message'])): ?>
                            <div class="pt-2">
                                <p class="font-medium">Lời nhắn:</p>
                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($order['message']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Thông tin chuyển khoản -->
                    <div class="md:w-1/2">
                        <h3 class="section-title text-xl font-semibold text-center">THÔNG TIN CHUYỂN KHOẢN</h3>
                        
                        <div class="info-card p-5 rounded-lg mb-6">
                            <div class="text-center mb-4">
                                <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo $bankInfo['bank']; ?>
                                </span>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="font-medium">Chủ tài khoản:</span>
                                    <span><?php echo $bankInfo['name']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Số tài khoản:</span>
                                    <span class="font-mono"><?php echo $bankInfo['account']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Chi nhánh:</span>
                                    <span><?php echo $bankInfo['branch']; ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-amber-50 rounded border border-amber-200">
                                <p class="text-sm text-center font-medium text-amber-800">
                                    Nội dung chuyển khoản: <span class="font-bold"><?php echo $bankInfo['keyword'].$order['phone']; ?></span>
                                </p>
                                <p class="text-xs text-center text-amber-600 mt-1">(Bắt buộc để xác nhận giao dịch)</p>
                            </div>
                        </div>
                        
                        <!-- QR Code - Căn giữa đẹp mắt -->
                        <div class="qr-container">
                            <div class="qr-code">
                                <img src="https://api.vietqr.io/image/<?=$bankInfo['bankid'] ?>-<?=$bankInfo['account']?>-vhokej8.jpg?accountName=<?=$bankInfo['name']?>&amount=<?php echo $order['amount']; ?>&addInfo=<?= $bankInfo['keyword'].$order['phone']?>"
                                     alt="QR Code"
                                     class="mx-auto">
                                <p class="text-sm text-gray-600 mt-3">Quét mã QR để chuyển khoản</p>
                                <p class="text-xs text-gray-500 mt-1">Hoặc chuyển khoản thủ công theo thông tin trên</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đơn hàng này không?');" class="text-center mt-4" >
                    <input type="hidden" name="cancel_order" value="1">
                    <button type="submit" 
                        class="text-red-600 hover:text-red-800 hover:underline transition inline-flex items-center">
                        <i class="fas fa-times-circle mr-2"></i> Huỷ đơn hàng & quay lại trang chủ
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Đếm ngược 30 phút
        let timeLeft = <?php echo $time_remaining; ?>;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            if (timeLeft <= 0) {
                window.location.href = '/home';
                return;
            }

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `Thời gian còn lại: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            timeLeft--;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Hiển thị tên file khi chọn
        const fileInput = document.getElementById('proof');
        const uploadContent = document.getElementById('upload-content');
        const uploadArea = fileInput.closest('.upload-area');

        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                uploadContent.innerHTML = `
                    <i class="fas fa-file-alt text-3xl text-green-500 mb-2"></i>
                    <p class="text-sm font-medium text-gray-700 truncate px-2">${file.name}</p>
                    <p class="text-xs text-gray-500 mt w-full; height: 150px;" class="rounded-lg mb-4"></p>
                    <p class="text-sm text-gray-600 mt-3">Quét mã QR để chuyển khoản</p>
                    <p class="text-xs text-gray-500 mt-1">Hoặc chuyển khoản thủ công theo thông tin trên</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    // Đếm ngược 30 phút
    let timeLeft = <?php echo $time_remaining; ?>;
    const countdownElement = document.getElementById('countdown');

    function updateCountdown() {
        if (timeLeft <= 0) {
            window.location.href = '/home';
            return;
        }

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownElement.textContent = `Thời gian còn lại: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        timeLeft--;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);

    // Hiển thị tên file khi chọn
    const fileInput = document.getElementById('proof');
    const uploadContent = document.getElementById('upload-content');
    const uploadArea = fileInput.closest('.upload-area');

    fileInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            const file = this.files[0];
            uploadContent.innerHTML = `
                <i class="fas fa-file-alt text-3xl text-green-500 mb-2"></i>
                <p class="text-sm font-medium text-gray-700 truncate px-2">${file.name}</p>
                <p class="text-xs text-gray-500 mt-1">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                <p class="text-xs text-blue-500 mt-2">Click để chọn file khác</p>
            `;
            uploadArea.classList.add('border-green-500', 'bg-green-50');
        }
    });

    // Drag and drop effect
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('border-green-500', 'bg-green-50');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('border-green-500', 'bg-green-50');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileInput.files = e.dataTransfer.files;
        const event = new Event('change');
        fileInput.dispatchEvent(event);
    });
</script>
<script>
setInterval(function() {
    $.ajax({
        url: '/donate/donate.php',
        type: 'POST',
        dataType: 'json',
        
        success: function(result) {
            if (result && result.status == 2) {
                Swal.fire('Thành công',
                    `${result.msg}`,
                    'success').then(() => {
                    window.location.href = "/";
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
        }
    });
}, 5000); // 30 giây
</script>
</body>
</html>