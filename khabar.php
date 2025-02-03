<?php
ob_start(); // شروع بافر خروجی

// شروع session
session_start();

// فعال‌سازی نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'news_site';
$username = 'root';
$password = '';

try {
    // اتصال به پایگاه داده با استفاده از PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // تنظیم حالت خطا
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// بررسی اینکه آیا شناسه مقاله در URL موجود است یا خیر
if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];

    // دریافت اطلاعات مقاله با استفاده از slug
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? AND ispublished = 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();

    if ($article) {
        // افزایش تعداد بازدید
        $updateViews = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $updateViews->execute([$article['id']]);

        // دریافت اطلاعات نویسنده
        $authorStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $authorStmt->execute([$article['author_id']]);
        $author = $authorStmt->fetch();

        // دریافت اخبار مرتبط (بر اساس دسته‌بندی)
        $relatedStmt = $pdo->prepare("SELECT * FROM articles WHERE category = ? AND id != ? AND ispublished = 1 LIMIT 5");
        $relatedStmt->execute([$article['category'], $article['id']]);
        $relatedArticles = $relatedStmt->fetchAll();

    } else {
        // اگر مقاله‌ای با این شناسه پیدا نشد
        die("مقاله مورد نظر یافت نشد.");
    }
} else {
    // اگر شناسه مقاله در URL نباشد
    die("مقاله مورد نظر مشخص نشده است.");
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> | خبرگزاری پیشرو</title>
    <link rel="stylesheet" href="khabar.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <ul class="nav-links">
                <a href="#" class="logo">خبرگزاری</a>
                <li class="dropdown">
                    <a href="#news">اخبار ▼</a>
                    <div class="dropdown-content">
                        <a href="#political">سیاسی</a>
                        <a href="#cultural">فرهنگی</a>
                        <a href="#economy">اقتصادی</a>
                        <a href="#sports">ورزشی</a>
                        <a href="#technology">فناوری</a>
                    </div>
                </li>
                <li><a href="#contact">ارتباط با ما</a></li>
                <li><a href="#about">درباره ما</a></li>
            </ul>
            <button class="login-btn" id="loginButton">
                <?php if (isset($_SESSION['username'])): ?>
                    خوش آمدید، <?= htmlspecialchars($_SESSION['username']) ?>!
                <?php else: ?>
                    ورود / ثبت‌نام
                <?php endif; ?>
            </button>
        </nav>
    </header>

    <main class="article-container">
        <header class="article-header">
            <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
            <div class="article-meta">
                <time><?= date('Y/m/d - H:i', strtotime($article['published_at'])) ?></time>
                <span>نویسنده: <?= htmlspecialchars($author['username']) ?></span>
                <span>دسته‌بندی: <?= htmlspecialchars($article['category']) ?></span>
            </div>
        </header>

        <div class="article-body">
            <div class="featured-media">
                <?php if (!empty($article['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="featured-image">
                <?php endif; ?>
                <?php if (!empty($article['image_caption'])): ?>
                    <div class="media-caption"><?= htmlspecialchars($article['image_caption']) ?></div>
                <?php endif; ?>
            </div>

            <article class="article-content">
                <p class="lead"><?= htmlspecialchars($article['summary']) ?></p>

                <!-- محتوای اصلی مقاله -->
                <?= $article['content'] ?>

            </article>
        </div>

        <aside class="sidebar">
            <div class="related-news">
                <h3>اخبار مرتبط</h3>
                <?php foreach ($relatedArticles as $related): ?>
                    <div class="related-item">
                        <?php if (!empty($related['featured_image'])): ?>
                            <img src="<?= htmlspecialchars($related['featured_image']) ?>" alt="<?= htmlspecialchars($related['title']) ?>" width="80">
                        <?php endif; ?>
                        <div>
                            <h4><a href="article.php?slug=<?= htmlspecialchars($related['slug']) ?>"><?= htmlspecialchars($related['title']) ?></a></h4>
                            <span><?= date('Y/m/d', strtotime($related['published_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <a href="#" class="read-more">موارد بیشتر</a>
            </div>
        </aside>
    </main>

    <footer class="footer">
        <!-- محتوای فوتر -->
        <div class="footer-container">
            <div class="footer-section">
                <h4 class="footer-heading">درباره ما</h4>
                <p>خبرگزاری در ارائه آخرین اخبار روز به صورت لحظه‌ای و معتبر</p>
            </div>
          
            <div class="footer-section">
                <h4 class="footer-heading">ارتباط با ما</h4>
                <ul class="footer-links">
                    <li>تلفن: ۱۲۳۴۵-۶۷۸۹۰</li>
                    <li>آدرس: بیرجند، دانشگاه بیرجند</li>
                    <li>ایمیل: info@example.com</li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-heading">شبکه‌های اجتماعی</h4>
                <div class="social-links">
                    <a href="#" aria-label="تلگرام"><img width="48" height="48" src="https://img.icons8.com/fluency/48/telegram-app.png" alt="telegram-app"/></a>
                    <a href="#" aria-label="اینستاگرام"><img width="48" height="48" src="https://img.icons8.com/color/48/instagram-new--v1.png" alt="instagram-new--v1"/></a>
                    <a href="#" aria-label="توییتر"><img width="48" height="48" src="https://img.icons8.com/ios-filled/50/twitterx--v1.png" alt="twitterx--v1"/></a>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            © ۱۴۰۳ کلیه حقوق برای خبرگزاری محفوظ است
        </div>
    </footer>

    <script>
        document.getElementById('loginButton').addEventListener('click', function() {
            <?php if (isset($_SESSION['username'])): ?>
                window.location.href = 'profile.php';
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php
ob_end_flush(); // ارسال و پاکسازی بافر خروجی
?>
