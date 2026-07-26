<?php
require_once 'config.php';
require_once 'functions.php';

// اگر لاگین است، به داشبورد برود
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// پردازش ثبت‌نام
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $captcha = $_POST['captcha'];

    // بررسی وجود کپچا در سشن
    if (!isset($_SESSION['captcha']) || $captcha != $_SESSION['captcha']) {
        $error = 'کد امنیتی اشتباه است';
        unset($_SESSION['captcha']); // پاک کردن کپچا
    } elseif (strlen($username) < 3 || strlen($password) < 6) {
        $error = 'نام کاربری حداقل ۳ کاراکتر و رمز حداقل ۶ کاراکتر باشد';
        unset($_SESSION['captcha']);
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed, $email]);
            $success = 'ثبت‌نام موفق، اکنون وارد شوید';
            unset($_SESSION['captcha']); // پاک کردن کپچا بعد از موفقیت
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = 'نام کاربری یا ایمیل قبلاً ثبت شده است';
            } else {
                $error = 'خطا در ثبت‌نام: ' . $e->getMessage();
            }
            unset($_SESSION['captcha']);
        }
    }
}

// پردازش ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    if (!isset($_SESSION['captcha']) || $captcha != $_SESSION['captcha']) {
        $error = 'کد امنیتی اشتباه است';
        unset($_SESSION['captcha']);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['captcha']); // پاک کردن کپچا بعد از موفقیت
            redirect('dashboard.php');
        } else {
            $error = 'نام کاربری یا رمز اشتباه است';
            unset($_SESSION['captcha']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogTracker - ورود / ثبت‌نام</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1><i class="fas fa-chart-line" style="color:#3b82f6;"></i> LogTracker</h1>
    <?php if ($error): ?>
        <div class="alert error"><?= escape($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert success"><?= escape($success) ?></div>
    <?php endif; ?>

    <!-- کپچای یکتا برای کل صفحه -->
    <div class="captcha-wrapper">
        <div class="captcha-box">
            <label>کد امنیتی <i class="fas fa-shield-alt"></i></label>
            <div class="captcha-row">
                <img src="captcha.php" alt="کپچا" id="captcha_img" onclick="this.src='captcha.php?'+Math.random()">
                <input type="text" name="captcha_global" id="captcha_global" placeholder="کد را وارد کنید" required>
                <button type="button" onclick="document.getElementById('captcha_img').src='captcha.php?'+Math.random(); document.getElementById('captcha_global').value='';">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="forms">
        <!-- فرم ورود -->
        <div class="form-box">
            <h2><i class="fas fa-sign-in-alt"></i> ورود</h2>
            <form method="post" onsubmit="document.getElementById('login_captcha').value = document.getElementById('captcha_global').value;">
                <input type="text" name="username" placeholder="نام کاربری" required>
                <input type="password" name="password" placeholder="رمز عبور" required>
                <input type="hidden" name="captcha" id="login_captcha">
                <button type="submit" name="login"><i class="fas fa-arrow-left"></i> ورود</button>
            </form>
        </div>

        <!-- فرم ثبت‌نام -->
        <div class="form-box">
            <h2><i class="fas fa-user-plus"></i> ثبت‌نام</h2>
            <form method="post" onsubmit="document.getElementById('register_captcha').value = document.getElementById('captcha_global').value;">
                <input type="text" name="username" placeholder="نام کاربری" required>
                <input type="password" name="password" placeholder="رمز عبور (حداقل ۶ کاراکتر)" required>
                <input type="email" name="email" placeholder="ایمیل (اختیاری)">
                <input type="hidden" name="captcha" id="register_captcha">
                <button type="submit" name="register"><i class="fas fa-user-check"></i> ثبت‌نام</button>
            </form>
        </div>
    </div>
</div>

<script>
    // وقتی صفحه بارگذاری می‌شود، کپچا را به‌روز کن
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('captcha_img').src = 'captcha.php?' + Math.random();
    });
</script>
</body>
</html>