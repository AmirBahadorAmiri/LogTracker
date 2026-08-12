<?php
require_once 'auth.php';

$app_uuid = $_GET['app_uuid'] ?? '';
if (!$app_uuid) {
    redirect('dashboard.php');
}

// بررسی دسترسی به اپ
$stmt = $pdo->prepare("SELECT * FROM apps WHERE app_uuid = ? AND user_id = ?");
$stmt->execute([$app_uuid, $_SESSION['user_id']]);
$app = $stmt->fetch();
if (!$app) {
    redirect('dashboard.php');
}

// =============================================
// ✅ بخش حذف لاگ (اضافه شده)
// =============================================
if (isset($_GET['delete_log'])) {
    $log_id = (int)$_GET['delete_log'];

    // اطمینان از اینکه این لاگ متعلق به همین اپلیکیشن است (امنیت)
    $check_stmt = $pdo->prepare("SELECT id FROM logs WHERE id = ? AND app_id = ?");
    $check_stmt->execute([$log_id, $app['id']]);

    if ($check_stmt->rowCount() > 0) {
        // حذف لاگ از دیتابیس
        $del_stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
        $del_stmt->execute([$log_id]);
    }

    // پس از حذف، کاربر را به همان صفحه برمی‌گردانیم (رفرش می‌کنیم)
    // این کار از ارسال مجدد درخواست با رفرش صفحه جلوگیری می‌کند
    redirect("view_app.php?app_uuid=" . urlencode($app_uuid));
}
// =============================================

// دریافت پارامترهای فیلتر
$tag = $_GET['tag'] ?? '';
$message = $_GET['message'] ?? '';
$client = $_GET['client_identifier'] ?? '';
$log_uuid = $_GET['log_uuid'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$perPage = 100;
$offset = ($page - 1) * $perPage;

// ساخت کوئری شرطی برای دریافت لاگ‌ها
$sql = "SELECT * FROM logs WHERE app_id = ?";
$params = [$app['id']];

if ($tag) {
    $sql .= " AND tag LIKE ?";
    $params[] = "%$tag%";
}
if ($message) {
    $sql .= " AND message LIKE ?";
    $params[] = "%$message%";
}
if ($client) {
    $sql .= " AND client_identifier = ?";
    $params[] = $client;
}
if ($log_uuid) {
    $sql .= " AND log_uuid = ?";
    $params[] = $log_uuid;
}
if ($from) {
    $sql .= " AND created_at >= ?";
    $params[] = $from;
}
if ($to) {
    $sql .= " AND created_at <= ?";
    $params[] = $to . ' 23:59:59';
}

// شمارش کل رکوردها
$countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// کوئری اصلی با ORDER و LIMIT
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll();

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لاگ‌های <?= escape($app['app_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h2>لاگ‌های اپ: <?= escape($app['app_name']) ?></h2>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-arrow-right"></i> بازگشت به داشبورد</a>
        </div>
    </header>

    <!-- فرم فیلتر -->
    <section class="filters">
        <form method="get">
            <input type="hidden" name="app_uuid" value="<?= escape($app_uuid) ?>">
            <input type="text" name="tag" placeholder="تگ" value="<?= escape($tag) ?>">
            <input type="text" name="message" placeholder="متن لاگ" value="<?= escape($message) ?>">
            <input type="text" name="client_identifier" placeholder="شناسه کلاینت" value="<?= escape($client) ?>">
            <input type="text" name="log_uuid" placeholder="UUID لاگ" value="<?= escape($log_uuid) ?>">
            <input type="date" name="from" value="<?= escape($from) ?>">
            <input type="date" name="to" value="<?= escape($to) ?>">
            <button type="submit"><i class="fas fa-filter"></i> فیلتر</button>
            <a href="view_app.php?app_uuid=<?= escape($app_uuid) ?>"><i class="fas fa-undo"></i> پاک کردن</a>
        </form>
    </section>

    <!-- نمایش لاگ‌ها -->
    <section class="logs">
        <?php if (empty($logs)): ?>
            <p><i class="fas fa-info-circle"></i> هیچ لاگی یافت نشد.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>UUID لاگ</th>
                    <th>کلاینت</th>
                    <th>تگ</th>
                    <th>پیام</th>
                    <th>IP</th>
                    <th>زمان</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= escape($log['log_uuid']) ?></td>
                        <td><?= escape($log['client_identifier']) ?></td>
                        <td><?= escape($log['tag']) ?></td>
                        <td><?= escape($log['message']) ?></td>
                        <td><?= escape($log['ip_address']) ?></td>
                        <td><?= escape($log['created_at']) ?></td>
                        <td>
                            <a href="view_app.php?delete_log=<?= $log['id'] ?>&app_uuid=<?= escape($app_uuid) ?>"
                               onclick="return confirm('آیا از حذف این لاگ مطمئن هستید؟')"
                               style="color: #141c31;">
                                <i class="fas fa-trash-alt"></i> حذف
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- صفحه‌بندی -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?app_uuid=<?= escape($app_uuid) ?>&page=<?= $page - 1 ?>&<?= http_build_query(array_filter(['tag'=>$tag,'message'=>$message,'client_identifier'=>$client,'log_uuid'=>$log_uuid,'from'=>$from,'to'=>$to])) ?>">قبلی</a>
                <?php endif; ?>
                <span>صفحه <?= $page ?> از <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?app_uuid=<?= escape($app_uuid) ?>&page=<?= $page + 1 ?>&<?= http_build_query(array_filter(['tag'=>$tag,'message'=>$message,'client_identifier'=>$client,'log_uuid'=>$log_uuid,'from'=>$from,'to'=>$to])) ?>">بعدی</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
</body>
</html>