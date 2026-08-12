<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$app_uuid = isset($_POST['app_uuid']) ? $_POST['app_uuid'] : '';
$tag = isset($_POST['tag']) ? $_POST['tag'] : 'general';
$message = isset($_POST['message']) ? $_POST['message'] : '';
$client_identifier = isset($_POST['client_identifier']) ? $_POST['client_identifier'] : '';

if (empty($app_uuid) || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'app_uuid و message الزامی هستند']);
    exit;
}

// بررسی وجود اپ
$stmt = $pdo->prepare("SELECT id FROM apps WHERE app_uuid = ?");
$stmt->execute([$app_uuid]);
$app = $stmt->fetch();
if (!$app) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'app_uuid نامعتبر']);
    exit;
}

// تولید log_uuid و دریافت IP
$log_uuid = generateUUID();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// درج لاگ
$stmt = $pdo->prepare("INSERT INTO logs (app_id, log_uuid, client_identifier, tag, message, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$app['id'], $log_uuid, $client_identifier, $tag, $message, $ip]);

echo json_encode(['status' => 'success', 'log_uuid' => $log_uuid]);
?>