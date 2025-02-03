<?php
// admin.php

session_start();

// فرض کنید پس از ورود، اطلاعات کاربر در آرایه‌ی $_SESSION['user'] ذخیره شده است
// مثال:
// $_SESSION['user'] = [
//   'id'       => 1,
//   'username' => 'admin',
//   'role'     => 'admin', // یا 'writer'
//   // ...
// ];

// در صورتی که کاربر وارد نشده باشد، به صفحه ورود هدایت شود
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// اتصال به پایگاه داده با PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=news_sit;charset=utf8", "db_username", "db_password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("خطا در اتصال به پایگاه داده: " . $e->getMessage());
}

// نمونه کوئری inner join جهت دریافت اخبار به همراه نام نویسنده (برای بخش اخبار مدیریت)
$stmt = $pdo->prepare("SELECT a.*, u.username AS author_name 
                       FROM articles a 
                       INNER JOIN users u ON a.author_id = u.id
                       ORDER BY a.creat_at DESC");
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ذخیره نقش کاربر
$userRole = $_SESSION['user']['role'];
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
    .main-content { margin-right: 250px; padding: 20px; }
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
      <!-- منو پیشخوان -->
      <li class="nav-item" data-target="dashboard">🧰 پیشخوان</li>
      
      <?php if ($userRole === 'admin') : ?>
      <!-- منو کاربران فقط برای ادمین -->
      <li class="nav-item has-submenu">
        👥 کاربران
        <ul class="submenu">
          <li class="submenu-item" data-target="userList">لیست کاربران</li>
          <li class="submenu-item" data-target="addUser">افزودن کاربر</li>
        </ul>
      </li>
      <?php endif; ?>

      <?php if ($userRole === 'admin') : ?>
      <!-- منو اخبار با قابلیت مدیریت کامل برای ادمین -->
      <li class="nav-item has-submenu">
        📝 اخبار
        <ul class="submenu">
          <li class="submenu-item" data-target="newsList">همه اخبار</li>
          <li class="submenu-item" data-target="addNews">افزودن خبر جدید</li>
        </ul>
      </li>
      <!-- منو دسته‌بندی فقط برای ادمین -->
      <li class="nav-item has-submenu">
        📎 دسته بندی
        <ul class="submenu">
          <li class="submenu-item" data-target="categoryList">همه دسته بندی‌ها</li>
          <li class="submenu-item" data-target="addCategory">افزودن دسته بندی جدید</li>
        </ul>
      </li>
      <!-- منو آمار فقط برای ادمین -->
      <li class="nav-item" data-target="statistics">📊 آمار</li>
      <?php else: ?>
      <!-- نویسنده فقط به افزودن خبر و پیشخوان دسترسی دارد -->
      <li class="nav-item" data-target="addNews">📝 افزودن خبر</li>
      <?php endif; ?>
    </ul>
  </aside>

  <!-- محتوای اصلی -->
  <main class="main-content">
    <!-- هدر -->
    <header class="admin-header">
      <h1>پنل مدیریت</h1>
      <button class="btn btn-primary" id="logoutBtn">خروج از سیستم</button>
    </header>

    <!-- container for dynamic content -->
    <div id="content">
      <!-- داشبورد -->
      <div id="dashboard" class="content-section active">
        <div class="card">
          <h2>اطلاعات مدیر</h2>
          <p><strong>نام:</strong> <?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
          <p><strong>ایمیل:</strong> <?php // فرض کنید ایمیل کاربر نیز در سشن ذخیره شده است
            echo isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : 'نامشخص'; ?></p>
          <p><strong>نقش:</strong> <?php echo $userRole === 'admin' ? 'ادمین' : 'نویسنده'; ?></p>
        </div>
      </div>

      <?php if ($userRole === 'admin') : ?>
      <!-- لیست کاربران (فقط برای ادمین) -->
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
              <?php
              // نمونه دریافت کاربران (در دنیای واقعی این کوئری باید با امنیت کامل انجام شود)
              $stmtUsers = $pdo->query("SELECT * FROM users ORDER BY creat_at DESC");
              while ($user = $stmtUsers->fetch(PDO::FETCH_ASSOC)):
              ?>
              <tr>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo $user['role'] === 'admin' ? 'ادمین' : 'نویسنده'; ?></td>
                <td>
                  <button class="btn btn-primary">ویرایش</button>
                  <button class="btn btn-danger">حذف</button>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- فرم افزودن کاربر (فقط برای ادمین) -->
      <div id="addUser" class="content-section">
        <h2>افزودن کاربر جدید</h2>
        <div class="card">
          <form id="addUserForm" method="post" action="process_user.php">
            <div class="form-group">
              <label for="username">نام کاربری:</label>
              <input type="text" id="username" name="username" placeholder="مثلاً ali123" required>
            </div>
            <div class="form-group">
              <label for="email">ایمیل:</label>
              <input type="email" id="email" name="email" placeholder="example@mail.com" required>
            </div>
            <div class="form-group">
              <label for="role">نقش:</label>
              <select id="role" name="role">
                <option value="writer">نویسنده</option>
                <option value="admin">ادمین</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">افزودن کاربر</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <!-- بخش آمار (فقط برای ادمین) -->
      <?php if ($userRole === 'admin') : ?>
      <div id="statistics" class="content-section">
        <h2>آمار سیستم</h2>
        <div class="card">
          <h3>آمار سریع</h3>
          <p><strong>بازدید امروز:</strong> ۲,۵۶۰</p>
          <p><strong>کاربران جدید:</strong> ۱۲۳</p>
          <p><strong>نرخ تعامل:</strong> ۸۹%</p>
        </div>
      </div>
      <?php endif; ?>

      <!-- بخش اخبار -->
      <?php if ($userRole === 'admin') : ?>
      <div id="newsList" class="content-section">
        <h2>مدیریت همه اخبار</h2>
        <div class="card">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>عنوان</th>
                  <th>نویسنده</th>
                  <th>تاریخ انتشار</th>
                  <th>دسته‌بندی</th>
                  <th>عملیات</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($articles as $article) : ?>
                <tr>
                  <td><?php echo htmlspecialchars($article['title']); ?></td>
                  <td><?php echo htmlspecialchars($article['author_name']); ?></td>
                  <td><?php echo htmlspecialchars($article['published_at']); ?></td>
                  <td><?php echo htmlspecialchars($article['category']); ?></td>
                  <td>
                    <button class="btn btn-primary btn-edit">ویرایش</button>
                    <button class="btn btn-danger btn-delete">حذف</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- فرم افزودن یا ویرایش خبر (برای هر دو نقش: ادمین و نویسنده) -->
      <div id="addNews" class="content-section">
        <h2><?php echo $userRole === 'admin' ? 'افزودن خبر جدید' : 'ایجاد خبر جدید'; ?></h2>
        <div class="card">
          <form id="addNewsForm" method="post" action="process_article.php" enctype="multipart/form-data">
            <div class="form-group">
              <label>عنوان خبر</label>
              <input type="text" 
                    id="newsTitle" 
                    name="title"
                    required
                    maxlength="120"
                    placeholder="عنوان خبر را وارد کنید">
              <div class="char-counter"><span id="titleCounter">0</span>/120</div>
            </div>

            <div class="form-group">
              <label>توضیح یک خطی</label>
              <input type="text" 
                    id="newsSummary" 
                    name="summary"
                    required
                    maxlength="150"
                    placeholder="خلاصه خبر در یک جمله">
              <div class="char-counter"><span id="summaryCounter">0</span>/150</div>
            </div>

            <div class="form-group">
              <label>متن کامل خبر</label>
              <div class="editor-wrapper">
                <!-- فرض کنید از یک ویرایشگر WYSIWYG مانند TinyMCE یا CKEditor استفاده می‌کنید -->
                <textarea id="newsContent" name="content" rows="8" placeholder="متن خبر را وارد کنید"></textarea>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label>دسته‌بندی</label>
                <select id="newsCategory" name="category" required>
                  <option value="">انتخاب دسته‌بندی</option>
                  <?php
                  // نمونه بازیابی دسته‌بندی‌ها از پایگاه داده
                  $stmtCat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                  while ($cat = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
                      echo '<option value="'.htmlspecialchars($cat['name']).'">'.htmlspecialchars($cat['name']).'</option>';
                  }
                  ?>
                </select>
              </div>

              <div class="form-group">
                <label>نویسنده</label>
                <!-- برای نویسنده، نام کاربری از سشن خوانده می‌شود؛ برای ادمین امکان تغییر دارد -->
                <?php if ($userRole === 'admin') : ?>
                <input type="text" id="newsAuthor" name="author" required placeholder="نام نویسنده">
                <?php else: ?>
                <input type="text" id="newsAuthor" name="author" value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>" readonly>
                <?php endif; ?>
              </div>

              <div class="form-group">
                <label>تاریخ انتشار</label>
                <input type="datetime-local" id="publishDate" name="published_at" required>
              </div>
            </div>

            <div class="form-group">
              <label>تصویر شاخص</label>
              <div class="image-uploader" id="uploadContainer">
                <input type="file" id="newsImage" name="featured_image" accept="image/*" hidden>
                <img src="" class="image-preview" alt="پیش‌نمایش تصویر">
                <p>برای آپلود کلیک کنید یا تصویر را اینجا بکشید</p>
                <small>فرمت‌های مجاز: JPG, PNG (حداکثر 2MB)</small>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary"><?php echo $userRole === 'admin' ? 'انتشار خبر' : 'ارسال خبر برای بررسی'; ?></button>
              <button type="button" class="btn btn-secondary">ذخیره پیش‌نویس</button>
            </div>
          </form>
        </div>
      </div>

      <?php if ($userRole === 'admin') : ?>
      <!-- دسته‌بندی (فقط برای ادمین) -->
      <div id="categoryList" class="content-section">
        <div class="card">
          <div class="admin-header">
            <h2>لیست دسته‌بندی‌ها</h2>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>عنوان دسته‌بندی</th>
                  <th>تعداد اخبار</th>
                  <th>تاریخ ایجاد</th>
                  <th>عملیات</th>
                </tr>
              </thead>
              <tbody id="categoriesBody">
                <?php
                // نمونه دریافت دسته‌بندی‌ها از پایگاه داده
                $stmtCatList = $pdo->query("SELECT * FROM categories ORDER BY creat_at DESC");
                while ($cat = $stmtCatList->fetch(PDO::FETCH_ASSOC)):
                ?>
                <tr data-category-id="<?php echo $cat['id']; ?>">
                  <td><?php echo $cat['id']; ?></td>
                  <td><?php echo htmlspecialchars($cat['name']); ?></td>
                  <td><?php echo $cat['article_count'] ?? 0; ?></td>
                  <td><?php echo htmlspecialchars($cat['creat_at']); ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-primary btn-sm" onclick="openEditModal(this)">ویرایش</button>
                      <button class="btn btn-danger btn-sm" onclick="deleteCategory(this)">حذف</button>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- فرم افزودن دسته‌بندی -->
      <div id="addCategory" class="content-section">
        <h2>افزودن دسته بندی جدید</h2>
        <div class="card">
          <form id="addCategoryForm" method="post" action="process_category.php">
            <div class="form-group">
              <label for="categoryName">نام دسته بندی:</label>
              <input type="text" 
                     id="categoryName" 
                     name="name" 
                     placeholder="مثال: اخبار فناوری"
                     required
                     maxlength="50">
            </div>
            <button type="submit" class="btn btn-primary">ثبت دسته بندی</button>
          </form>
        </div>
      </div>

      <!-- مودال ویرایش دسته‌بندی -->
      <div id="editCategoryModal" class="modal" style="display:none;">
        <div class="modal-content">
          <span class="close" onclick="closeEditModal()">&times;</span>
          <h3>ویرایش دسته‌بندی</h3>
          <form id="editCategoryForm" onsubmit="handleCategoryUpdate(event)">
            <div class="form-group">
              <label>عنوان جدید:</label>
              <input type="text" id="editCategoryName" class="form-control" required>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
              <button type="button" class="btn btn-secondary" onclick="closeEditModal()">انصراف</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

    </div> <!-- پایان div#content -->
  </main>

  <script>
    // تابع نمایش بخش مورد نظر و بستن منوهای کشویی
    function showSection(sectionId) {
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.toggle('active', section.id === sectionId);
      });
      // بستن منوهای فعال
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

    // مدیریت منوهای کشویی
    document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
      item.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('active');
      });
    });

    // اسکریپت خروج از سیستم
    document.getElementById('logoutBtn').addEventListener('click', function() {
      // حذف اطلاعات سشن (در صورت استفاده از سشن)
      window.location.href = 'logout.php';
    });

    // نمایش اعلان (Toast)
    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.remove();
      }, 3000);
    }

    // مدیریت دسته‌بندی‌ها (ویرایش و حذف)
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
      // در اینجا باید از AJAX یا ارسال فرم برای به‌روزرسانی دسته‌بندی در سرور استفاده شود
      // به عنوان نمونه، تغییر عنوان در جدول را به‌صورت موقت انجام می‌دهیم:
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

    // جلوگیری از بستن مودال هنگام کلیک خارج از آن
    window.onclick = function(e) {
      const modal = document.getElementById('editCategoryModal');
      if (e.target === modal) {
        closeEditModal();
      }
    };
  </script>
</body>
</html>
