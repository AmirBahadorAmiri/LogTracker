<?php
require_once 'auth.php';
$user = currentUser($pdo);
$error = '';
$success = '';

// --- FORM SUBMISSION LOGIC (UNCHANGED) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_username'])) {
        $new_username = trim($_POST['username']);
        if (strlen($new_username) >= 3) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$new_username, $_SESSION['user_id']]);
                $_SESSION['username'] = $new_username;
                $success = 'نام کاربری با موفقیت به‌روز شد.';
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) { $error = 'این نام کاربری قبلاً انتخاب شده است.'; } 
                else { $error = 'خطا در به‌روزرسانی نام کاربری.'; }
            }
        } else { $error = 'نام کاربری باید حداقل ۳ کاراکتر باشد.'; }
    }

    if (isset($_POST['update_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($old, $row['password'])) { $error = 'رمز عبور فعلی شما صحیح نیست.'; } 
        elseif (strlen($new) < 6) { $error = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.'; } 
        elseif ($new !== $confirm) { $error = 'تکرار رمز عبور جدید مطابقت ندارد.'; } 
        else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $success = 'رمز عبور با موفقیت تغییر یافت.';
        }
    }

    if (isset($_POST['update_email'])) {
        $new_email = trim($_POST['email']);
        if (empty($new_email) || filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$new_email, $_SESSION['user_id']]);
                $success = 'ایمیل با موفقیت به‌روز شد.';
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) { $error = 'این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.'; } 
                else { $error = 'خطا در به‌روزرسانی ایمیل.'; }
            }
        } else { $error = 'فرمت ایمیل وارد شده نامعتبر است.'; }
    }
    // Refresh user data after updates
    $user = currentUser($pdo);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پروفایل کاربری - LogStream</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg bg-light fixed-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-stream"></i> LogStream</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-arrow-left"></i> بازگشت به داشبورد</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container" style="padding-top: 80px;">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-user-edit"></i> پروفایل کاربری: <?= escape($user['username']) ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Update Username -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-user"></i> تغییر نام کاربری</h5></div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">نام کاربری جدید</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= escape($user['username']) ?>" required>
                        </div>
                        <button type="submit" name="update_username" class="btn btn-primary"><i class="fas fa-save"></i> به‌روزرسانی</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Update Email -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-envelope"></i> تغییر ایمیل</h5></div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">ایمیل جدید</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= escape($user['email']) ?>" placeholder="ایمیل جدید (اختیاری)">
                        </div>
                        <button type="submit" name="update_email" class="btn btn-primary"><i class="fas fa-save"></i> به‌روزرسانی</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Password -->
    <div class="row justify-content-center">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-key"></i> تغییر رمز عبور</h5></div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="old_password" class="form-label">رمز عبور فعلی</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">رمز عبور جدید</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="حداقل ۶ کاراکتر" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">تکرار رمز عبور جدید</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-warning"><i class="fas fa-sync-alt"></i> تغییر رمز</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>