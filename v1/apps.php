<?php
require_once '../auth.php';

if (isset($_GET['delete'])) {
    $app_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id = ? AND user_id = ?");
    $stmt->execute([$app_id, $_SESSION['user_id']]);
    if ($stmt->rowCount()) {
        $stmt = $pdo->prepare("DELETE FROM apps WHERE id = ?");
        $stmt->execute([$app_id]);
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_app'])) {
        $new_name = trim($_POST['app_name']);
        if (!empty($new_name)) {
            $stmt = $pdo->prepare("UPDATE apps SET app_name = ? WHERE id = ?");
            $stmt->execute([$new_name, $app_id]);
            redirect('dashboard.php');
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa">
    <head>
        <meta charset="UTF-8">
        <title>ویرایش اپ</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
    <div class="container">
        <h1>ویرایش اپلیکیشن</h1>
        <form method="post">
            <input type="text" name="app_name" value="<?= escape($app['app_name']) ?>" required>
            <button type="submit" name="update_app">ذخیره</button>
            <a href="dashboard.php">بازگشت</a>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}
?>