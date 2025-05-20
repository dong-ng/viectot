<?php
// sidebar.php
// File này giả sử được include sau khi session đã được khởi tạo và $_SESSION['username'] có giá trị.
?>
<aside class="sidebar bg-gradient-to-b from-indigo-900 to-indigo-800 text-white w-64 flex flex-col">
    <div class="p-4 flex items-center justify-between border-b border-indigo-700">
        <div class="flex items-center">
            <i class="fas fa-hand-holding-heart text-2xl text-pink-400 logo-icon ml-1"></i>
            <span class="text-xl font-bold ml-3 logo-text">ViecTot</span>
        </div>
        <button id="sidebarToggle" class="text-gray-300 hover:text-white focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="flex-1 overflow-y-auto py-4">
        <nav>
            <ul>
                <li class="mb-1">
                    <a href="/admin" class="flex items-center px-6 py-3 text-white hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-tachometer-alt w-6 text-center"></i>
                        <span class="ml-3 sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="mb-1">
                    <a href="/admin/projects" class="flex items-center px-6 py-3 text-white hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-project-diagram w-6 text-center"></i>
                        <span class="ml-3 sidebar-text">Quản lý dự án</span>
                    </a>
                </li>
                <li class="mb-1">
                    <a href="/admin/duyetdon" class="flex items-center px-6 py-3 text-white hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-check-circle w-6 text-center"></i>
                        <span class="ml-3 sidebar-text">Quản lý duyệt dự án</span>
                    </a>
                </li>
                <li class="mb-1">
                    <a href="/admin/baocao.php" class="flex items-center px-6 py-3 text-white hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-chart-bar w-6 text-center"></i>
                        <span class="ml-3 sidebar-text">Quản lý giao dịch</span>
                    </a>
                </li>

                <li class="mb-1">
                    <a href="/admin/users.php" class="flex items-center px-6 py-3 text-white hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-users w-6 text-center"></i>
                        <span class="ml-3 sidebar-text">Quản lý người dùng</span>
                    </a>
                </li>
                <!--<li class="mb-1">-->
                <!--    <a href="/admin/settings.php" class="flex items-center px-6 py-3 text-white hover:bg-indigo-700 transition-colors">-->
                <!--        <i class="fas fa-cogs w-6 text-center"></i>-->
                <!--        <span class="ml-3 sidebar-text">Cài đặt</span>-->
                <!--    </a>-->
                <!--</li>-->
            </ul>
        </nav>
    </div>
    
    <div class="p-4 border-t border-indigo-700">
        <div class="flex items-center">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=random" 
                 class="w-10 h-10 rounded-full" alt="User">
            <div class="ml-3 sidebar-text">
                <div class="font-medium"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="text-xs text-indigo-300">Admin</div>
            </div>
        </div>
        <a href="/home/logout.php" class="mt-3 flex items-center text-indigo-200 hover:text-white transition-colors sidebar-text">
            <i class="fas fa-sign-out-alt w-6 text-center"></i>
            <span class="ml-3">Đăng xuất</span>
        </a>
    </div>
</aside>
