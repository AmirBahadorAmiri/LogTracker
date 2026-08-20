<?php
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$active_tab = 'login'; // Default to login tab

// --- Registration Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $active_tab = 'register';
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $captcha = $_POST['captcha'];

    if (!isset($_SESSION['captcha']) || strtolower($captcha) != strtolower($_SESSION['captcha'])) {
        $error = 'کد امنیتی اشتباه است.';
    } elseif (strlen($username) < 3 || strlen($password) < 6) {
        $error = 'نام کاربری باید حداقل ۳ کاراکتر و رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed, $email]);
            $success = 'ثبت‌نام با موفقیت انجام شد. اکنون می‌توانید وارد شوید.';
            $active_tab = 'login'; // Switch to login tab on success
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = 'این نام کاربری یا ایمیل قبلاً استفاده شده است.';
            } else {
                $error = 'خطایی در هنگام ثبت‌نام رخ داد. لطفاً دوباره تلاش کنید.';
            }
        }
    }
    unset($_SESSION['captcha']);
}

// --- Login Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $active_tab = 'login';
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    if (!isset($_SESSION['captcha']) || strtolower($captcha) != strtolower($_SESSION['captcha'])) {
        $error = 'کد امنیتی اشتباه است.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('dashboard.php');
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است.';
        }
    }
    unset($_SESSION['captcha']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogStream - ورود / ثبت‌نام</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f4f7f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Vazirmatn', sans-serif;
        }
        .auth-card {
            max-width: 450px;
            width: 100%;
        }
        .captcha-img {
            cursor: pointer;
            border-radius: .25rem;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-dark text-white text-center py-3">
            <h1 class="h4 mb-0"><i class="fas fa-stream"></i> LogStream</h1>
        </div>
        <div class="card-body p-4">
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= escape($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>

            <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'login' ? 'active' : '' ?>" id="pills-login-tab" data-bs-toggle="pill" data-bs-target="#pills-login" type="button" role="tab">ورود</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'register' ? 'active' : '' ?>" id="pills-register-tab" data-bs-toggle="pill" data-bs-target="#pills-register" type="button" role="tab">ثبت‌نام</button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <!-- Login Form -->
                <div class="tab-pane fade <?= $active_tab === 'login' ? 'show active' : '' ?>" id="pills-login" role="tabpanel">
                    <form method="post">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="login_username" name="username" placeholder="نام کاربری" required>
                            <label for="login_username">نام کاربری</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="login_password" name="password" placeholder="رمز عبور" required>
                            <label for="login_password">رمز عبور</label>
                        </div>
                        <div class="mb-3">
                            <label for="login_captcha" class="form-label">کد امنیتی</label>
                            <div class="input-group">
                                <img src="captcha.php" alt="کپچا" id="login_captcha_img" class="captcha-img" onclick="this.src='captcha.php?'+Math.random()">
                                <input type="text" class="form-control" id="login_captcha" name="captcha" placeholder="کد را وارد کنید" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> ورود</button>
                        </div>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="tab-pane fade <?= $active_tab === 'register' ? 'show active' : '' ?>" id="pills-register" role="tabpanel">
                    <form method="post">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="register_username" name="username" placeholder="نام کاربری" required>
                            <label for="register_username">نام کاربری</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="register_password" name="password" placeholder="رمز عبور" required>
                            <label for="register_password">رمز عبور (حداقل ۶ کاراکتر)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="register_email" name="email" placeholder="ایمیل (اختیاری)">
                            <label for="register_email">ایمیل (اختیاری)</label>
                        </div>
                        <div class="mb-3">
                            <label for="register_captcha" class="form-label">کد امنیتی</label>
                            <div class="input-group">
                                <img src="captcha.php" alt="کپچا" id="register_captcha_img" class="captcha-img" onclick="this.src='captcha.php?'+Math.random()">
                                <input type="text" class="form-control" id="register_captcha" name="captcha" placeholder="کد را وارد کنید" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="register" class="btn btn-success btn-lg"><i class="fas fa-user-plus"></i> ثبت‌نام</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>