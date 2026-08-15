<?php
require_once '../auth.php';

if (isset($_GET['delete'])) {
    $app_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT id FROM apps WHERE id = ? AND user_id = ?");
    $stmt->execute([$app_id, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        $log_del_stmt = $pdo->prepare("DELETE FROM logs WHERE app_id = ?");
        $log_del_stmt->execute([$app_id]);
        
        $app_del_stmt = $pdo->prepare("DELETE FROM apps WHERE id = ?");
        $app_del_stmt->execute([$app_id]);
    }
    redirect('dashboard.php');
}

if (isset($_GET['edit'])) {
    $app_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id = ? AND user_id = ?");
    $stmt->execute([$app_id, $_SESSION['user_id']]);
    $app = $stmt->fetch();
    if (!$app) {
        redirect('dashboard.php');
    }

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_app'])) {
        $new_name = trim($_POST['app_name']);
        if (!empty($new_name)) {
            $stmt = $pdo->prepare("UPDATE apps SET app_name = ? WHERE id = ?");
            $stmt->execute([$new_name, $app_id]);
            $success = 'نام اپلیکیشن با موفقیت به‌روز شد.';
            $app['app_name'] = $new_name;
        } else {
            $error = 'نام اپلیکیشن نمی‌تواند خالی باشد.';
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ویرایش اپلیکیشن - LogStream</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
    <div class="container">
        <header>
            <h2><i class="fas fa-edit"></i> ویرایش اپلیکیشن: <?= escape($app['app_name']) ?></h2>
            <nav class="nav">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> بازگشت به داشبورد</a>
            </nav>
        </header>

        <?php if ($error): ?>
            <div class="alert error"><?= escape($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= escape($success) ?></div>
        <?php endif; ?>

        <section class="profile-section">
            <h4><i class="fas fa-pen"></i> تغییر نام اپلیکیشن</h4>
            <form method="post">
                <input type="text" name="app_name" value="<?= escape($app['app_name']) ?>" required placeholder="نام جدید اپلیکیشن">
                <button type="submit" name="update_app"><i class="fas fa-save"></i> به‌روزرسانی</button>
            </form>
        </section>
    </div>
    </body>
    </html>
    <?php
    exit;
}

redirect('dashboard.php');
?>