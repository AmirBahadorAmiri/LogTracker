<?php
require_once 'auth.php';
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
                $success = 'نام کاربری به‌روز شد';
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $error = 'این نام کاربری قبلاً ثبت شده است';
                } else {
                    $error = 'خطا: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'نام کاربری حداقل ۳ کاراکتر باشد';
        }
    }

    // تغییر رمز عبور
    if (isset($_POST['update_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        // بررسی رمز فعلی
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if (!password_verify($old, $row['password'])) {
            $error = 'رمز فعلی اشتباه است';
        } elseif (strlen($new) < 6) {
            $error = 'رمز جدید حداقل ۶ کاراکتر باشد';
        } elseif ($new !== $confirm) {
            $error = 'تکرار رمز مطابقت ندارد';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $success = 'رمز با موفقیت تغییر کرد';
        }
    }

    // تغییر ایمیل
    if (isset($_POST['update_email'])) {
        $new_email = trim($_POST['email']);
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$new_email, $_SESSION['user_id']]);
                $success = 'ایمیل به‌روز شد';
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $error = 'این ایمیل قبلاً ثبت شده است';
                } else {
                    $error = 'خطا: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'ایمیل نامعتبر است';
        }
    }
}

// دریافت اطلاعات مجدد برای نمایش
$user = currentUser($pdo);
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>پروفایل کاربر</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>پروفایل <?= escape($user['username']) ?></h1>
    <?php if ($error): ?>
        <div class="alert error"><?= escape($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert success"><?= escape($success) ?></div>
    <?php endif; ?>

    <div class="profile-sections">
        <!-- تغییر نام کاربری -->
        <div class="section">
            <h3>تغییر نام کاربری</h3>
            <form method="post">
                <input type="text" name="username" value="<?= escape($user['username']) ?>" required>
                <button type="submit" name="update_username">به‌روزرسانی</button>
            </form>
        </div>

        <!-- تغییر رمز عبور -->
        <div class="section">
            <h3>تغییر رمز عبور</h3>
            <form method="post">
                <input type="password" name="old_password" placeholder="رمز فعلی" required>
                <input type="password" name="new_password" placeholder="رمز جدید (حداقل ۶)" required>
                <input type="password" name="confirm_password" placeholder="تکرار رمز جدید" required>
                <button type="submit" name="update_password">تغییر رمز</button>
            </form>
        </div>

        <!-- تغییر ایمیل -->
        <div class="section">
            <h3>تغییر ایمیل</h3>
            <form method="post">
                <input type="email" name="email" value="<?= escape($user['email']) ?>">
                <button type="submit" name="update_email">به‌روزرسانی</button>
            </form>
        </div>
    </div>

    <p><a href="dashboard.php">بازگشت به داشبورد</a></p>
</div>
</body>
</html>