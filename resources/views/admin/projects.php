<?php
session_start();
require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Quản lý Dự án</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --success: #10b981;
      --success-hover: #059669;
      --warning: #f59e0b;
      --warning-hover: #d97706;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }
    .sidebar {
      width: 250px;
      height: 100vh;
      position: fixed;
      background: linear-gradient(195deg, #1a237e, #283593);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .main-content {
      margin-left: 280px;
      width: calc(100% - 280px);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      padding: 2rem;
    }
    .progress-bar {
      height: 8px;
      border-radius: 4px;
      overflow: hidden;
      background-color: #e2e8f0;
    }
    .progress-value {
      height: 100%;
      background: linear-gradient(90deg, var(--primary), #818cf8);
      transition: width 0.3s ease;
    }
    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
      border: 1px solid #f1f5f9;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .badge-active {
      background-color: #e0e7ff;
      color: var(--primary);
    }
    .badge-completed {
      background-color: #d1fae5;
      color: var(--success);
    }
    .badge-expired {
      background-color: #fee2e2;
      color: var(--danger);
    }
    .tab-btn {
      padding: 0.5rem 1.25rem;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.2s;
      display: flex;
      align-items: center;
    }
    .tab-btn i {
      margin-right: 0.5rem;
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
      background-color: var(--primary);
      color: white;
    }
    .pagination-btn:not(.active):hover {
      background-color: #f1f5f9;
    }
    .project-image {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
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
<body class="bg-gray-50">
  <div class="sidebar text-white">
    <?php include 'sidebar.php'; ?>
  </div>

  <div class="main-content">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">📋 Quản lý Dự án</h1>
        <p class="text-gray-500 mt-1">Theo dõi và quản lý các dự án từ thiện</p>
      </div>
      <a href="/admin/projects/add" class="flex items-center justify-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition transform hover:-translate-y-0.5">
        <i class="fas fa-plus mr-2"></i> Thêm dự án mới
      </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="card p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">Đang triển khai</p>
            <h3 id="count-active" class="text-2xl font-bold text-gray-800 mt-1">0</h3>
          </div>
          <div class="p-3 rounded-full bg-indigo-50 text-indigo-600">
            <i class="fas fa-spinner fa-lg"></i>
          </div>
        </div>
      </div>
      <div class="card p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">Đã hoàn thành</p>
            <h3 id="count-done" class="text-2xl font-bold text-gray-800 mt-1">0</h3>
          </div>
          <div class="p-3 rounded-full bg-green-50 text-green-600">
            <i class="fas fa-check-circle fa-lg"></i>
          </div>
        </div>
      </div>
      <div class="card p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">Đã quá hạn</p>
            <h3 id="count-expired" class="text-2xl font-bold text-gray-800 mt-1">0</h3>
          </div>
          <div class="p-3 rounded-full bg-red-50 text-red-600">
            <i class="fas fa-exclamation-circle fa-lg"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Tabs -->
    <div class="card p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex flex-wrap gap-2">
          <button onclick="setView('active')" id="tab-active" class="tab-btn bg-indigo-600 text-white">
            <i class="fas fa-spinner"></i> Đang triển khai
          </button>
          <button onclick="setView('done')" id="tab-done" class="tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-check-circle"></i> Hoàn thành
          </button>
          <button onclick="setView('expired')" id="tab-expired" class="tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-exclamation-circle"></i> Quá hạn
          </button>
          <button onclick="showRefundLog()" id="tab-log" class="hidden tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200">
  <i class="fas fa-file-alt"></i> Log hoàn tiền
</button>

        </div>
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" id="searchInput" onkeyup="renderPage(1)" placeholder="Tìm dự án..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full"/>
          </div>
          <select id="timeFilter" onchange="renderPage(1)" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <option value="all">Tất cả thời gian</option>
            <option value="week">Tuần này</option>
            <option value="month">Tháng này</option>
            <option value="year">Năm nay</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Projects Table -->
    <div class="card overflow-hidden">
      <div id="projectTable" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hình ảnh</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <div class="flex items-center">
                Dự án
                <button id="sortBtn" onclick="toggleSort()" class="ml-2 text-gray-500 hover:text-gray-700">
                  <i id="sortIcon" class="fas fa-sort"></i>
                </button>
              </div>
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiến độ</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quỹ</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
          </tr>
          </thead>
          <tbody id="projectTableBody" class="bg-white divide-y divide-gray-200"></tbody>
        </table>
      </div>

      <!-- Refund Log -->
      <div id="refundLog" class="hidden p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Lịch sử hoàn tiền</h3>
        <div id="logContent" class="bg-gray-50 p-4 rounded-lg max-h-[500px] overflow-y-auto"></div>
      </div>

      <!-- Empty State -->
      <div id="emptyState" class="hidden p-12 text-center">
        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900">Không có dự án nào</h3>
        <p class="mt-1 text-sm text-gray-500">Tạo dự án mới để bắt đầu</p>
        <div class="mt-6">
          <a href="/admin/projects/add" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i> Thêm dự án mới
          </a>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
      <div id="pagination" class="flex items-center space-x-1"></div>
    </div>
  </div>

  <script>
    let allProjects = [];
    let currentPage = 1;
    let currentView = 'active';
    const perPage = 5;
    let sortOrder = 'asc';

    function formatCurrency(amount) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency', 
        currency: 'VND', 
        minimumFractionDigits: 0
      }).format(amount);
    }

    function formatDate(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
      return d.toLocaleDateString('vi-VN', options);
    }

    function daysLeft(endDateStr, progress) {
      if (!endDateStr) return '-';
      const end = new Date(endDateStr);
      const now = new Date();
      const diff = Math.ceil((end - now) / (1000 * 60 * 60 * 24));
      
      if (progress >= 100) return '<span class="text-green-600 font-medium">Hoàn thành</span>';
      if (diff >= 0) return `<span class="text-gray-700">Còn ${diff} ngày</span>`;
      return '<span class="text-red-600 font-medium">Đã quá hạn</span>';
    }

    function getStatusBadge(project) {
      const now = new Date();
      const end = new Date(project.end_date);

      if (project.progress >= 100) {
        return `<span class="badge badge-completed"><i class="fas fa-check-circle mr-1"></i> Hoàn thành</span>`;
      } else if (project.source === 'request' && end < now) {
        return `<span class="badge badge-expired"><i class="fas fa-hourglass-end mr-1"></i> Quá hạn - chờ duyệt</span>`;
      } else if (end < now) {
        return `<span class="badge badge-expired"><i class="fas fa-exclamation-circle mr-1"></i> Đã quá hạn</span>`;
      } else if (project.source === 'request') {
        return `<span class="badge badge-active"><i class="fas fa-clock mr-1"></i> Chờ duyệt</span>`;
      } else {
        return `<span class="badge badge-active"><i class="fas fa-spinner mr-1"></i> Đang triển khai</span>`;
      }
    }

    function refundProject(projectId) {
      Swal.fire({
        title: 'Xác nhận hoàn tiền',
        text: 'Bạn có chắc muốn hoàn tiền cho tất cả nhà hảo tâm của dự án này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hoàn tiền',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('/ajaxs/admin/refund_project.php', {
            method: 'POST',
            body: JSON.stringify({ project_id: projectId })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire('Thành công', 'Hoàn tiền thành công!', 'success').then(() => {
                fetchProjects(); // Tải lại danh sách dự án
              });
            } else {
              Swal.fire('Lỗi', data.message || 'Không thể hoàn tiền', 'error');
            }
          })
          .catch(err => {
            Swal.fire('Lỗi', 'Có lỗi xảy ra: ' + err.message, 'error');
          });
        }
      });
    }
