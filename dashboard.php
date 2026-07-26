<?php
require_once 'auth.php';
require_once 'functions.php';

// بررسی مجدد وجود کاربر در دیتابیس (احتیاط)
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    // کاربر وجود ندارد → لاگ‌اوت کن
    session_destroy();
    redirect('index.php');
}

// دریافت لیست اپ‌های کاربر
$stmt = $pdo->prepare("SELECT * FROM apps WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$apps = $stmt->fetchAll();

// پردازش افزودن اپ جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_app'])) {
    $app_name = trim($_POST['app_name']);
    if (!empty($app_name)) {
        $uuid = generateUUID();
        try {
            $stmt = $pdo->prepare("INSERT INTO apps (user_id, app_uuid, app_name) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $uuid, $app_name]);
            redirect('dashboard.php');
        } catch (PDOException $e) {
            // اگر خطای foreign key بود، پیام بده
            if ($e->errorInfo[1] == 1452) {
                $error = 'کاربر نامعتبر. لطفاً دوباره وارد شوید.';
                session_destroy();
                redirect('index.php');
            } else {
                throw $e; // خطای دیگر
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد LogTracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>سلام <?= escape($_SESSION['username']) ?>!</h1>
        <div class="nav">
            <a href="profile.php">پروفایل</a>
            <a href="logout.php">خروج</a>
        </div>
    </header>

    <section class="add-app">
        <h2>افزودن اپلیکیشن جدید</h2>
        <form method="post">
            <input type="text" name="app_name" placeholder="نام اپلیکیشن" required>
            <button type="submit" name="add_app">ایجاد</button>
        </form>
    </section>

    <section class="app-list">
        <h2>اپلیکیشن‌های من</h2>
        <?php if (empty($apps)): ?>
            <p>هیچ اپلیکیشنی ندارید.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($apps as $app): ?>
                    <li>
                        <strong><?= escape($app['app_name']) ?></strong>
                        <span>UUID: <?= escape($app['app_uuid']) ?></span>
                        <div class="actions">
                            <a href="view_app.php?app_uuid=<?= $app['app_uuid'] ?>">مشاهده لاگ‌ها</a>
                            <a href="apps.php?edit=<?= $app['id'] ?>">ویرایش</a>
                            <a href="apps.php?delete=<?= $app['id'] ?>" onclick="return confirm('حذف شود؟')">حذف</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
</body>
</html>