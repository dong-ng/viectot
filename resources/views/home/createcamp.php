<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

$db = new DB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $getUser = $db->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_SESSION['login']) . "'");
    if (!$getUser) {
        die('Không xác định được người dùng. Vui lòng đăng nhập lại.');
    }

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $goal = $_POST['goal'] ?? 0;
    $category = $_POST['category'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $address = $_POST['address'] ?? '';

    $goal = (int)str_replace(['.', ','], '', $goal);

    if (empty($title) || empty($description) || empty($goal) || empty($category) || empty($end_date) || empty($address)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif ($goal <= 0) {
        $error = 'Mục tiêu gây quỹ phải lớn hơn 0';
    } elseif (strtotime($end_date) < strtotime(date('Y-m-d'))) {
        $error = 'Ngày kết thúc phải ở trong tương lai';
    } else {
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!is_writable($uploadDir)) {
                $error = "Thư mục không có quyền ghi: $uploadDir";
            } else {
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $uploadFile = $uploadDir . $fileName;

                $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($imageFileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                        $image = $fileName;
                    } else {
                        $error = 'Có lỗi khi upload ảnh';
                    }
                } else {
                    $error = 'Chỉ chấp nhận file ảnh JPG, JPEG, PNG & GIF';
                }
            }
        } else {
            $error = 'Vui lòng chọn ảnh cho chiến dịch';
        }

        if (empty($error)) {
            $start_date = date('Y-m-d');

            $insert = $db->insert('project_requests', [
                'user_id' => $getUser['id'],
                'title' => $title,
                'description' => $description,
                'image' => $image,
                'goal' => $goal,
                'address' => $address,
                'category_id' => (int)$category,
                'end_date' => $end_date,
                'start_date' => $start_date,
                'status' => 'pending'
            ]);

            if (!$insert) {
                echo "<pre style='color:red'>";
                echo "❌ Lỗi SQL: " . mysqli_error($db->connect()) . "\n";
                echo "Query lỗi có thể nằm ở dữ liệu:\n";
                print_r([
                    'user_id' => $getUser['id'],
                    'title' => $title,
                    'description' => $description,
                    'image' => $image,
                    'goal' => $goal,
                    'address' => $address,
                    'category_id' => (int)$category,
                    'end_date' => $end_date,
                    'start_date' => $start_date,
                    'status' => 'pending'
                ]);
                echo "</pre>";
            }

            if ($insert) {
                $success = 'Tạo chiến dịch thành công! Chiến dịch của bạn đang chờ phê duyệt.';
                $_POST = [];

                $adminEmail = 'admin@example.com';
                $subject = 'Chiến dịch mới cần duyệt';
                $user_id = $getUser['id'];

                $message = "Một chiến dịch mới vừa được gửi bởi người dùng ID $user_id\nTiêu đề: $title\nMục tiêu: $goal VND\nNgày bắt đầu: $start_date\nNgày kết thúc: $end_date\nĐịa chỉ: $address";
                $headers = "From: no-reply@yourdomain.com";

                @mail($adminEmail, $subject, $message, $headers);
            } else {
                $error = 'Có lỗi xảy ra khi gửi chiến dịch. Vui lòng thử lại.';
            }
        }
    }
}

