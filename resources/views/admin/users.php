<?php
session_start();
require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();

require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';

$db = new DB();

// Fetch all users
$query = "SELECT id, name, email, is_admin, created_at FROM users ORDER BY created_at DESC";
$users = $db->get_list($query);

// Ensure is_admin is an integer in the data
foreach ($users as &$user) {
    $user['is_admin'] = (int)$user['is_admin'];
}

// Convert users to JSON for JavaScript with JSON_NUMERIC_CHECK to preserve numeric types
$users_json = json_encode($users, JSON_NUMERIC_CHECK);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý người dùng | Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
    }
    .badge-admin {
      background-color: #6366f1;
      color: white;
    }
    .badge-user {
      background-color: #e5e7eb;
      color: #4b5563;
    }
    .action-btn {
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.875rem;
      transition: all 0.2s;
    }
    .btn-edit {
      background-color: #dbeafe;
      color: #1d4ed8;
    }
    .btn-edit:hover {
      background-color: #bfdbfe;
    }
    .btn-delete {
      background-color: #fee2e2;
      color: #b91c1c;
    }
    .btn-delete:hover {
      background-color: #fecaca;
    }
    .sidebar {
      width: 280px;
      height: 100vh;
      position: fixed;
      background: linear-gradient(195deg, #1a237e, #283593);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .main-content {
      margin-left: 280px;
      width: calc(100% - 280px);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      padding: 2rem;
    }
    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      border: 1px solid #f1f5f9;
    }
    .pagination-btn {
      width: 2.5rem;
      height: 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.2s;
    }
    .pagination-btn.active {
      background-color: #6366f1;
      color: white;
    }
    .pagination-btn:not(.active):hover {
      background-color: #f1f5f9;
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
<body class="bg-gray-50 min-h-screen">
  <div class="flex flex-col lg:flex-row min-h-screen">

    <!-- SIDEBAR -->
    <div class="sidebar text-white">
      <?php include 'sidebar.php'; ?>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800">📋 Quản lý người dùng</h1>
          <p class="text-gray-500 mt-1">Theo dõi và quản lý danh sách người dùng</p>
        </div>
        <a href="/admin/users/add" class="flex items-center justify-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition transform hover:-translate-y-0.5">
          <i class="fas fa-plus mr-2"></i> Thêm người dùng
        </a>
      </div>

      <!-- Filters -->
      <div class="card p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" id="searchInput" onkeyup="renderPage(1)" placeholder="Tìm kiếm theo tên hoặc email..."
                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full"/>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="card overflow-hidden">
        <div id="userTable" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vai trò</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
              </tr>
            </thead>
            <tbody id="userTableBody" class="bg-white divide-y divide-gray-200"></tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden p-12 text-center">
          <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
          </div>
          <h3 class="text-lg font-medium text-gray-900">Không có người dùng nào</h3>
          <p class="mt-1 text-sm text-gray-500">Thêm người dùng mới để bắt đầu</p>
          <div class="mt-6">
            <a href="/admin/users/add" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
              <i class="fas fa-plus mr-2"></i> Thêm người dùng
            </a>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex justify-center">
        <div id="pagination" class="flex items-center space-x-1"></div>
      </div>
    </div>
  </div>

  <script>
    let allUsers = <?php echo $users_json; ?>;
    let currentPage = 1;
    const perPage = 10;

    function formatDate(timestamp) {
      if (!timestamp) return '-';
      const d = new Date(timestamp * 1000); // Convert Unix timestamp to milliseconds
      const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
      return d.toLocaleDateString('vi-VN', options);
    }

    function getRoleBadge(user) {
      // Handle both integer and string values for is_admin
      const isAdmin = parseInt(user.is_admin, 10) === 1;
      return isAdmin
        ? `<span class="badge badge-admin"><i class="fas fa-user-shield mr-1"></i> Quản trị viên</span>`
        : `<span class="badge badge-user"><i class="fas fa-user mr-1"></i> Người dùng</span>`;
    }

    function renderPage(page) {
      currentPage = page;
      const start = (page - 1) * perPage;
      const end = start + perPage;
      const keyword = document.getElementById('searchInput').value.toLowerCase();

      let filtered = allUsers.filter(user => {
        return user.name.toLowerCase().includes(keyword) || 
               user.email.toLowerCase().includes(keyword);
      });

      // Sort by created_at DESC
      filtered.sort((a, b) => b.created_at - a.created_at);

      const list = filtered.slice(start, end);
      const tbody = document.getElementById('userTableBody');
      const emptyState = document.getElementById('emptyState');

      if (filtered.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        renderPagination(0);
        return;
      } else {
        emptyState.classList.add('hidden');
      }

      tbody.innerHTML = '';
      list.forEach((user, index) => {
        // Debug: Log is_admin value to console
        console.log(`User ${user.id}: is_admin = ${user.is_admin}, type = ${typeof user.is_admin}`);

        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${start + index + 1}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.id}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">${user.name}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
          <td class="px-6 py-4 whitespace-nowrap">${getRoleBadge(user)}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(user.created_at)}</td>
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <a href="/admin/users/edit/${user.id}" class="action-btn btn-edit mr-2">
              <i class="fas fa-edit mr-1"></i>Sửa
            </a>
            <a href="/admin/users/delete/${user.id}" class="action-btn btn-delete" onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')">
              <i class="fas fa-trash-alt mr-1"></i>Xóa
            </a>
          </td>
        `;
        tbody.appendChild(row);
      });

      renderPagination(filtered.length);
    }

    function renderPagination(totalItems) {
      const totalPages = Math.ceil(totalItems / perPage);
      const pagination = document.getElementById('pagination');

      if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
      }

      let html = '';
      const maxVisiblePages = 5;
      let startPage, endPage;

      if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
      } else {
        const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
        const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;

        if (currentPage <= maxPagesBeforeCurrent) {
          startPage = 1;
          endPage = maxVisiblePages;
        } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
          startPage = totalPages - maxVisiblePages + 1;
          endPage = totalPages;
        } else {
          startPage = currentPage - maxPagesBeforeCurrent;
          endPage = currentPage + maxPagesAfterCurrent;
        }
      }

      if (currentPage > 1) {
        html += `
          <button onclick="renderPage(${currentPage - 1})" class="pagination-btn text-gray-500 hover:bg-gray-100">
            <i class="fas fa-chevron-left"></i>
          </button>
        `;
      }

      for (let i = startPage; i <= endPage; i++) {
        html += `
          <button onclick="renderPage(${i})" class="pagination-btn ${currentPage === i ? 'active' : 'text-gray-700'}">
            ${i}
          </button>
        `;
      }

      if (currentPage < totalPages) {
        html += `
          <button onclick="renderPage(${currentPage + 1})" class="pagination-btn text-gray-500 hover:bg-gray-100">
            <i class="fas fa-chevron-right"></i>
          </button>
        `;
      }

      pagination.innerHTML = html;
    }

    // Initialize
    renderPage(1);
  </script>
</body>
</html>