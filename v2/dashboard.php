<?php
require_once '../auth.php';
require_once '../functions.php';

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    session_destroy();
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT * FROM apps WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$apps = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_app'])) {
    $app_name = trim($_POST['app_name']);
    if (!empty($app_name)) {
        $uuid = generateUUID();
        try {
            $stmt = $pdo->prepare("INSERT INTO apps (user_id, app_uuid, app_name) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $uuid, $app_name]);
            redirect('dashboard.php');
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1452) {
                $error = 'کاربر نامعتبر. لطفاً دوباره وارد شوید.';
                session_destroy();
                redirect('index.php');
            } else {
                throw $e;
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
    <title>داشبورد LogStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h2>سلام <?= escape($_SESSION['username']) ?>!</h2>
        <nav class="nav">
            <a href="profile.php"><i class="fas fa-user-circle"></i> پروفایل</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </header>

    <section class="add-app">
        <h3><i class="fas fa-plus-circle"></i> افزودن اپلیکیشن جدید</h3>
        <form method="post">
            <input type="text" name="app_name" placeholder="نام اپلیکیشن را وارد کنید..." required>
            <button type="submit" name="add_app"><i class="fas fa-check"></i> ایجاد</button>
        </form>
    </section>

    <section class="app-list">
        <h3><i class="fas fa-list-ul"></i> اپلیکیشن‌های من</h3>
        <?php if (empty($apps)): ?>
            <p style="text-align: center; padding: 1rem; background: #f9f9f9; border-radius: 4px;">هیچ اپلیکیشنی برای نمایش وجود ندارد. یکی جدید بسازید!</p>
        <?php else: ?>
            <ul>
                <?php foreach ($apps as $app): ?>
                    <li>
                        <strong><?= escape($app['app_name']) ?></strong>
                        <span>UUID: <?= escape($app['app_uuid']) ?></span>
                        <div class="actions">
                            <a href="view_app.php?app_uuid=<?= $app['app_uuid'] ?>" title="مشاهده لاگ‌ها"><i class="fas fa-eye"></i></a>
                            <a href="apps.php?edit=<?= $app['id'] ?>" title="ویرایش"><i class="fas fa-edit"></i></a>
                            <a href="apps.php?delete=<?= $app['id'] ?>" title="حذف" onclick="return confirm('آیا از حذف این اپلیکیشن مطمئن هستید؟')"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
</body>
</html>