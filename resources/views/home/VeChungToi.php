<?php
session_start();
require_once __DIR__ . '/../../../core/is_user.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

$db = new DB();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Về Chúng Tôi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1521791055366-0d553872125f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80');
            background-size: cover;
            background-position: center;
        }
        .partner-logo {
            transition: all 0.3s ease;
        }
        .partner-logo:hover {
            transform: scale(1.1);
        }
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .partner-logo img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}

/* Hiệu ứng mượt mà */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}
    </style>
</head>
<body class="bg-gray-50">

<?php include 'nav.php'; ?>

<!-- Hero Section -->
<section class="hero-section text-white py-20 md:py-32">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Về Viectot</h1>
        <p class="text-xl md:text-2xl max-w-3xl mx-auto">Nền tảng gây quỹ cộng đồng trực tuyến tiện lợi, tin cậy và minh bạch</p>
    </div>
</section>

<!-- About Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Giới thiệu về Viectot</h2>
            <div class="text-gray-600 text-lg space-y-4">
                <p>GViectot là nền tảng gây quỹ cộng đồng trực tuyến tiện lợi, tin cậy và minh bạch, được ghi nhận Top 3 bài toán Chuyển đổi số xuất sắc nhất cho các dự án vì cộng đồng, Cuộc thi Tìm kiếm Giải pháp Chuyển đổi số Quốc gia 2022 (Viet Solutions 2022) và là Chiến dịch Marketing vì sự phát triển bền vững tại Marketing for Development Awards 2022, giải thưởng do Red Communication tổ chức cùng sự đồng hành của Liên minh Châu Âu, Oxfam, ProNGO,…</p>
                <p>Viectot được tin dùng bởi các tổ chức cộng đồng uy tín, như: Trung ương Hội chữ thập đỏ Việt Nam, Quỹ Bảo Trợ Trẻ Em Việt Nam, Quỹ Hy vọng, Quỹ từ thiện Nâng bước tuổi thơ, Quỹ từ thiện Bông Sen, Quỹ Trò nghèo Vùng cao, Quỹ Vì Tầm Vóc Việt, Quỹ từ tâm Đắk Lắk, và nhiều tổ chức khác.</p>
            </div>
        </div>

        <!-- Achievements -->
        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto mt-16">
            <div class="bg-green-50 p-8 rounded-lg text-center">
                <div class="text-4xl font-bold text-green-600 mb-4">100+</div>
                <h3 class="text-xl font-semibold mb-2">Tổ chức đối tác</h3>
                <p class="text-gray-600">Hợp tác với các tổ chức uy tín trên khắp Việt Nam</p>
            </div>
            <div class="bg-blue-50 p-8 rounded-lg text-center">
                <div class="text-4xl font-bold text-blue-600 mb-4">1,000+</div>
                <h3 class="text-xl font-semibold mb-2">Dự án thành công</h3>
                <p class="text-gray-600">Hỗ trợ hàng ngàn dự án cộng đồng đạt mục tiêu</p>
            </div>
            <div class="bg-purple-50 p-8 rounded-lg text-center">
                <div class="text-4xl font-bold text-purple-600 mb-4">10M+</div>
                <h3 class="text-xl font-semibold mb-2">Lượt ủng hộ</h3>
                <p class="text-gray-600">Kết nối triệu trái tim nhân ái trên cả nước</p>
            </div>
        </div>
    </div>
</section>

