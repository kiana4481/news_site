<?php
ob_start(); // شروع بافر خروجی

// شروع session
session_start();

// فعال سازی نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'news_site';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$login_error = '';
$register_errors = [];

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = "لطفاً نام کاربری و رمز عبور را وارد کنید.";
    } else {
        // بررسی نام کاربری
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // به‌روزرسانی last_login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // تنظیم متغیرهای session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // نمایش متغیرهای session و توقف برای بررسی
            var_dump($_SESSION);
            exit(); // توقف برای مشاهده خروجی
        } else {
            $login_error = "نام کاربری یا رمز عبور اشتباه است.";
        }
    }
}

// پردازش فرم ثبت‌نام
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $repeat_password = $_POST['repeat_password'] ?? '';

    $errors = [];

    if (empty($username)) $errors[] = "نام کاربری الزامی است.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "ایمیل نامعتبر است.";
    if (strlen($password) < 8) $errors[] = "رمز عبور باید حداقل 8 کاراکتر باشد.";
    if ($password !== $repeat_password) $errors[] = "تکرار رمز عبور مطابقت ندارد.";

    // بررسی تکراری نبودن نام کاربری و ایمیل
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->fetchColumn() > 0) {
        $errors[] = "نام کاربری یا ایمیل قبلا ثبت شده است.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            // درج کاربر جدید
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $role = 'نویسنده'; // می‌توانید نقش را بر اساس نیاز تنظیم کنید
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                // تنظیم متغیرهای session
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $role;

                // نمایش متغیرهای session و توقف برای بررسی
                var_dump($_SESSION);
                exit(); // توقف برای مشاهده خروجی
            } else {
                $register_errors[] = "خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.";
            }
        } catch (PDOException $e) {
            $register_errors[] = "خطا: " . $e->getMessage();
        }
    } else {
        $register_errors = $errors;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود خبرنگاران | خبرگزاری مدرن</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>خبرگزاری</h1>
            <p>ورود به پنل خبرنگاران و نویسندگان</p>
        </div>
        
        <div class="auth-tabs">
            <div class="auth-tab active" data-tab="login">ورود اعضا</div>
            <div class="auth-tab" data-tab="register">ثبت نام جدید</div>
        </div>
        
        <div class="auth-content">
            <!-- فرم ورود -->
            <form class="auth-form active" id="loginForm" method="POST">
                <?php if (!empty($login_error)): ?>
                    <div class="error-message"><?= $login_error ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">نام کاربری</label>
                    <input type="text" class="form-input" placeholder="نام کاربری" required name="username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">رمز عبور</label>
                    <input type="password" class="form-input" placeholder="رمز عبور خود را وارد کنید" required name="password">
                </div>
                
                <button type="submit" class="submit-btn" name="login">
                    ورود به پنل مدیریت
                </button>
            </form>
            
            <!-- فرم ثبت نام -->
            <form class="auth-form" id="registerForm" method="POST">
                <?php if (!empty($register_errors)): ?>
                    <?php foreach ($register_errors as $error): ?>
                        <div class="error-message"><?= $error ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">نام کاربری</label>
                    <input type="text" class="form-input" placeholder="نام کاربری" required name="username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">ایمیل سازمانی</label>
                    <input type="email" class="form-input" placeholder="example@news.com" required name="email">
                </div>
                
                <div class="form-group">
                    <label class="form-label">رمز عبور</label>
                    <input type="password" class="form-input" placeholder="حداقل 8 کاراکتر" required name="password">
                </div>

                <div class="form-group">
                    <label class="form-label">تکرار رمز عبور</label>
                    <input type="password" class="form-input" placeholder="تکرار رمز عبور" required name="repeat_password">
                </div>
                
                <button type="submit" class="submit-btn" name="register">
                    ایجاد حساب جدید
                </button>
            </form>
        </div>
    </div>

    <script>
        // مدیریت تب‌ها
        const tabs = document.querySelectorAll('.auth-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.auth-tab, .auth-form').forEach(el => el.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab + 'Form').classList.add('active');
            });
        });
    </script>
</body>
</html>
<?php
ob_end_flush(); // ارسال و پاکسازی بافر خروجی
?>