function showDisburseLog(projectId) {
  fetch('/ajaxs/admin/desburse_log.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ project_id: projectId })
  })
  .then(res => res.json())
  .then(data => {
    const logs = data.success ? data.data : [];
    if (logs.length === 0) {
      return Swal.fire('Thông báo', 'Không có lịch sử giải ngân nào cho dự án này.', 'info');
    }

    // Tạo HTML hiển thị từng entry
    const html = logs.map(log => `
      <p>
        <strong>Người giải ngân:</strong> ${log.admin_name || 'Admin'}<br>
        <strong>Thời gian:</strong> ${formatDate(log.datetime)}<br>
        <strong>Số tiền:</strong> ${formatCurrency(log.amount)}
      </p><hr>
    `).join('');

    Swal.fire({
      title: 'Lịch sử giải ngân',
      html,
      width: 600,
      confirmButtonText: 'Đóng'
    });
  })
  .catch(err => {
    Swal.fire('Lỗi', 'Không thể tải lịch sử giải ngân: ' + err.message, 'error');
  });
}

    function fetchProjects() {
      fetch('/ajaxs/admin/project_list.php')
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            allProjects = data.data;
            updateCounts();
            renderPage(1);
          }
        });
    }

    function updateCounts() {
      const now = new Date();
      let active = 0, done = 0, expired = 0;
      
      allProjects.forEach(p => {
        if (p.progress >= 100) {
          done++;
        } else if (new Date(p.end_date) < now) {
          expired++;
        } else {
          active++;
        }
      });
      
      document.getElementById('count-active').innerText = active;
      document.getElementById('count-done').innerText = done;
      document.getElementById('count-expired').innerText = expired;
    }

    function setView(view) {
      currentView = view;
      
      document.getElementById('tab-active').className = 
        view === 'active' ? 'tab-btn bg-indigo-600 text-white' : 'tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200';
      document.getElementById('tab-done').className = 
        view === 'done' ? 'tab-btn bg-green-600 text-white' : 'tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200';
      document.getElementById('tab-expired').className = 
        view === 'expired' ? 'tab-btn bg-red-600 text-white' : 'tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200';
      document.getElementById('tab-log').className = 
        view === 'log' ? 'tab-btn bg-blue-600 text-white' : 'tab-btn bg-gray-100 text-gray-700 hover:bg-gray-200';
      
      if (view === 'log') {
        showRefundLog();
      } else {
            document.getElementById('tab-log').classList.add('hidden'); // ẩn tab

        document.getElementById('projectTable').classList.remove('hidden');
        document.getElementById('refundLog').classList.add('hidden');
        renderPage(1);
      }
    }

   function showRefundLog(projectId) {
  fetch('/ajaxs/admin/refund_log.php')
    .then(res => res.json())
    .then(data => {
      let filteredLogs = data.data.filter(log => log.project_id == projectId);
      if (filteredLogs.length === 0) {
        Swal.fire('Thông báo', 'Không có log hoàn tiền nào cho dự án này.', 'info');
        return;
      }

      let html = filteredLogs.map(log => `
      <p><strong>Người hoàn:</strong> <?= $getUser['name']?></p>
        <p><strong>Thời gian:</strong> ${log.datetime}<br>
        <strong>Số tiền:</strong> ${formatCurrency(log.amount || log.total_amount)}<br>
        ${log.email ? '<strong>Email được hoàn:</strong> ' + log.email : ''}</p><hr>
      `).join('');

      Swal.fire({
        title: 'Lịch sử hoàn tiền',
        html: html,
        width: 600,
        confirmButtonText: 'Đóng'
      });
    });
}


    function toggleSort() {
      sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
      const sortIcon = document.getElementById('sortIcon');
      sortIcon.className = sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
      renderPage(1);
    }

    function renderPage(page) {
    currentPage = page;
    const start = (page - 1) * perPage;
    const end = start + perPage;
    const keyword = document.getElementById('searchInput').value.toLowerCase();
    const timeFilter = document.getElementById('timeFilter').value;
    const now = new Date();

    let filtered = allProjects.filter(p => {
        const matchesTab =
            currentView === 'active' ? p.progress < 100 && new Date(p.end_date) >= now :
            currentView === 'done' ? p.progress >= 100 :
            currentView === 'expired' ? p.progress < 100 && new Date(p.end_date) < now :
            true;
        const matchesSearch = 
            p.title.toLowerCase().includes(keyword) || 
            p.description.toLowerCase().includes(keyword);
        const startDate = new Date(p.start_date);
        let matchesTime = true;
        
        if (timeFilter === 'week') {
            const startOfWeek = new Date(now);
            startOfWeek.setDate(now.getDate() - now.getDay());
            matchesTime = startDate >= startOfWeek;
        } else if (timeFilter === 'month') {
            const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            matchesTime = startDate >= startOfMonth;
        } else if (timeFilter === 'year') {
            const startOfYear = new Date(now.getFullYear(), 0, 1);
            matchesTime = startDate >= startOfYear;
        }

        return matchesTab && matchesSearch && matchesTime;
    });

    filtered.sort((a, b) => {
        const titleA = a.title.toLowerCase();
        const titleB = b.title.toLowerCase();
        if (sortOrder === 'asc') {
            return titleA < titleB ? -1 : titleA > titleB ? 1 : 0;
        } else {
            return titleA > titleB ? -1 : titleA < titleB ? 1 : 0;
        }
    });

    const list = filtered.slice(start, end);
    const tbody = document.getElementById('projectTableBody');
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
    list.forEach((project, index) => {
        const row = document.createElement('tr');

        const isDone = project.progress >= 100;
        const isExpired = project.progress < 100 && new Date(project.end_date) < now && project.status !== 'refunded';
        // Kiểm tra nếu đã giải ngân (disbursed_amount > 0)
        const isDisbursed = project.disbursed_amount > 0;
        const disburseBtn = isDone ? 
  (isDisbursed ? 
    `<span class="ml-3 text-green-300 cursor-not-allowed" title="Đã giải ngân" style="opacity: 0.5; pointer-events: none;">
      <i class="fas fa-hand-holding-usd"></i>
    </span>` :
    `<a href="#" onclick="disburseProject(${project.id}); return false;" class="ml-3 text-green-600 hover:text-green-800 transition" title="Giải ngân">
      <i class="fas fa-hand-holding-usd"></i>
    </a>`) : '';
        // trong renderPage(), sau khi lấy project và xác định isExpired:
        const hasExpired = project.progress < 100 && new Date(project.end_date) < new Date();
        
        // kiểm tra flag refund từ API
        const isRefunded = project.refund === 1;
        const refundBtn = hasExpired && !isRefunded
              ? `<a href="#" onclick="refundProject(${project.id}); return false;"
                    class="ml-3 text-yellow-600 hover:text-yellow-800 transition"
                    title="Hoàn tiền">
                    <i class="fas fa-undo"></i>
                 </a>`
              : '';

        const refundHistoryBtn = isExpired ? 
            `<a href="#" onclick="showRefundLog(${project.id})" class="ml-3 text-blue-600 hover:text-blue-800 transition" title="Xem lịch sử hoàn tiền">
                <i class="fas fa-history"></i>
            </a>` : '';
        const disburseHistoryBtn = isDisbursed
          ? `<a href="#" onclick="showDisburseLog(${project.id}); return false;"
               class="ml-3 text-purple-600 hover:text-purple-800 transition"
               title="Xem lịch sử giải ngân">
               <i class="fas fa-history"></i>
             </a>`
          : '';
        const imageSrc = project.image ? `/images/${project.image}` : 'https://via.placeholder.com/50?text=No+Image';

        row.innerHTML = `
            <td class="px-6 py-4 text-sm text-gray-800 font-medium">${start + index + 1}</td>
            <td class="px-6 py-4">
                ${project.source === 'request' ? `
                    <a href="/admin/chitietduan.php?id=${project.id}">
                        <img src="${imageSrc}" alt="${project.title}" class="project-image"/>
                    </a>
                ` : `
                    <a href="/admin/chitietduan2/${project.id}">
                        <img src="${imageSrc}" alt="${project.title}" class="project-image"/>
                    </a>
                `}
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="ml-4">
                        <div class="font-medium text-gray-900">${project.title}</div>
                        <div class="text-gray-500 text-sm mt-1 line-clamp-1">${project.description}</div>
                        <div class="mt-1">${getStatusBadge(project)}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900">${formatDate(project.start_date)}</div>
                <div class="text-sm text-gray-500">→ ${formatDate(project.end_date)}</div>
                <div class="mt-1 text-xs text-gray-500">${daysLeft(project.end_date, project.progress)}</div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-16 text-sm text-right font-medium text-gray-900">${project.progress}%</div>
                    <div class="flex-1 progress-bar ml-3">
                        <div class="progress-value" style="width: ${project.progress}%"></div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">${formatCurrency(project.raised)}</div>
                <div class="text-xs text-gray-500">Mục tiêu: ${formatCurrency(project.goal)}</div>
            </td>
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end space-x-2">
                    <a href="/admin/projects/edit/${project.id}" class="text-indigo-600 hover:text-indigo-900 transition" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="/admin/projects/delete/${project.id}" class="text-red-600 hover:text-red-900 transition" onclick="return confirm('Bạn chắc chắn muốn xóa dự án này?')" title="Xóa">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    ${disburseBtn}
                    ${refundBtn}
                    ${refundHistoryBtn}
                    ${disburseHistoryBtn}
                </div>
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
        const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) -1;
        
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
    fetchProjects();
    function disburseProject(projectId) {
  Swal.fire({
    title: 'Xác nhận giải ngân',
    text: 'Bạn có chắc muốn giải ngân cho dự án này?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Giải ngân',
    cancelButtonText: 'Hủy',
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#ef4444'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('/ajaxs/admin/disburse.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ project_id: projectId })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Thành công', data.message, 'success').then(() => {
            fetchProjects(); // Cập nhật lại bảng
          });
        } else {
          Swal.fire('Lỗi', data.message, 'error');
        }
      })
      .catch(err => {
        Swal.fire('Lỗi', 'Có lỗi xảy ra: ' + err.message, 'error');
      });
    }
  });
}

  </script>
</body>
</html>