<!-- Technology Partners -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Được hỗ trợ bởi</h2>
        <div class="flex flex-wrap justify-center items-center gap-8 md:gap-12">
            <img src="/images/taitro1.jfif" alt="Comartek" class="h-16 partner-logo">
            <img src="/images/taitro2.jfif" alt="FPT Smart Cloud" class="h-16 partner-logo">
            <img src="/images/taitro3.png" alt="Viettel Money" class="h-16 partner-logo">
            <img src="/images/taitro4.png" alt="VNPay" class="h-16 partner-logo">
        </div>
        <p class="text-center text-gray-600 mt-8 max-w-2xl mx-auto">Viectot được hỗ trợ công nghệ bởi các đối tác hàng đầu, đảm bảo ứng dụng hoạt động ổn định và phương thức thanh toán đa dạng, thuận tiện và an toàn.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Các tổ chức gây quỹ tiêu biểu</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-8"> <!-- Tăng gap từ 6 lên 8 -->
            <?php
               $partners = [
                [
                    'name' => 'Quỹ Bảo Trợ Trẻ Em Việt Nam',
                    'logo' => '/images/tochuc.png'
                ],
                [
                    'name' => 'Hội Chữ Thập Đỏ',
                    'logo' => '/images/tochuc1.png'
                ],
                [
                    'name' => 'Quỹ Hy Vọng',
                    'logo' => '/images/tochuc2.jfif'
                ],
                [
                    'name' => 'PanNature',
                    'logo' => '/images/tochuc3.jpg'
                ],
                [
                    'name' => 'Quỹ Song',
                    'logo' => '/images/tochuc4.png'
                ],
                [
                    'name' => 'Quỹ Phan Anh',
                    'logo' => '/images/tochuc5.png'
                ],
                [
                    'name' => 'Quỹ Sài Gòn',
                    'logo' => '/images/tochuc6.png'
                ],
                [
                    'name' => 'Hoa Chia Sẻ',
                    'logo' => '/images/tochuc7.jfif'
                ],
                [
                    'name' => 'Quỹ Bông Sen',
                    'logo' => '/images/tochuc8.jfif'
                ],
                [
                    'name' => 'Sang Group',
                    'logo' => '/images/tochuc9.png'
                ],
                [
                    'name' => 'Ngân hàng Bưu điện',
                    'logo' => '/images/tochuc10.png'
                ],
                [
                    'name' => 'Huy hiệu Đoàn',
                    'logo' => '/images/tochuc11.jpg'
                ],
                [
                    'name' => 'Hội từ thiện HTP',
                    'logo' => '/images/tochuc12.jfif'
                ],
                [
                    'name' => 'Vườn Xanh',
                    'logo' => '/images/tochuc13.jfif'
                ],
                [
                    'name' => 'Hội Final',
                    'logo' => '/images/tochuc14.jfif'
                ],
                [
                    'name' => 'Trò nghèo Vùng cao',
                    'logo' => '/images/tochuc15.png'
                ],
                [
                    'name' => 'Conservation Vietnam',
                    'logo' => '/images/tochuc16.png'
                ],
                [
                    'name' => 'HandOn',
                    'logo' => '/images/tochuc17.jfif'
                ]
            ];
            
           foreach ($partners as $partner) {
                echo '<div class="group bg-white rounded-xl p-2 flex items-center justify-center h-40 transition-all duration-300 hover:shadow-lg hover:scale-105">';
                echo '<div class="relative w-full h-full overflow-hidden rounded-lg">';
                echo '<img src="' . htmlspecialchars($partner['logo']) . '" 
                      alt="' . htmlspecialchars($partner['name']) . '" 
                      class="absolute inset-0 w-full h-full object-contain transform group-hover:scale-110 transition-transform duration-300"
                      title="' . htmlspecialchars($partner['name']) . '">';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Press Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Báo chí nói về Viectot</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-sm text-gray-500 mb-2">VnExpress - 15/03/2023</div>
                <h3 class="text-xl font-semibold mb-3">Viectot - Cầu nối nhân ái thời công nghệ số</h3>
                <p class="text-gray-600 mb-4">Viectot đã tạo nên một làn sóng mới trong hoạt động từ thiện, kết nối triệu trái tim nhân ái chỉ với vài thao tác đơn giản.</p>
                <a href="#" class="text-green-600 font-medium">Đọc thêm →</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-sm text-gray-500 mb-2">Tuổi Trẻ - 02/04/2023</div>
                <h3 class="text-xl font-semibold mb-3">Ứng dụng công nghệ để lan tỏa yêu thương</h3>
                <p class="text-gray-600 mb-4">Với Viectot, mọi khoản đóng góp dù nhỏ đều được minh bạch và đến tận tay người cần giúp đỡ.</p>
                <a href="#" class="text-green-600 font-medium">Đọc thêm →</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-sm text-gray-500 mb-2">Thanh Niên - 20/05/2023</div>
                <h3 class="text-xl font-semibold mb-3">Top 3 giải pháp chuyển đổi số quốc gia</h3>
                <p class="text-gray-600 mb-4">Viectot được vinh danh tại Viet Solutions 2022 nhờ những đóng góp tích cực cho cộng đồng thông qua công nghệ.</p>
                <a href="#" class="text-green-600 font-medium">Đọc thêm →</a>
            </div>
        </div>
    </div>
</section>



<?php include 'footer.php'; ?>

</body>
</html>