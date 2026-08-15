<?php
require_once '../auth.php';

$app_uuid = $_GET['app_uuid'] ?? '';
if (!$app_uuid) {
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM apps WHERE app_uuid = ? AND user_id = ?");
$stmt->execute([$app_uuid, $_SESSION['user_id']]);
$app = $stmt->fetch();
if (!$app) {
    redirect('dashboard.php');
}

if (isset($_GET['delete_log'])) {
    $log_id = (int)$_GET['delete_log'];
    $check_stmt = $pdo->prepare("SELECT id FROM logs WHERE id = ? AND app_id = ?");
    $check_stmt->execute([$log_id, $app['id']]);
    if ($check_stmt->rowCount() > 0) {
        $del_stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
        $del_stmt->execute([$log_id]);
    }
    $redirect_url = "view_app.php?app_uuid=" . urlencode($app_uuid);
    $query_params = $_GET;
    unset($query_params['delete_log']);
    if (!empty($query_params)) {
        $redirect_url .= '&' . http_build_query($query_params);
    }
    redirect($redirect_url);
}

$tag = $_GET['tag'] ?? '';
$message = $_GET['message'] ?? '';
$client = $_GET['client_identifier'] ?? '';
$log_uuid = $_GET['log_uuid'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM logs WHERE app_id = ?";
$params = [$app['id']];
$filter_params = [];

if ($tag) { $sql .= " AND tag LIKE ?"; $params[] = "%$tag%"; $filter_params['tag'] = $tag; }
if ($message) { $sql .= " AND message LIKE ?"; $params[] = "%$message%"; $filter_params['message'] = $message; }
if ($client) { $sql .= " AND client_identifier = ?"; $params[] = $client; $filter_params['client_identifier'] = $client; }
if ($log_uuid) { $sql .= " AND log_uuid = ?"; $params[] = $log_uuid; $filter_params['log_uuid'] = $log_uuid; }
if ($from) { $sql .= " AND created_at >= ?"; $params[] = $from; $filter_params['from'] = $from; }
if ($to) { $sql .= " AND created_at <= ?"; $params[] = $to . ' 23:59:59'; $filter_params['to'] = $to; }

$countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) { $stmt->bindValue($key + 1, $value); }
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
    <title>لاگ‌های اپلیکیشن: <?= escape($app['app_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h2><i class="fas fa-clipboard-list"></i> لاگ‌های اپ: <?= escape($app['app_name']) ?></h2>
        <nav class="nav">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> بازگشت به داشبورد</a>
        </nav>
    </header>

    <section class="filters">
        <h3><i class="fas fa-filter"></i> فیلتر کردن لاگ‌ها</h3>
        <form method="get">
            <input type="hidden" name="app_uuid" value="<?= escape($app_uuid) ?>">
            <input type="text" name="tag" placeholder="تگ" value="<?= escape($tag) ?>">
            <input type="text" name="message" placeholder="متن پیام" value="<?= escape($message) ?>">
            <input type="text" name="client_identifier" placeholder="شناسه کلاینت" value="<?= escape($client) ?>">
            <input type="text" name="log_uuid" placeholder="UUID لاگ" value="<?= escape($log_uuid) ?>">
            <input type="date" name="from" title="از تاریخ" value="<?= escape($from) ?>">
            <input type="date" name="to" title="تا تاریخ" value="<?= escape($to) ?>">
            <button type="submit"><i class="fas fa-search"></i> فیلتر</button>
            <a href="view_app.php?app_uuid=<?= escape($app_uuid) ?>" class="button"><i class="fas fa-undo"></i> پاک کردن</a>
        </form>
    </section>

    <section class="logs">
        <h3><i class="fas fa-stream"></i> لیست لاگ‌ها (<?= $total ?> مورد)</h3>
        <?php if (empty($logs)): ?>
            <p style="text-align: center; padding: 2rem; background: #f9f9f9; border-radius: 4px;">
                <i class="fas fa-info-circle"></i> هیچ لاگی با این فیلترها یافت نشد.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
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
                            <td><small><?= escape($log['log_uuid']) ?></small></td>
                            <td><?= escape($log['client_identifier']) ?></td>
                            <td><span class="tag-badge"><?= escape($log['tag']) ?></span></td>
                            <td class="log-message"><?= nl2br(escape($log['message'])) ?></td>
                            <td><?= escape($log['ip_address']) ?></td>
                            <td><small><?= $log['created_at'] ?></small></td>
                            <td>
                                <a href="view_app.php?delete_log=<?= $log['id'] ?>&<?= http_build_query(array_merge(['app_uuid' => $app_uuid], $filter_params, ['page' => $page])) ?>"
                                   onclick="return confirm('آیا از حذف این لاگ مطمئن هستید؟')"
                                   class="delete-link" title="حذف لاگ">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_merge(['app_uuid' => $app_uuid], $filter_params)) ?>">
                            <i class="fas fa-chevron-right"></i> قبلی
                        </a>
                    <?php endif; ?>

                    <span>صفحه <?= $page ?> از <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_merge(['app_uuid' => $app_uuid], $filter_params)) ?>">
                            بعدی <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
</body>
</html>