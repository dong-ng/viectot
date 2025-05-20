<?php
$categories = $NNL->get_list("SELECT * FROM category");
?>


<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="/" class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-xl">VT</div>
                    <span class="ml-2 text-xl font-bold text-gray-800">Viectot</span>
                </a>
            </div>

            <!-- Main Navigation -->
            <nav class="hidden md:flex items-center space-x-8">
                <a href="/" class="text-gray-700 hover:text-green-600 font-medium">Trang chủ</a>
                <a href="/home/du-an" class="text-gray-700 hover:text-green-600 font-medium">Dự án</a>

                <!-- Dropdown Danh Mục -->
                <div class="relative">
                    <a href="#" id="categoryMenuButton" class="text-gray-700 hover:text-green-600 font-medium">Danh mục</a>
                    <div id="categoryDropdown" class="absolute left-0 mt-2 w-44 bg-white rounded-md shadow-lg hidden z-50">
                        <?php
                        
                        if (!empty($categories)) {
                            foreach ($categories as $cat) {
                                echo '<a href="/home/category/' . $cat['id'] . '" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">' . htmlspecialchars($cat['name']) . '</a>';
                            }
                        } else {
                            echo '<div class="px-4 py-2 text-gray-500">Không có danh mục</div>';
                        }
                        ?>
                    </div>
                </div>
<a href="/home/history.php" class="text-gray-700 hover:text-green-600 font-medium">Lịch sử giao dịch</a>
                <a href="/home/VeChungToi.php" class="text-gray-700 hover:text-green-600 font-medium">Về chúng tôi</a>
              
            </nav>

            <!-- Auth Buttons - Desktop -->
            <div class="flex items-center space-x-4 relative">
                <a href="/home/createcamp" class="hidden md:inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200 font-medium">
                    Tạo chiến dịch
                </a>
               
               
                <div class="relative">
                    <button id="userMenuButton" class="px-3 py-2 text-gray-700 hover:text-green-600 focus:outline-none">
                        <i class="far fa-user"></i>
                    </button>
                    <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-50">
    <?php
    if (CheckLogin()) {
        echo "<div class='px-4 py-2 text-gray-700 font-medium border-b'>Xin chào, " . htmlspecialchars($getUser['name']) . "</div>";
        if ($getUser['is_admin'] == 1) {
            echo '<a href="/admin" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Trang Quản Trị</a>';
        } else {
            echo '<a href="/user/duancuatoi" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Quản lý dự án</a>';
        }
        echo "<p class='block px-4 py-2 text-gray-700 hover:bg-gray-100'>Số dư: ".$getUser['sodu']."</p>";
        echo "<a href='" . BASE_URL('') . "home/logout' class='block px-4 py-2 text-gray-700 hover:bg-gray-100'>Đăng xuất</a>";
    } else {
        echo "<a href='" . BASE_URL('') . "home/login' class='block px-4 py-2 text-gray-700 hover:bg-gray-100'>Đăng nhập</a>";
    }
    ?>
</div>

                </div>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center space-x-4">
                <button id="mobileMenuButton" class="mobile-menu-button text-gray-700 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="mobile-menu md:hidden bg-white hidden">
            <div class="px-2 pt-2 pb-4 space-y-1">
                <a href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50">Trang chủ</a>
                <a href="/home/du-an" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50">Dự án</a>

                <!-- Dropdown danh mục mobile -->
                <?php
                if (!empty($categories)) {
                    echo '<div class="block px-3 py-2 rounded-md text-base font-medium text-gray-700">Danh mục</div>';
                    foreach ($categories as $cat) {
                        echo '<a href="category.php?id=' . $cat['id'] . '" class="block pl-6 pr-3 py-2 text-sm text-gray-700 hover:text-green-600 hover:bg-gray-100">' . htmlspecialchars($cat['name']) . '</a>';
                    }
                }
                ?>

                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50">Về chúng tôi</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50">Tin tức</a>

                <div class="pt-2 border-t border-gray-200">
                    <a href="/home/createcamp" class="block w-full px-4 py-2 text-center bg-green-600 text-white rounded-md font-medium hover:bg-green-700 mb-2">
                        Tạo chiến dịch
                    </a>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="#" class="px-4 py-2 text-center border border-gray-300 rounded-md font-medium hover:bg-gray-100">
                            Đăng nhập
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- JS: Danh mục dropdown + Mobile menu -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("categoryMenuButton");
    const dropdown = document.getElementById("categoryDropdown");
    const mobileMenuBtn = document.getElementById("mobileMenuButton");
    const mobileMenu = document.getElementById("mobileMenu");

    btn.addEventListener("click", (e) => {
        e.preventDefault();
        dropdown.classList.toggle("hidden");
    });

    document.addEventListener("click", function(e) {
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add("hidden");
        }
    });

    mobileMenuBtn.addEventListener("click", () => {
        mobileMenu.classList.toggle("hidden");
    });
});
</script>