$categories = $db->get_list("SELECT * FROM category ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo chiến dịch mới - Viectot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .mobile-menu.active {
            max-height: 500px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'nav.php'?>

    <!-- Main Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-green-600 text-white py-4 px-6">
                        <h1 class="text-2xl font-bold">Tạo chiến dịch mới</h1>
                        <p class="text-green-100">Hãy chia sẻ câu chuyện của bạn và bắt đầu gây quỹ</p>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                                <span class="block sm:inline"><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                                <span class="block sm:inline"><?php echo $success; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- Tên người gửi -->
                            <div class="mb-6">
                                <label for="sender_name" class="block text-gray-700 font-medium mb-2">Tên người gửi *</label>
                                <input type="text" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($getUser['name'] ?? ''); ?>" 
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed" 
                                    readonly>
                                <p class="text-gray-500 text-sm mt-1">Tên được lấy từ thông tin tài khoản của bạn</p>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-6">
                                <label for="email" class="block text-gray-700 font-medium mb-2">Email *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($getUser['email'] ?? ''); ?>" 
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed" 
                                    readonly>
                                <p class="text-gray-500 text-sm mt-1">Email được lấy từ thông tin tài khoản của bạn</p>
                            </div>
                            
                            <!-- Địa chỉ người nhận ủng hộ -->
                            <div class="mb-6">
                                <label for="address" class="block text-gray-700 font-medium mb-2">Địa chỉ người nhận ủng hộ *</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                    placeholder="Ví dụ: 123 Đường Láng, Đống Đa, Hà Nội" required>
                                <p class="text-gray-500 text-sm mt-1">Nhập địa chỉ cụ thể để nhận hỗ trợ</p>
                            </div>
                            
                            <!-- Tiêu đề chiến dịch -->
                            <div class="mb-6">
                                <label for="title" class="block text-gray-700 font-medium mb-2">Tiêu đề chiến dịch *</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                    placeholder="Ví dụ: Hỗ trợ trẻ em vùng cao đến trường" required>
                                <p class="text-gray-500 text-sm mt-1">Tiêu đề ngắn gọn, mô tả rõ mục đích chiến dịch</p>
                            </div>
                            
                            <!-- Mô tả chiến dịch -->
                            <div class="mb-6">
                                <label for="description" class="block text-gray-700 font-medium mb-2">Mô tả chi tiết *</label>
                                <textarea id="description" name="description" rows="6" 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                    placeholder="Mô tả chi tiết về chiến dịch của bạn, lý do gây quỹ, cách sử dụng số tiền..." 
                                    required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Ảnh chiến dịch -->
                            <div class="mb-6">
                                <label for="image" class="block text-gray-700 font-medium mb-2">Ảnh chiến dịch *</label>
                                <div class="flex items-center justify-center w-full">
                                    <label for="image" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                            <p class="mb-2 text-sm text-gray-500">
                                                <span class="font-semibold">Click để upload ảnh</span> hoặc kéo thả vào đây
                                            </p>
                                            <p class="text-xs text-gray-500">PNG, JPG, GIF (Tối đa 5MB)</p>
                                        </div>
                                        <input id="image" name="image" type="file" class="hidden" accept="image/*" required>
                                    </label>
                                </div>
                                <div id="image-preview" class="mt-2 hidden">
                                    <img id="preview-image" src="#" alt="Preview" class="max-h-48 rounded-lg">
                                </div>
                            </div>
                            
                            <!-- Mục tiêu gây quỹ -->
                            <div class="mb-6">
                                <label for="goal" class="block text-gray-700 font-medium mb-2">Mục tiêu gây quỹ (VND) *</label>
                                <input type="text" id="goal" name="goal" 
                                    value="<?php echo isset($_POST['goal']) ? number_format(intval($_POST['goal']), 0, ',', '.') : ''; ?>"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                    placeholder="Ví dụ: 50,000,000" min="100000" required
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                                <p class="text-gray-500 text-sm mt-1">Số tiền tối thiểu là 100,000 VND</p>
                            </div>
                            
                            <!-- Danh mục -->
                            <div class="mb-6">
                                <label for="category" class="block text-gray-700 font-medium mb-2">Danh mục *</label>
                                <select id="category" name="category"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                    <option value="">-- Chọn danh mục --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (($_POST['category'] ?? '') == $cat['id'] ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Ngày bắt đầu -->
                            <div class="mb-6">
                                <label for="start_date" class="block text-gray-700 font-medium mb-2">Ngày bắt đầu *</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" 
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed" 
                                    readonly>
                                <p class="text-gray-500 text-sm mt-1">Ngày bắt đầu được tự động đặt là ngày hiện tại</p>
                            </div>
                            
                            <!-- Ngày kết thúc -->
                            <div class="mb-6">
                                <label for="end_date" class="block text-gray-700 font-medium mb-2">Ngày kết thúc *</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                <p class="text-gray-500 text-sm mt-1">Chiến dịch phải kéo dài ít nhất 1 ngày</p>
                            </div>
                            
                            <!-- Checkbox cam kết -->
                            <div class="flex items-start mb-6">
                                <input id="confirm" name="confirm" type="checkbox"
                                    class="w-4 h-4 mt-1 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                                <label for="confirm" class="ml-3 text-sm text-gray-700">
                                    Tôi cam đoan thông tin cung cấp là đúng sự thật và hoàn toàn chịu trách nhiệm trước pháp luật.
                                </label>
                            </div>
                            
                            <!-- Thông báo -->
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Chiến dịch của bạn sẽ được xem xét và phê duyệt trong vòng 24 giờ. 
                                            Chúng tôi sẽ thông báo qua email khi chiến dịch được duyệt.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Nút submit -->
                            <div class="flex justify-end">
                                <button type="submit" 
                                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition duration-200">
                                    <i class="fas fa-paper-plane mr-2"></i> Gửi chiến dịch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmBox = document.getElementById('confirm');
            if (!confirmBox.checked) {
                e.preventDefault();
                alert("Vui lòng xác nhận cam kết thông tin là đúng sự thật.");
            }
        });

        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            mobileMenu.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && e.target !== mobileMenuButton) {
                mobileMenu.classList.remove('active');
            }
        });

        userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!userDropdown.contains(e.target) && e.target !== userMenuButton) {
                userDropdown.classList.add('hidden');
            }
        });

        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const previewImage = document.getElementById('preview-image');
        
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    previewImage.setAttribute('src', this.result);
                    imagePreview.classList.remove('hidden');
                });
                
                reader.readAsDataURL(file);
            }
        });
        
        const goalInput = document.getElementById('goal');
        goalInput.addEventListener('blur', function() {
            if (this.value) {
                this.dataset.rawValue = this.value.replace(/\D/g, '');
                this.value = parseInt(this.dataset.rawValue).toLocaleString('vi-VN');
            }
        });

        goalInput.addEventListener('focus', function() {
            if (this.dataset.rawValue) {
                this.value = this.dataset.rawValue;
            }
        });

        document.querySelector('form').addEventListener('submit', function() {
            if (goalInput.dataset.rawValue) {
                goalInput.value = goalInput.dataset.rawValue;
            }
        });
    </script>
</body>
</html>