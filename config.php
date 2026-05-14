<?php
/**
 * ملف الإعدادات الآمن
 * هذا الملف يحتوي على بيانات الاتصال بقاعدة البيانات
 * لا تشاركه مع أحد ولا تضعه في مستودع عام
 */

// إعدادات قاعدة البيانات InfinityFree
define('DB_HOST', 'localhost');
define('DB_USER', 'if0_41916916');
define('DB_PASS', 'aboode2005555');
define('DB_NAME', 'if0_41916916_cibod');

// إعدادات الأمان
define('SESSION_TIMEOUT', 1800); // 30 دقيقة
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:8000',
    'https://yourdomain.com', // غير هذا إلى نطاقك الفعلي
]);

// مفتاح reCAPTCHA (غير هذه القيم بمفاتيحك الخاصة)
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY');
define('RECAPTCHA_SITE_KEY', 'YOUR_RECAPTCHA_SITE_KEY');

// بدء الجلسة بأمان
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), // HTTPS فقط
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime' => SESSION_TIMEOUT,
]);

// إعادة تعيين معرف الجلسة كل 5 دقائق
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// التحقق من انتهاء الجلسة
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_destroy();
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'انتهت صلاحية الجلسة']));
}
$_SESSION['last_activity'] = time();
?>
