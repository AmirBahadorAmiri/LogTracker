<?php
session_start();

if (!extension_loaded('gd')) {
    die('افزونه GD در PHP فعال نیست. برای نمایش کپچا لطفاً آن را فعال کنید.');
}

$width = 120;
$height = 40;
$image = imagecreate($width, $height);
$bg = imagecolorallocate($image, 220, 220, 220);
$textColor = imagecolorallocate($image, 0, 0, 0);
$code = rand(1000, 9999);
$_SESSION['captcha'] = $code;

// خطوط نویز
for ($i = 0; $i < 5; $i++) {
    $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
}

imagestring($image, 5, 30, 10, $code, $textColor);
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
exit;