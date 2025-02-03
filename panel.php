<?php
session_start(); // Start the session at the beginning of the script

$host = 'localhost';
$dbname = 'news_site';
$username = 'root';
$password = ''; // Leave empty if no password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Define the user role (this should come from your session or authentication system)
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest'; // Example role, change based on your session

// Example query to fetch data (change as needed)
$sql = "SELECT * FROM users";  // Replace with your table name
$stmt = $conn->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql ="SELECT * FROM articles";
$stmt = $conn->prepare($sql);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>پنل مدیریت</title>
  <link rel="stylesheet" href="panel.css">
  <style>
    /* استایل‌های نمونه برای نمایش بهتر */
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; direction: rtl; }
    .sidebar { width: 250px; background: #333; color: #fff; height: 100vh; float: right; }
    .sidebar .logo { padding: 20px; font-size: 20px; text-align: center; background: #444; }
    .nav-menu { list-style: none; padding: 0; margin: 0; }
    .nav-item { padding: 15px 20px; cursor: pointer; border-bottom: 1px solid #444; }
    .nav-item.active, .nav-item:hover { background: #555; }
    .submenu { list-style: none; padding-right: 20px; display: none; }
    .nav-item.active .submenu { display: block; }
    .submenu-item { padding: 10px 0; cursor: pointer; }
    .main-content { padding: 20px; }
    .admin-header { display: flex; justify-content: space-between; align-items: center; }
    .card { background: #f9f9f9; padding: 15px; margin: 15px 0; border: 1px solid #ddd; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { border: 1px solid #ccc; padding: 10px; text-align: center; }
    .btn { padding: 5px 10px; text-decoration: none; border: none; cursor: pointer; }
    .btn-primary { background: #007bff; color: #fff; }
    .btn-danger { background: #dc3545; color: #fff; }
    .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #333; color: #fff; padding: 10px 20px; border-radius: 5px; opacity: 0.9; }
    .toast.error { background: #dc3545; }
  </style>
</head>
<body>
  <!-- نوار کناری -->
  <aside class="sidebar">
    <div class="logo">پنل مدیریت</div>
    <ul class="nav-menu">
      <li class="nav-item" data-target="dashboard">🧰 پیشخوان</li>
      <?php if ($userRole === 'admin') : ?>
      <li class="nav-item has-submenu">
        👥 کاربران
        <ul class="submenu">
          <li class="submenu-item" data-target="userList">لیست کاربران</li>
          <!-- <li class="submenu-item" data-target="addUser">افزودن کاربر</li> -->
        </ul>
      </li>
      <?php endif; ?>

      <?php if ($userRole === 'admin') : ?>
      <li class="nav-item has-submenu">
        📝 اخبار
        <ul class="submenu">
          <li class="submenu-item" data-target="newsList">همه اخبار</li>
          <li class="submenu-item" data-target="addNews">افزودن خبر جدید</li>
        </ul>
      </li>
      <!-- <li class="nav-item has-submenu">
        📎 دسته بندی
        <ul class="submenu">
          <li class="submenu-item" data-target="categoryList">همه دسته بندی‌ها</li>
          <li class="submenu-item" data-target="addCategory">افزودن دسته بندی جدید</li>
        </ul>
      </li> -->
      <li class="nav-item" data-target="statistics">📊 آمار</li>
      <?php else: ?>
      <li class="nav-item" data-target="addNews">📝 افزودن خبر</li>
      <?php endif; ?>
    </ul>
  </aside>

  <!-- محتوای اصلی -->
  <main class="main-content">
    <header class="admin-header">
      <h1>پنل مدیریت</h1>
      <button class="btn btn-primary" id="logoutBtn">خروج از سیستم</button>
    </header>

    <!-- container for dynamic content -->
    <div id="content">
      <div id="dashboard" class="content-section active">
        <div class="card">
          <h2>اطلاعات مدیر</h2>
          <p><strong>نام:</strong> <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'نامشخص'; ?></p>
          <p><strong>نقش:</strong> <?php echo $_SESSION['user_role'] === 'admin' ? 'ادمین' : 'نویسنده'; ?></p>
        </div>
      </div>

      <?php if ($userRole === 'admin') : ?>
<div id="userList" class="content-section">
    <h2>لیست کاربران</h2>
    <div class="card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام کاربری</th>
                    <th>ایمیل</th>
                    <th>نقش</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) : ?>
                    <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['role'] === 'admin' ? 'ادمین' : 'نویسنده'; ?></td>
                        <td>
                            <button class="btn btn-primary">ویرایش</button>
                            <button class="btn btn-danger">حذف</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4">هیچ کاربری یافت نشد.</td> <!-- Display message if no users are found -->
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<div id="newsList" class="content-section">
  <h2>مدیریت همه اخبار</h2>
  <div class="card">
      <div class="table-responsive">
          <table class="data-table">
              <thead>
                  <tr>
                      <th>عنوان</th>
                      <th>تاریخ انتشار</th>
                      <th>دسته‌بندی</th>
                      <th>عملیات</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if (!empty($articles)) : ?>
                      <?php foreach ($articles as $news) : ?>
                          <tr>
                              <td><?php echo htmlspecialchars($news['title']); ?></td>
                              <td><?php echo date("Y/m/d", strtotime($news['published_at'])); ?></td> <!-- Assuming 'published_at' is a valid date column -->
                              <td><?php echo htmlspecialchars($news['category']); ?></td>
                              <td>
                                  <button class="btn btn-primary btn-edit" id="edit-<?php echo $news['id']; ?>"><a href="edit.php">ویرایش</a></button>
                                  <button class="btn btn-danger btn-delete" data-id="<?php echo $news['id']; ?>">حذف</button>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php else : ?>
                      <tr>
                          <td colspan="5">هیچ خبری یافت نشد.</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>
  </div>
</div>
<div id="addNews" class="content-section">
  <h2>افزودن خبر جدید</h2>
  <div class="card">
    <form id="addNewsForm" enctype="multipart/form-data">
      <div class="form-group">
        <label>عنوان خبر</label>
        <input type="text" 
               id="newsTitle" 
               required
               maxlength="120"
               placeholder="عنوان خبر را وارد کنید">
        <div class="char-counter"><span id="titleCounter">0</span>/120</div>
      </div>

      <div class="form-group">
        <label>توضیح یک خطی</label>
        <input type="text" 
               id="newsSummary" 
               required
               maxlength="150"
               placeholder="خلاصه خبر در یک جمله">
        <div class="char-counter"><span id="summaryCounter">0</span>/150</div>
      </div>

      <div class="form-group">
        <label>متن کامل خبر</label>
        <div class="editor-wrapper">
          <div id="editor"></div>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label>دسته‌بندی</label>
          <select id="newsCategory" required>
            <option value="">انتخاب دسته‌بندی</option>
            <option value="1">سیاسی</option>
            <option value="2">اقتصادی</option>
            <option value="3">فرهنگی</option>
          </select>
        </div>

        <div class="form-group">
          <label>نویسنده</label>
          <input type="text" 
                 id="newsAuthor" 
                 required
                 placeholder="نام نویسنده">
        </div>

        <div class="form-group">
          <label>تاریخ انتشار</label>
          <input type="datetime-local" 
                 id="publishDate" 
                 required>
        </div>
      </div>

      <div class="form-group">
        <label>تصویر شاخص</label>
        <div class="image-uploader" id="uploadContainer">
          <input type="file" id="newsImage" accept="image/*" hidden>
          <img src="" class="image-preview" alt="پیش‌نمایش تصویر">
          <p>برای آپلود کلیک کنید یا تصویر را اینجا بکشید</p>
          <small>فرمت‌های مجاز: JPG, PNG (حداکثر 2MB)</small>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">انتشار خبر</button>
        <button type="button" class="btn btn-secondary">ذخیره پیش‌نویس</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Character counter for Title
  document.getElementById('newsTitle').addEventListener('input', function () {
    document.getElementById('titleCounter').textContent = this.value.length;
  });

  // Character counter for Summary
  document.getElementById('newsSummary').addEventListener('input', function () {
    document.getElementById('summaryCounter').textContent = this.value.length;
  });

  // Image preview
  const uploadContainer = document.getElementById('uploadContainer');
  const fileInput = document.getElementById('newsImage');
  const imagePreview = uploadContainer.querySelector('.image-preview');
  uploadContainer.addEventListener('click', function () {
    fileInput.click();
  });

  fileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        imagePreview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // Form submission via AJAX
  document.getElementById('addNewsForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const formData = new FormData(this);

    // Send the data to the server via AJAX
    fetch('process_news.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('خبر با موفقیت اضافه شد');
        window.location.href = 'news_list.php'; // Redirect to news list page
      } else {
        alert('خطا در افزودن خبر');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('خطا در ارسال اطلاعات');
    });
  });
</script>

    </div>
  </main>

  <script>
    // تابع نمایش بخش مورد نظر و بستن منوهای کشویی
    function showSection(sectionId) {
      // نمایش تنها بخش انتخاب شده
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.toggle('active', section.id === sectionId);
      });
      // بستن تمام منوهای فعال (کشویی)
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
    }

    // افزودن رویداد کلیک به موارد دارای data-target
    document.querySelectorAll('[data-target]').forEach(item => {
      item.addEventListener('click', function(e) {
        e.stopPropagation();
        const targetId = this.getAttribute('data-target');
        showSection(targetId);
      });
    });

    // مدیریت منوهای کشویی برای منوهایی که دارای زیر منو هستند
    document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
      item.addEventListener('click', function(e) {
        e.stopPropagation();
        // تغییر وضعیت این منو (باز/بسته)
        this.classList.toggle('active');
      });
    });

    // جلوگیری از بستن منو هنگام کلیک روی زیر منو
    document.querySelectorAll('.submenu, .submenu-item').forEach(element => {
      element.addEventListener('click', e => e.stopPropagation());
    });

    // اسکریپت خروج از سیستم
    document.getElementById('logoutBtn').addEventListener('click', function() {
      localStorage.removeItem('authToken');
      localStorage.removeItem('userData');
      sessionStorage.clear();
      alert('با موفقیت از سیستم خارج شدید');
      setTimeout(() => {
        window.location.href = 'index.html';
      }, 100);
    });

    // پردازش فرم افزودن کاربر (نمونه)
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // در اینجا می‌توانید اطلاعات فرم را به سرور ارسال کنید.
      alert('کاربر جدید اضافه شد!');
      this.reset();
      // پس از افزودن کاربر، به لیست کاربران بروید
      showSection('userList');
    });
    
      // مدیریت حذف خبر
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if(confirm('آیا از حذف این خبر مطمئن هستید؟')) {
                const row = this.closest('tr');
                row.remove();
                showToast('خبر با موفقیت حذف شد', 'success');
            }
        });
    });
    document.getElementById('edit').addEventListener('click', function() {
                // آدرس صفحه مقصد را اینجا وارد کنید
             window.location.href = '/edit.html';
        });
        // مدیریت دسته‌بندی‌ها
