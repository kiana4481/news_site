<?php
// اتصال به دیتابیس
$servername = "localhost";
$username = "root"; // نام کاربری دیتابیس
$password = ""; // رمز عبور دیتابیس
$dbname = "news_site"; // نام دیتابیس

// ایجاد اتصال به دیتابیس
$conn = new mysqli($servername, $username, $password, $dbname);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// بررسی ارسال درخواست
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // دریافت داده‌ها از فرم
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $content = $_POST['content']; // متن کامل خبر (ویرایشگر Quill)

    // اجرای دستور SQL برای ذخیره‌سازی در جدول articles
    $stmt = $conn->prepare("INSERT INTO articles (title, content, category, author_id, ispublished, published_at, created_at, updated_at, slug) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");

    // فرض کنید `author_id` 1 باشد (برای مثال)
    $author_id = 1;
    $ispublished = 1; // اگر خبر منتشر شده باشد
    $slug = strtolower(str_replace(" ", "-", $title)); // ایجاد slug از عنوان

    // بایند کردن پارامترها
    $stmt->bind_param("ssssssss", $title, $content, $category, $author_id, $ispublished, $date, $slug);

    // اجرای دستور
    if ($stmt->execute()) {
        echo "خبر با موفقیت ذخیره شد!";
    } else {
        echo "خطا در ذخیره‌سازی: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش خبر | خبرگزاری پیشرو</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="edit.css">
</head>
<body>
    <div class="edit-header">
        <h1>ویرایش خبر</h1>
    </div>

    <div class="edit-container">
        <form class="edit-form" id="editForm" method="POST" action="">
            <div class="form-group">
                <label for="newsTitle">عنوان خبر</label>
                <input type="text" 
                      id="newsTitle" 
                      name="title"
                      class="form-control"
                      value="کشف روش جدید درمان سرطان با استفاده از نانوذرات هوشمند">
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="newsAuthor">نویسنده</label>
                    <input type="text" 
                          id="newsAuthor" 
                          name="author"
                          class="form-control"
                          value="دکتر مریم حسینی">
                </div>

                <div class="form-group">
                    <label for="newsCategory">دسته‌بندی</label>
                    <select id="newsCategory" name="category" class="form-control">
                        <option value="سلامت">سلامت</option>
                        <option value="فناوری">فناوری</option>
                        <option value="اقتصادی">اقتصادی</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="publishDate">تاریخ انتشار</label>
                    <input type="datetime-local" 
                          id="publishDate" 
                          name="date"
                          class="form-control"
                          value="2024-08-15T14:30">
                </div>
            </div>

            <div class="form-group">
                <label>متن کامل خبر</label>
                <div class="editor-wrapper">
                    <div id="editor"></div>
                </div>
                <input type="hidden" name="content" id="contentInput">
            </div>

            <div class="action-buttons">
                <button type="button" class="btn btn-secondary" id="edit_cancel">انصراف</button>
                <button type="submit" class="btn btn-primary" id="edit_save">ذخیره تغییرات</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // تنظیمات ویرایشگر متن
        const quill = new Quill('#editor', {
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            theme: 'snow'
        });

        // مدیریت ارسال فرم
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // ذخیره‌سازی محتوای ویرایشگر
            document.getElementById('contentInput').value = quill.root.innerHTML;

            // ارسال فرم
            this.submit();
        });

        // تنظیم محتوای پیش‌فرض ویرایشگر
        quill.root.innerHTML = ` 
            <p class="lead">پژوهشگران ایرانی موفق به توسعه روشی انقلابی در هدفگیری سلولهای سرطانی با دقت ۹۵ درصدی شدند.</p>
            <div class="highlight-box">
                <p>"این دستاورد علمی می‌تواند تحول عظیمی در صنعت داروسازی و سرطان درمانی ایجاد کند"<br>- دکتر رضا نوروزی، رئیس انجمن سرطان شناسی ایران</p>
            </div>
            <p>این روش مبتنی بر فناوری نانوذرات هوشمند قادر است:</p>
            <p>این پروژه که با همکاری ۱۵ مرکز تحقیقاتی بین‌المللی انجام شده، تاکنون موفق به دریافت ۳ جایزه معتبر جهانی شده است.</p>
        `;
    </script>
</body>
</html>
