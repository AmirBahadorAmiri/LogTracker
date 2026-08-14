<?php
require_once '../auth.php';
$user = currentUser($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تغییر نام کاربری
    if (isset($_POST['update_username'])) {
        $new_username = trim($_POST['username']);
        if (strlen($new_username) >= 3) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$new_username, $_SESSION['user_id']]);
                $_SESSION['username'] = $new_username;
                $success = 'نام کاربری با موفقیت به‌روز شد.';
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $error = 'این نام کاربری قبلاً انتخاب شده است.';
                } else {
                    $error = 'خطا در به‌روزرسانی نام کاربری: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'نام کاربری باید حداقل ۳ کاراکتر باشد.';
        }
    }

    // تغییر رمز عبور
    if (isset($_POST['update_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($old, $row['password'])) {
            $error = 'رمز عبور فعلی شما صحیح نیست.';
        } elseif (strlen($new) < 6) {
            $error = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.';
        } elseif ($new !== $confirm) {
            $error = 'تکرار رمز عبور جدید مطابقت ندارد.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $success = 'رمز عبور با موفقیت تغییر یافت.';
        }
    }

    // تغییر ایمیل
    if (isset($_POST['update_email'])) {
        $new_email = trim($_POST['email']);
        if (empty($new_email) || filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$new_email, $_SESSION['user_id']]);
                $success = 'ایمیل با موفقیت به‌روز شد.';
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $error = 'این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.';
                } else {
                    $error = 'خطا در به‌روزرسانی ایمیل: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'فرمت ایمیل وارد شده نامعتبر است.';
        }
    }
    // Refresh user data after updates
    $user = currentUser($pdo);
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پروفایل کاربری - LogStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h2><i class="fas fa-user-edit"></i> پروفایل کاربری: <?= escape($user['username']) ?></h2>
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

    <div class="profile-sections">
        <!-- تغییر نام کاربری -->
        <section class="profile-section">
            <h4><i class="fas fa-user"></i> تغییر نام کاربری</h4>
            <form method="post">
                <input type="text" name="username" value="<?= escape($user['username']) ?>" required placeholder="نام کاربری جدید">
                <button type="submit" name="update_username"><i class="fas fa-save"></i> به‌روزرسانی</button>
            </form>
        </section>

        <!-- تغییر ایمیل -->
        <section class="profile-section">
            <h4><i class="fas fa-envelope"></i> تغییر ایمیل</h4>
            <form method="post">
                <input type="email" name="email" value="<?= escape($user['email']) ?>" placeholder="ایمیل جدید (اختیاری)">
                <button type="submit" name="update_email"><i class="fas fa-save"></i> به‌روزرسانی</button>
            </form>
        </section>

        <!-- تغییر رمز عبور -->
        <section class="profile-section password-form">
            <h4><i class="fas fa-key"></i> تغییر رمز عبور</h4>
            <form method="post">
                <input type="password" name="old_password" placeholder="رمز عبور فعلی" required>
                <input type="password" name="new_password" placeholder="رمز عبور جدید (حداقل ۶ کاراکتر)" required>
                <input type="password" name="confirm_password" placeholder="تکرار رمز جدید" required>
                <button type="submit" name="update_password"><i class="fas fa-sync-alt"></i> تغییر رمز</button>
            </form>
        </section>
    </div>
</div>
</body>
</html>