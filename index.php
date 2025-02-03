<?php
// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اتصال به دیتابیس
$mysqli = new mysqli("localhost", "root", "", "news_site");
if ($mysqli->connect_error) {
    die("خطا در اتصال به دیتابیس: " . $mysqli->connect_error);
}

// تنظیم base_url (در صورت نیاز مسیر را تغییر دهید)
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/news_site/";

// دریافت دسته‌بندی‌ها
$categories = [];
$catQuery = "SELECT DISTINCT category FROM articles ORDER BY category ASC";
if ($catResult = $mysqli->query($catQuery)) {
    while ($catRow = $catResult->fetch_assoc()) {
        $categories[] = $catRow['category'];
    }
    $catResult->free();
} else {
    die("خطا در دریافت دسته‌بندی‌ها: " . $mysqli->error);
}

// دریافت خبر ویژه
$featuredNews = null;
$featuredQuery = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 1";
if ($result = $mysqli->query($featuredQuery)) {
    $featuredNews = $result->fetch_assoc();
    $result->free();
} else {
    die("خطا در دریافت خبر ویژه: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سایت خبری</title>
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
</head>

<body>
    <header class="header">
        <nav class="nav-container">
            <ul class="nav-links">
                <a href="<?= $base_url ?>" class="logo">خبرگزاری</a>
                <!-- منوی Dropdown اخبار به صورت داینامیک از دیتابیس -->
                <li class="dropdown">
                    <a href="#">اخبار ▼</a>
                    <div class="dropdown-content">
                        <?php foreach ($categories as $cat): ?>
                            <a href="#<?= strtolower($cat); ?>"><?= htmlspecialchars($cat); ?></a>
                        <?php endforeach; ?>
                    </div>
                </li>
                <li><a href="#contact">ارتباط با ما</a></li>
                <li><a href="#about">درباره ما</a></li>
            </ul>
            <button class="login-btn" id="loginButton">ورود / ثبت‌نام</button>
        </nav>
    </header>

    <main class="main-content">
        <!-- اسلایدر خبر ویژه -->
        <section class="hero-slider">
            <div class="slider-item">
                <?php if ($featuredNews): ?>
                    <img src="<?= htmlspecialchars($featuredNews['featured_image'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($featuredNews['title'] ?? ''); ?>">
                    <div class="slider-content">
                        <h2><?= htmlspecialchars($featuredNews['title'] ?? ''); ?></h2>
                        <p><?= mb_substr(strip_tags($featuredNews['content'] ?? ''), 0, 150) . '...'; ?></p>
                        <a href="article.php?slug=<?= htmlspecialchars($featuredNews['slug'] ?? ''); ?>" class="read-more">مطالعه بیشتر</a>
                    </div>
                <?php else: ?>
                    <!-- در صورت عدم وجود خبر ویژه -->
                    <img src="breaking1.jpg" alt="خبر ویژه">
                    <div class="slider-content">
                        <h2>عنوان خبر مهم روز</h2>
                        <p>متن خلاصه خبر در این بخش قرار می‌گیرد...</p>
                        <a href="khabar.html" class="read-more">مطالعه بیشتر</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- اسلایدرهای دسته‌بندی -->
        <?php foreach ($categories as $cat): ?>
            <?php
            // کوئری دریافت اخبار مربوط به دسته‌بندی فعلی
            $stmt = $mysqli->prepare("SELECT * FROM articles WHERE category = ? ORDER BY created_at DESC");
            if ($stmt) {
                $stmt->bind_param("s", $cat);
                $stmt->execute();
                $catResult = $stmt->get_result();
            } else {
                die("خطا در آماده‌سازی کوئری: " . $mysqli->error);
            }

            if ($catResult && $catResult->num_rows > 0):
            ?>
            <div class="category-slider" id="<?= strtolower($cat); ?>">
                <h2 class="category-title"><?= htmlspecialchars($cat); ?></h2>
                <div class="slider-container">
                    <button class="slider-nav prev">‹</button>
                    <div class="news-slider">
                        <?php while ($row = $catResult->fetch_assoc()): ?>
                            <article class="news-card">
                                <div class="card-header">
                                    <img src="<?= htmlspecialchars($row['featured_image'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($row['title'] ?? ''); ?>" class="news-image">
                                    <span class="news-category"><?= htmlspecialchars($row['category'] ?? ''); ?></span>
                                </div>
                                <div class="news-content">
                                    <h3 class="news-title"><?= htmlspecialchars($row['title'] ?? ''); ?></h3>
                                    <p class="excerpt"><?= mb_substr(strip_tags($row['content'] ?? ''), 0, 100) . '...'; ?></p>
                                    <div class="card-footer">
                                        <a href="article.php?slug=<?= htmlspecialchars($row['slug'] ?? ''); ?>" class="read-more">مطالعه کامل</a>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    <button class="slider-nav next">›</button>
                </div>
            </div>
            <?php
            endif;
            if (isset($catResult)) {
                $catResult->free();
            }
            $stmt->close();
            ?>
        <?php endforeach; ?>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4 class="footer-heading">درباره ما</h4>
                <p>خبرگزاری در ارائه آخرین اخبار روز<br>به صورت لحظه‌ای و معتبر</p>
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
                    <a href="#" aria-label="تلگرام">
                        <img width="48" height="48" src="https://img.icons8.com/fluency/48/telegram-app.png" alt="تلگرام"/>
                    </a>
                    <a href="#" aria-label="اینستاگرام">
                        <img width="48" height="48" src="https://img.icons8.com/color/48/instagram-new--v1.png" alt="اینستاگرام"/>
                    </a>
                    <a href="#" aria-label="توییتر">
                        <img width="48" height="48" src="https://img.icons8.com/ios-filled/50/twitterx--v1.png" alt="توییتر"/>
                    </a>
                </div>
            </div>
        </div>
        <div class="copyright">
            © ۱۴۰۳ کلیه حقوق برای خبرگزاری محفوظ است
        </div>
    </footer>

    <script>
        // مدیریت اسلایدرها
        document.querySelectorAll('.slider-container').forEach(slider => {
            const next = slider.querySelector('.next');
            const prev = slider.querySelector('.prev');
            const newsSlider = slider.querySelector('.news-slider');

            const slide = (direction) => {
                const cardWidth = slider.querySelector('.news-card').offsetWidth;
                const gap = parseInt(window.getComputedStyle(newsSlider).gap) || 0;
                newsSlider.scrollBy({left: (cardWidth + gap) * direction, behavior: 'smooth'});
            };

            next.addEventListener('click', () => slide(1));
            prev.addEventListener('click', () => slide(-1));

            // اسکرول با ماوس
            let isDown = false;
            let startX;
            let scrollLeft;

            newsSlider.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - newsSlider.offsetLeft;
                scrollLeft = newsSlider.scrollLeft;
            });

            newsSlider.addEventListener('mouseleave', () => isDown = false);
            newsSlider.addEventListener('mouseup', () => isDown = false);
            newsSlider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - newsSlider.offsetLeft;
                const walk = (x - startX);
                newsSlider.scrollLeft = scrollLeft - walk;
            });
        });

        // هدایت به صفحه لاگین
        document.getElementById('loginButton').addEventListener('click', function() {
            window.location.href = '<?= $base_url ?>login.php';
        });
    </script>
</body>
</html>
<?php
$mysqli->close();
?>
