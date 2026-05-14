<?php
/**
 * ملف الإعدادات الآمن (مُحدّث)
 * مُهيأ للعمل مع استضافة InfinityFree ونطاق المشروع cibod.xo.je
 * لا ترفع هذا الملف إلى مستودع عام إذا احتوى بيانات حساسة
 */

// إعدادات قاعدة البيانات InfinityFree (محدثة)
define('DB_HOST', 'sql301.infinityfree.com');
define('DB_USER', 'if0_41916916');
define('DB_PASS', 'aboode2005555');
define('DB_NAME', 'if0_41916916_cibod');

// إعدادات الأمان
define('SESSION_TIMEOUT', 1800); // 30 دقيقة
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:8000',
    'http://127.0.0.1:3000',
    'https://cibod.xo.je',
    'https://www.cibod.xo.je'
]);

// مفتاح reCAPTCHA (ضع مفاتيحك الحقيقية هنا)
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY');
define('RECAPTCHA_SITE_KEY', 'YOUR_RECAPTCHA_SITE_KEY');

// تحديد ما إذا كان الاتصال عبر HTTPS — مفيد لضبط خاصية cookie_secure
$using_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// بدء الجلسة بأمان
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => $using_https, // يُفعّل عند وجود HTTPS
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime' => SESSION_TIMEOUT,
]);

// إعادة تعيين معرف الجلسة كل 5 دقائق لمنع hijacking
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
    // هنا لا نُخرج نصوص HTML لأن هذا الملف قد يُضمّن في API
    die(json_encode(['success' => false, 'message' => 'انتهت صلاحية الجلسة']));
}
$_SESSION['last_activity'] = time();

// دالة مساعدة لتهيئة اتصالات قاعدة البيانات في بقية التطبيق
function db_connect() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        error_log('DB connect error: ' . $mysqli->connect_error);
        return null;
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

?>
