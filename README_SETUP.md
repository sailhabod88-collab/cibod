# 🔐 دليل إعداد النظام الآمن

## 📋 المتطلبات
- استضافة InfinityFree (تدعم PHP بشكل افتراضي)
- صلاحيات الكتابة على الملفات
- دعم MySQLi

## 🚀 خطوات الإعداد

### 1. إنشاء قاعدة البيانات
```sql
-- إنشاء جدول المستخدمين
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- إنشاء جدول المنشورات (مثال)
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- إنشاء جدول التعليقات (مثال)
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 2. تحديث ملف config.php
```php
// عدّل هذه البيانات ببيانات InfinityFree الخاصة بك:
define('DB_HOST', 'localhost');
define('DB_USER', 'if0_41916916');          // اسم المستخدم
define('DB_PASS', 'aboode2005555');         // كلمة المرور
define('DB_NAME', 'if0_41916916_cibod');    // اسم قاعدة البيانات

// عدّل النطاق المسموح:
define('ALLOWED_ORIGINS', [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
]);
```

### 3. إعداد reCAPTCHA
1. اذهب إلى: https://www.google.com/recaptcha/admin
2. أنشئ مشروع جديد (reCAPTCHA v3)
3. انسخ `SITE KEY` و `SECRET KEY`
4. عدّل في `config.php`:
```php
define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY_HERE');
define('RECAPTCHA_SITE_KEY', 'YOUR_SITE_KEY_HERE');
```
5. عدّل في `db.html`:
```html
<div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
```

### 4. تحديث رابط API
في ملف `db.html`، عدّل:
```javascript
const API_URL = 'https://yourdomain.com/api.php';
```

### 5. تحديث ALLOWED_ORIGINS
في `config.php`:
```php
define('ALLOWED_ORIGINS', [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
    'http://localhost:3000', // للاختبار المحلي
]);
```

## 🔒 ميزات الأمان

### ✅ CSRF Protection
- كل طلب POST يتطلب CSRF Token
- يتم إنشاء Token جديد لكل جلسة

### ✅ SQL Injection Protection
- استخدام Prepared Statements
- bind_param لكل المعاملات

### ✅ XSS Protection
- تشفير كل البيانات المعروضة مع `escapeHtml()`
- رؤوس الأمان الصحيحة

### ✅ CORS Security
- السماح فقط للنطاقات المعرّفة
- منع الوصول من نطاقات غير معروفة

### ✅ Bot Protection
- reCAPTCHA v3 على كل تسجيل دخول
- منع الهجمات الآلية

### ✅ Session Security
- Cookies بخاصية `HttpOnly` (لا يمكن الوصول من JavaScript)
- Secure flag (HTTPS فقط)
- SameSite=Strict (منع CSRF)
- معاد تعيين معرف الجلسة كل 5 دقائق

### ✅ Data Protection
- كلمات المرور مشفرة مع bcrypt (password_hash)
- Prepared Statements لكل الاستعلامات

## 📝 الملفات المرفوعة

| الملف | الوصف | الموقع |
|------|-------|--------|
| `config.php` | إعدادات الاتصال والأمان | الجذر |
| `api.php` | واجهة API الآمنة | الجذر |
| `db.html` | صفحة الويب الآمنة | الجذر |
| `.htaccess` | قواعد الأمان على المستوى الخادم | الجذر |

## 🧪 الاختبار

1. اذهب إلى: `https://yourdomain.com/db.html`
2. سجل دخول ببريد وكلمة مرور
3. حمّل البيانات من الجداول

## ⚠️ ملاحظات أمنية مهمة

1. **لا تشارك بيانات الاتصال** - `config.php` يجب أن يبقى سرياً
2. **استخدم HTTPS دائماً** - في الإنتاج
3. **حدّث كلماتك المرورية** - البيانات التي شاركتها قد تكون معروضة
4. **راجع السجلات بانتظام** - ابحث عن محاولات الوصول المريبة
5. **أبقِ PHP محدثاً** - استخدم أحدث إصدار متاح

## 🔧 معالجة المشاكل

### مشكلة: "خطأ في الاتصال بقاعدة البيانات"
- تحقق من بيانات الاتصال في `config.php`
- تأكد من تفعيل قاعدة البيانات على InfinityFree

### مشكلة: "فشل التحقق من reCAPTCHA"
- تأكد من صحة المفاتيح
- تأكد من أن النطاق مسجل في Google

### مشكلة: "CORS error"
- أضف النطاق الخاص بك إلى `ALLOWED_ORIGINS`
- تأكد من استخدام HTTP أو HTTPS بشكل متطابق

## 📞 دعم
للمساعدة، تواصل مع فريق الدعم أو راجع الأكواد مع قائمة التعليقات.

---
**آخر تحديث:** 2026-05-14
**الإصدار:** 1.0.0
