<?php
/**
 * واجهة API الآمنة - ملف المعالجة الرئيسي
 * جميع الطلبات تمر عبر هذا الملف
 * الحماية من: CSRF, XSS, SQL Injection, reCAPTCHA
 */

require_once 'config.php';

// ============================================
// رؤوس الأمان والـ CORS
// ============================================
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// التحقق من CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
}

// معالجة الطلبات المسبقة (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(json_encode(['success' => true]));
}

// ============================================
// الدوال الأساسية
// ============================================

/**
 * تشفير البيانات HTML لمنع XSS
 */
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * الاتصال بقاعدة البيانات
 */
function getDatabaseConnection() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'message' => 'خطأ في الاتصال بقاعدة البيانات'
        ]));
    }
    
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

/**
 * التحقق من CSRF Token
 */
function verifyCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    
    $token = $_POST['csrf_token'] ?? '';
    
    if (empty($token) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die(json_encode([
            'success' => false,
            'message' => 'فشل التحقق من الأمان (CSRF Token غير صحيح)'
        ]));
    }
    
    return true;
}

/**
 * التحقق من reCAPTCHA
 */
function verifyRecaptcha($token) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['success']) && $result['success'] === true && $result['score'] > 0.5;
}

/**
 * تسجيل دخول آمن
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return errorResponse('طريقة الطلب غير صحيحة');
    }
    
    verifyCsrfToken();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $recaptcha_token = $_POST['recaptcha_token'] ?? '';
    
    // التحقق من صحة البيانات
    if (empty($email) || empty($password)) {
        return errorResponse('البريد والكلمة المرورية مطلوبة');
    }
    
    // التحقق من reCAPTCHA
    if (!verifyRecaptcha($recaptcha_token)) {
        return errorResponse('فشل التحقق من أنك لست روبوتاً');
    }
    
    // التحقق من صيغة البريد
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return errorResponse('صيغة البريد غير صحيحة');
    }
    
    // البحث عن المستخدم في قاعدة البيانات
    $mysqli = getDatabaseConnection();
    $stmt = $mysqli->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return errorResponse('البريد أو الكلمة المرورية غير صحيحة');
    }
    
    $user = $result->fetch_assoc();
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        return errorResponse('البريد أو الكلمة المرورية غير صحيحة');
    }
    
    // تسجيل الدخول بنجاح
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['login_time'] = time();
    
    $stmt->close();
    $mysqli->close();
    
    return successResponse('تم تسجيل الدخول بنجاح');
}

/**
 * تسجيل الخروج
 */
function handleLogout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return errorResponse('طريقة الطلب غير صحيحة');
    }
    
    verifyCsrfToken();
    
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    
    return successResponse('تم تسجيل الخروج بنجاح');
}

/**
 * الحصول على CSRF Token
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return [
        'success' => true,
        'csrf_token' => $_SESSION['csrf_token']
    ];
}

/**
 * التحقق من تسجيل الدخول
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * الحصول على بيانات الجدول
 */
function handleGetData() {
    if (!isUserLoggedIn()) {
        http_response_code(401);
        return errorResponse('يجب تسجيل الدخول أولاً');
    }
    
    $table = $_GET['table'] ?? '';
    
    // التحقق من أن اسم الجدول صحيح
    $allowed_tables = ['users', 'posts', 'comments'];
    if (!in_array($table, $allowed_tables)) {
        return errorResponse('جدول غير صحيح');
    }
    
    $mysqli = getDatabaseConnection();
    
    // عدم اختيار الحقول الحساسة
    if ($table === 'users') {
        $query = 'SELECT id, name, email, created_at FROM users LIMIT 100';
    } else {
        $query = 'SELECT * FROM ' . $table . ' LIMIT 100';
    }
    
    $result = $mysqli->query($query);
    
    if (!$result) {
        $mysqli->close();
        return errorResponse('خطأ في استرجاع البيانات');
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $result->free();
    $mysqli->close();
    
    return [
        'success' => true,
        'data' => $data
    ];
}

/**
 * إدراج بيانات جديدة
 */
function handleInsertData() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return errorResponse('طريقة الطلب غير صحيحة');
    }
    
    if (!isUserLoggedIn()) {
        http_response_code(401);
        return errorResponse('يجب تسجيل الدخول أولاً');
    }
    
    verifyCsrfToken();
    
    $table = $_POST['table'] ?? '';
    $data = $_POST['data'] ?? [];
    
    // التحقق من صحة الجدول
    $allowed_tables = ['posts', 'comments'];
    if (!in_array($table, $allowed_tables)) {
        return errorResponse('جدول غير صحيح أو محمي');
    }
    
    if (empty($data)) {
        return errorResponse('البيانات مطلوبة');
    }
    
    $mysqli = getDatabaseConnection();
    
    // بناء الاستعلام بأمان
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    
    $query = 'INSERT INTO ' . $table . ' (' . implode(',', array_map(function($col) {
        return '`' . $col . '`';
    }, $columns)) . ') VALUES (' . implode(',', $placeholders) . ')';
    
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        $mysqli->close();
        return errorResponse('خطأ في تحضير الاستعلام');
    }
    
    // ربط البيانات
    $types = str_repeat('s', count($columns));
    $stmt->bind_param($types, ...array_values($data));
    
    if (!$stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        return errorResponse('خطأ في إدراج البيانات');
    }
    
    $stmt->close();
    $mysqli->close();
    
    return successResponse('تم إدراج البيانات بنجاح');
}

/**
 * دالة لإرسال رد نجاح
 */
function successResponse($message, $extra = []) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    return array_merge($response, $extra);
}

/**
 * دالة لإرسال رد خطأ
 */
function errorResponse($message) {
    http_response_code(400);
    return [
        'success' => false,
        'message' => $message
    ];
}

// ============================================
// معالجة الطلبات
// ============================================

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    $response = match($action) {
        'csrf_token' => getCsrfToken(),
        'login' => handleLogin(),
        'logout' => handleLogout(),
        'get_data' => handleGetData(),
        'insert_data' => handleInsertData(),
        default => errorResponse('إجراء غير معروف')
    };
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم'
    ], JSON_UNESCAPED_UNICODE);
}
?>