let currentEditCategoryId = null;

function openEditModal(button) {
  const row = button.closest('tr');
  currentEditCategoryId = row.dataset.categoryId;
  const categoryName = row.cells[1].innerText;
  
  document.getElementById('editCategoryName').value = categoryName;
  document.getElementById('editCategoryModal').style.display = 'block';
}

function closeEditModal() {
  document.getElementById('editCategoryModal').style.display = 'none';
  currentEditCategoryId = null;
}

function handleCategoryUpdate(e) {
  e.preventDefault();
  const newName = document.getElementById('editCategoryName').value.trim();
  
  if (!newName) {
    showToast('لطفا عنوان جدید را وارد کنید', 'error');
    return;
  }

  const row = document.querySelector(`tr[data-category-id="${currentEditCategoryId}"]`);
  row.cells[1].innerText = newName;
  
  closeEditModal();
  showToast('تغییرات با موفقیت ذخیره شد', 'success');
}

function deleteCategory(button) {
  if (!confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟')) return;

  const row = button.closest('tr');
  row.remove();
  showToast('دسته‌بندی با موفقیت حذف شد', 'success');
}

// مدیریت کلیک خارج از مودال
window.onclick = function(e) {
  const modal = document.getElementById('editCategoryModal');
  if (e.target === modal) {
    closeEditModal();
  }
}

// نمایش اعلان
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.remove();
  }, 3000);
}
  </script>

</body>
</html>
