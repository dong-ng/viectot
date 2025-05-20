<?php
// file: category_manager.php

require_once(__DIR__ . '/../../../core/is_user.php');
CheckLogin();
CheckAdmin();
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/DB.php';


$db = new DB();

// Truy vấn dữ liệu từ bảng categories
$sql = "SELECT * FROM category ORDER BY created_at DESC";
$result = $db->query($sql);

// Lấy dữ liệu trả về vào mảng $categories
$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Xử lý thêm/xóa/sửa nếu có request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $image_avt = $_POST['image_avt'];
                
                // $sql = "INSERT INTO category (name, image_avt) VALUES (?, ?)";
                // $db->query($sql, [$name, $image_avt]);
                $NNL->insert("category", [
        'name'       => $name,
        'image_avt'            => $image_avt,
        'created_at'        => time(),
        'updated_at'    => time()
     ]);
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $image_avt = $_POST['image_avt'];
                
                // $sql = "UPDATE category SET name = ?, image_avt = ? WHERE id = ?";
                $NNL->update("category", [
        'name' => $name,
        'updated_at' => time(),
        'image_avt' => $image_avt
    ], " `id` = '".$id."' ");
                break;
                
            case 'delete':
                $id = $_POST['id'];
                // $sql = "DELETE FROM category WHERE id = ?";
                // $db->query($sql, [$id]);
                $del = $NNL->remove("category", "`id`='" . $id . "'");
                break;
        }
        
        // Reload trang sau khi thao tác
        header("Location: /admin/category.php");
        exit();
    }
}

// function createSlug($string) {
//     $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
//     $slug = strtolower($slug);
//     return $slug;
// }

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Quản lý Danh mục</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
</head>
<body 
  class="bg-gray-50"
  x-data="{
    activeTab: 'all',
    showAddModal: false,
    showEditModal: false,
    showDeleteModal: false,
    selectedCategory: null,
    newCategory: { name: '', image_avt: '' },
    editCategory: { id: '', name: '', image_avt: '' }
  }"
>

<div class="flex min-h-screen">
  <?php include 'sidebar.php'; ?>

  <div class="flex-1 p-6">
    <header class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Quản lý Danh mục</h1>
      <p class="text-gray-600">Thêm, sửa, xóa các danh mục</p>
    </header>

    <div class="flex justify-between items-center mb-6">
      <button 
        @click="activeTab = 'all'" 
        :class="{ 'text-blue-600 font-semibold border-b-2 border-blue-600': activeTab === 'all' }"
        class="py-2 px-4 text-sm text-gray-600 hover:text-blue-600"
      >
        Tất cả danh mục
      </button>

      <button 
        @click="showAddModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
      >
        <i class="fas fa-plus mr-2"></i>Thêm danh mục
      </button>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên danh mục</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Icon</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cập nhật lần cuối</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <?php foreach ($categories as $category): ?>
          <tr>
            <td class="px-6 py-4 text-sm text-gray-500"><?php echo $category['id']; ?></td>
            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></td>
            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($category['image_avt']); ?></td>
            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date("d/m/Y", $category['created_at']); ?></td>
             <td class="px-6 py-4 text-sm text-gray-500"><?php echo date("d/m/Y", $category['updated_at']); ?></td>
            <td class="px-6 py-4 text-center text-sm">
              <button 
                @click="
                  editCategory = {
                    id: '<?php echo $category['id']; ?>',
                    name: '<?php echo addslashes($category['name']); ?>',
                    image_avt: '<?php echo addslashes($category['image_avt']); ?>'
                  };
                  showEditModal = true;
                "
                class="text-blue-600 hover:text-blue-800 mr-3"
              >
                <i class="fas fa-edit"></i> Sửa
              </button>
              <button 
                @click="selectedCategory = <?php echo $category['id']; ?>; showDeleteModal = true;"
                class="text-red-600 hover:text-red-800"
              >
                <i class="fas fa-trash"></i> Xoá
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if (empty($categories)): ?>
      <div class="text-center py-10">
        <i class="fas fa-inbox text-4xl text-gray-400 mb-2"></i>
        <p class="text-gray-600">Không có danh mục nào. Hãy thêm mới.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL THÊM -->
<div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white p-6 rounded-md w-full max-w-md">
    <h2 class="text-lg font-semibold mb-4">Thêm danh mục</h2>
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Tên danh mục</label>
        <input x-model="newCategory.name" type="text" class="w-full border rounded px-3 py-2"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Icon (image_avt)</label>
        <textarea x-model="newCategory.image_avt" rows="2" class="w-full border rounded px-3 py-2"></textarea>
      </div>
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button @click="showAddModal = false" class="px-4 py-2 bg-gray-200 rounded">Hủy</button>
      <button 
        @click="
          if (newCategory.name.trim() === '') return alert('Vui lòng nhập tên');
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '';
          ['action', 'name', 'image_avt'].forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = newCategory[key];
            form.appendChild(input);
          });
          form.querySelector('[name=action]').value = 'add';
          document.body.appendChild(form);
          form.submit();
        "
        class="px-4 py-2 bg-blue-600 text-white rounded"
      >Thêm</button>
    </div>
  </div>
</div>

<!-- MODAL SỬA -->
<div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white p-6 rounded-md w-full max-w-md">
    <h2 class="text-lg font-semibold mb-4">Sửa danh mục</h2>
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Tên danh mục</label>
        <input x-model="editCategory.name" type="text" class="w-full border rounded px-3 py-2"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Icon (Font Awesome)(fas fa-)</label>
        <textarea x-model="editCategory.image_avt" rows="2" class="w-full border rounded px-3 py-2"></textarea>
      </div>
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button @click="showEditModal = false" class="px-4 py-2 bg-gray-200 rounded">Hủy</button>
      <button 
        @click="
          if (editCategory.name.trim() === '') return alert('Vui lòng nhập tên');
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '';
          ['action', 'id', 'name', 'image_avt'].forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = editCategory[key];
            form.appendChild(input);
          });
          form.querySelector('[name=action]').value = 'edit';
          document.body.appendChild(form);
          form.submit();
        "
        class="px-4 py-2 bg-blue-600 text-white rounded"
      >Lưu</button>
    </div>
  </div>
</div>

<!-- MODAL XOÁ -->
<div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white p-6 rounded-md w-full max-w-sm">
    <h2 class="text-lg font-semibold mb-4 text-red-600"><i class="fas fa-exclamation-triangle"></i> Xác nhận xoá</h2>
    <p class="mb-4 text-sm text-gray-700">Bạn chắc chắn muốn xoá danh mục này?</p>
    <div class="flex justify-end space-x-2">
      <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 rounded">Hủy</button>
      <button 
        @click="
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '';
          ['action', 'id'].forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = key === 'action' ? 'delete' : selectedCategory;
            form.appendChild(input);
          });
          document.body.appendChild(form);
          form.submit();
        "
        class="px-4 py-2 bg-red-600 text-white rounded"
      >Xoá</button>
    </div>
  </div>
</div>

</body>
</html>
