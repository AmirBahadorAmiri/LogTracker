<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// --- دریافت و اعتبارسنجی ورودی ---
$app_uuid = $_POST['app_uuid'] ?? '';
$client_identifier = $_POST['client_identifier'] ?? '';
$os_type = $_POST['os_type'] ?? null;
$os_version = $_POST['os_version'] ?? null;
$device_model = $_POST['device_model'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null; // دریافت خودکار User-Agent

if (empty($app_uuid) || empty($client_identifier)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'app_uuid and client_identifier are required']);
    exit;
}

// --- یافتن اپلیکیشن ---
$stmt = $pdo->prepare("SELECT id FROM apps WHERE app_uuid = ?");
$stmt->execute([$app_uuid]);
$app = $stmt->fetch();
if (!$app) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Invalid app_uuid']);
    exit;
}
$app_id = $app['id'];

// --- منطق UPSERT ---
// اگر رکوردی با این app_id و client_identifier وجود داشته باشد، آن را آپدیت کن
// در غیر این صورت، رکورد جدیدی درج کن
$sql = "
    INSERT INTO devices (app_id, client_identifier, os_type, os_version, device_model, user_agent)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        os_type = VALUES(os_type),
        os_version = VALUES(os_version),
        device_model = VALUES(device_model),
        user_agent = VALUES(user_agent)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$app_id, $client_identifier, $os_type, $os_version, $device_model, $user_agent]);

echo json_encode(['status' => 'success', 'message' => 'Device information updated successfully']);
?>
