<?php
session_start();
// if (!isset($_SESSION['login'])) { 
//     header('Location: ../login');
//     exit();
// }
require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Datepicker (Flatpickr) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { transition: all 0.3s; }
        .sidebar-collapsed { width: 80px; }
        .sidebar-collapsed .sidebar-text { display: none; }
        .sidebar-collapsed .logo-text { display: none; }
        .sidebar-collapsed .logo-icon { margin-left: 0; }
        .content-area { transition: all 0.3s; }
        canvas {
            max-width: 100%;
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- END SIDEBAR -->

        <!-- MAIN CONTENT -->
        <div class="content-area flex-1 flex flex-col overflow-hidden">
            <!-- HEADER -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" id="datePicker" class="bg-gray-100 border-0 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:bg-white" placeholder="Chọn tháng/năm">
                        </div>
                        <a href="../index.php" class="text-gray-600 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-home"></i>
                        </a>
                    </div>
                </div>
            </header>
            <!-- END HEADER -->

            <!-- MAIN SECTION -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Tổng quỹ -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Tổng quỹ ủng hộ</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="totalFund">0 VNĐ</h3>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-coins text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User (Số thành viên) -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-indigo-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">User</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="totalUsers">0</h3>
                            </div>
                            <div class="bg-indigo-100 p-3 rounded-full">
                                <i class="fas fa-users text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Số dự án -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Số dự án</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="totalProjects">0</h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-project-diagram text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lượt ủng hộ -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Lượt ủng hộ</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="totalDonations">0</h3>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-hand-holding-heart text-green-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Revenue Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">Doanh thu theo ngày</h2>
                            <div class="text-sm text-gray-500" id="revenueMonthLabel"></div>
                        </div>
                        <div class="h-80">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Projects Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 5 dự án</h2>
                        <div class="h-80">
                            <canvas id="projectsChart"></canvas>
                        </div>
                    </div>

                    <!-- Fund Breakdown Chart (Pie) -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Phân bổ quỹ</h2>
                        <div class="h-80">
                            <canvas id="fundBreakdownChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Donors Chart (Bar) -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 5 Donate</h2>
                        <div class="h-80">
                            <canvas id="topDonorsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Hoạt động gần đây</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người dùng</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody id="activityTable" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Đang tải dữ liệu...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-6 flex justify-center w-full">
                            <div id="pagination" class="inline-flex items-center space-x-1"></div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- END MAIN SECTION -->
        </div>
        <!-- END MAIN CONTENT -->
    </div>

    <script>
    // Khai báo biến toàn cục
    let revenueChart, projectsChart, fundBreakdownChart, topDonorsChart;
    let currentMonth = new Date().getMonth() + 1;
    let currentYear = new Date().getFullYear();

    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo Flatpickr
        flatpickr("#datePicker", {
            mode: "single",
            dateFormat: "m/Y",
            defaultDate: new Date(),
            onChange: function(selectedDates, dateStr, instance) {
                const date = selectedDates[0];
                currentMonth = date.getMonth() + 1;
                currentYear = date.getFullYear();
                loadDashboardData();
            }
        });

        // Load dữ liệu ban đầu
        loadDashboardData();
    });

    // Hàm load dữ liệu dashboard
    function loadDashboardData() {
        document.getElementById('revenueMonthLabel').textContent = `Tháng ${currentMonth}/${currentYear}`;

        // Load dữ liệu cho thống kê và biểu đồ
        fetch(`/ajaxs/admin/home.php?month=${currentMonth}&year=${currentYear}`)
            .then(response => response.json())
            .then(data => {
                if (data.success !== false) {
                    updateStats(data);
                    renderCharts(data);
                    renderTopDonorsTable(data.topDonors || []);
                } else {
                    console.error('Error loading dashboard data:', data.error);
                }
            })
            .catch(error => console.error('Fetch error:', error));

        // Load dữ liệu cho Hoạt động gần đây từ logs
        fetch(`/admin/logs.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success !== false) {
                    renderActivityTable(data.activities || []);
                } else {
                    console.error('Error loading logs data:', data.error);
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }

    // Cập nhật thống kê
    function updateStats(data) {
        document.getElementById('totalFund').textContent = formatCurrency(data.fundBreakdown.totalRaised);
        document.getElementById('totalUsers').textContent = data.totalUsers;
        document.getElementById('totalProjects').textContent = data.totalProjects;
        document.getElementById('totalDonations').textContent = data.totalDonations;
    }

    // Hiển thị top 5 người ủng hộ (bảng)
    function renderTopDonorsTable(donors) {
        const tableBody = document.getElementById('topDonorsTable');
        tableBody.innerHTML = '';

        if (donors.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center px-6 py-4 text-gray-500">Không có dữ liệu</td>
                </tr>
            `;
            return;
        }

        donors.forEach(donor => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-6 py-4 text-sm font-medium text-gray-900">${donor.name}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${formatCurrency(donor.total_amount)}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${donor.donation_count}</td>
            `;
            tableBody.appendChild(row);
        });
    }

    // Vẽ biểu đồ
    function renderCharts(data) {
        // Biểu đồ doanh thu
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        if (revenueChart) revenueChart.destroy();
        
        revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: data.daysInMonth}, (_, i) => i + 1),
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: data.revenueData,
                    borderColor: 'rgba(99, 102, 241, 1)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Doanh thu: ${formatCurrency(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatCurrency(value, true)
                        }
                    }
                }
            }
        });

        // Biểu đồ dự án
        const projectsCtx = document.getElementById('projectsChart').getContext('2d');
        if (projectsChart) projectsChart.destroy();
        
        projectsChart = new Chart(projectsCtx, {
            type: 'bar',
            data: {
                labels: data.projectTitles,
                datasets: [{
                    label: 'Số tiền ủng hộ',
                    data: data.projectRaised,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(244, 63, 94, 0.7)'
                    ],
                    borderColor: [
                        'rgba(99, 102, 241, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(244, 63, 94, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Số tiền: ${formatCurrency(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatCurrency(value, true)
                        }
                    }
                }
            }
        });

        // Biểu đồ phân bổ quỹ (Pie)
        const fundBreakdownCtx = document.getElementById('fundBreakdownChart').getContext('2d');
        if (fundBreakdownChart) fundBreakdownChart.destroy();

        const fundData = data.fundBreakdown;
        const projectDetails = fundData.projects;

        const remainingFund = fundData.totalRaised - (fundData.totalDisbursed + fundData.totalRefunded);
        const remainingPercentage = fundData.totalRaised > 0 ? ((remainingFund / fundData.totalRaised) * 100).toFixed(1) : 0;

        fundBreakdownChart = new Chart(fundBreakdownCtx, {
            type: 'pie',
            data: {
                labels: ['Còn lại', 'Đã giải ngân', 'Đã hoàn tiền'],
                datasets: [{
                    data: [
                        remainingFund,
                        fundData.totalDisbursed,
                        fundData.totalRefunded
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)', // Màu cho phần còn lại
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(244, 63, 94, 0.7)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(244, 63, 94, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const label = ctx.label;
                                const value = ctx.raw;
                                const percentage = fundData.totalRaised > 0 ? ((value / fundData.totalRaised) * 100).toFixed(1) : 0;
                                if (label === 'Còn lại') {
                                    return `${label}: ${formatCurrency(value)} (${remainingPercentage}%)`;
                                } else if (label === 'Đã giải ngân') {
                                    const disbursedDetails = projectDetails
                                        .filter(p => p.disbursed > 0)
                                        .map(p => `${p.title}: ${formatCurrency(p.disbursed)}`)
                                        .join('\n');
                                    return `${label}: ${formatCurrency(value)} (${percentage}%)\n${disbursedDetails}`;
                                } else if (label === 'Đã hoàn tiền') {
                                    return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            }
        });

        // Biểu đồ Top 5 Donate (Bar)
        const topDonorsCtx = document.getElementById('topDonorsChart').getContext('2d');
        if (topDonorsChart) topDonorsChart.destroy();

        topDonorsChart = new Chart(topDonorsCtx, {
            type: 'bar',
            data: {
                labels: data.topDonors.map(donor => donor.name),
                datasets: [{
                    label: 'Số tiền ủng hộ',
                    data: data.topDonors.map(donor => donor.total_amount),
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(244, 63, 94, 0.7)'
                    ],
                    borderColor: [
                        'rgba(99, 102, 241, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(244, 63, 94, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Số tiền: ${formatCurrency(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatCurrency(value, true)
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Hiển thị hoạt động gần đây
    let activitiesData = [];
    let currentPage = 1;
    let itemsPerPage = 5;

    function renderActivityTable(activities) {
        activitiesData = activities;
        currentPage = 1;
        renderPage(currentPage);
    }

    function renderPage(page) {
        const tableBody = document.getElementById('activityTable');
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageItems = activitiesData.slice(start, end);

        tableBody.innerHTML = '';

        if (pageItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center px-6 py-4 text-gray-500">Không có hoạt động nào</td>
                </tr>
            `;
            return;
        }

        pageItems.forEach(activity => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-6 py-4 text-sm text-gray-500">${activity.time}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">${activity.user}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${activity.action}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${activity.details}</td>
            `;
            tableBody.appendChild(row);
        });

        renderPagination();
    }

    function renderPagination() {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';
        const totalPages = Math.ceil(activitiesData.length / itemsPerPage);
        if (totalPages <= 1) return;

        // Prev button
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fas fa-angle-left"></i>';
        prevBtn.className = `px-3 py-2 rounded ${
            currentPage === 1 ? 'bg-gray-300 text-white cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-500'
        }`;
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });
        pagination.appendChild(prevBtn);

        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = `px-3 py-2 rounded ${
                i === currentPage ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'
            } hover:bg-indigo-500 hover:text-white transition`;
            btn.textContent = i;
            btn.addEventListener('click', () => {
                currentPage = i;
                renderPage(i);
            });
            pagination.appendChild(btn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fas fa-angle-right"></i>';
        nextBtn.className = `px-3 py-2 rounded ${
            currentPage === totalPages ? 'bg-gray-300 text-white cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-500'
        }`;
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderPage(currentPage);
            }
        });
        pagination.appendChild(nextBtn);
    }

    // Định dạng tiền tệ
    function formatCurrency(amount, shortForm = false) {
        if (shortForm && amount >= 1000000) {
            return (amount / 1000000).toFixed(1) + 'M VNĐ';
        }
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND',
            minimumFractionDigits: 0
        }).format(amount);
    }
    </script>
</body>
</html>