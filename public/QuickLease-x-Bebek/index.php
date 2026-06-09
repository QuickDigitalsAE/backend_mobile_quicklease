<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// GET CODE
$code = isset($_GET['CODE']) ? $_GET['CODE'] : 'NO-CODE';

// LOAD IMAGE
$imagePath = "QuickLease-x-Bebek-Complementry-Coffee-Voucher-Resized.jpg";
if (!file_exists($imagePath)) die("Base image not found");

$img = imagecreatefromjpeg($imagePath);

// COLORS
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);

// FONT PATH
$fontPath = __DIR__ . '/Roboto-Bold.ttf';
if (!file_exists($fontPath)) die("TTF font missing");

// IMAGE DIMENSIONS
$imgW = imagesx($img);
$imgH = imagesy($img);

// --- DYNAMIC FONT SIZE BASED ON IMAGE ---
$maxWidth = $imgW * 0.8;   // max 80% of width
$maxHeight = $imgH * 0.5;  // max 50% of height

$fontSize = 20;
if ($fontSize < 10) $fontSize = 10;

// Reduce font if too wide or tall
do {
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $code);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    if ($textWidth <= $maxWidth && $textHeight <= $maxHeight) break;
    $fontSize--;
} while ($fontSize > 5);

// MANUAL X-Y POSITION
$x = 425;
$y = 460;

// DRAW TEXT (plain, no shadow)
imagettftext($img, $fontSize, 0, $x, $y, $black, $fontPath, $code);

// OUTPUT IMAGE
header("Content-Type: image/jpeg");
header("Cache-Control: no-cache, no-store, must-revalidate");
imagejpeg($img, null, 95);
imagedestroy($img);
exit;
