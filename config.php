<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'log_stream');
define('DB_USER', 'root');
define('DB_PASS', '');

//define('DB_HOST', 'sql307.infinityfree.com');
//define('DB_NAME', 'if0_42441280_log_stream');
//define('DB_USER', 'if0_42441280');
//define('DB_PASS', '12Uli0RBtr');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

session_start();
?>