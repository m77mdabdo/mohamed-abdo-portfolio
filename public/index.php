<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// XAMPP's Apache runs an older PHP. Explain how to start the site instead of showing a server error.
if (PHP_VERSION_ID < 80401) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>Start the site</title>'
        .'<body style="font-family:system-ui;background:#121012;color:#ECE8E5;max-width:640px;margin:10vh auto;padding:0 20px;line-height:1.7">'
        .'<h1 style="color:#49DDE4">شغّل الموقع بالطريقة الصحيحة</h1>'
        .'<p>هذا الخادم يعمل بـ PHP '.htmlspecialchars(PHP_VERSION).'، والموقع يحتاج PHP 8.4 أو أحدث.</p>'
        .'<p>من Finder افتح مجلد <code>mohamed-abdo-portfolio</code> واضغط مرتين على <b>Start Portfolio.command</b>، '
        .'أو من الترمنال: <code style="direction:ltr;display:inline-block">bash scripts/start.sh</code></p>'
        .'<p>ثم افتح <a style="color:#49DDE4" href="http://127.0.0.1:8020/ar">http://127.0.0.1:8020/ar</a></p>'
        .'<hr style="border-color:#333"><p dir="ltr" style="text-align:left">This server runs PHP '.htmlspecialchars(PHP_VERSION).'; the site needs PHP 8.4+. '
        .'Double-click <b>Start Portfolio.command</b> in the project folder, then open http://127.0.0.1:8020/en</p></body></html>';
    exit;
}

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
