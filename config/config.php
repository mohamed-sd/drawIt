<?php
/**
 * ملف الإعدادات الرئيسي
 * DrawIt Competition Platform Configuration
 */

// معلومات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'drawit_competition');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
define('SITE_NAME', 'DrawIt - منصة مسابقات الرسم');
define('SITE_URL', 'http://localhost/drawIt');
define('SITE_EMAIL', 'info@drawit.com');

// إعدادات الجلسة
define('SESSION_LIFETIME', 3600 * 24); // 24 ساعة

// مسارات المجلدات
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('VIDEOS_PATH', UPLOAD_PATH . '/videos');
define('THUMBNAILS_PATH', UPLOAD_PATH . '/thumbnails');
define('PROFILES_PATH', UPLOAD_PATH . '/profiles');

// إعدادات الرفع
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100 MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/avi', 'video/mov', 'video/wmv']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);

// إعدادات المسابقة
define('VOTE_PRICE', 5.00); // سعر التصويت المدفوع
define('MAX_ADMINS', 5); // الحد الأقصى للمدراء

// إعدادات الأمان
define('PASSWORD_MIN_LENGTH', 6);
define('SALT', 'DrawIt_2026_Salt_Key_!@#$%'); // مفتاح التشفير

// المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// عرض الأخطاء (للتطوير فقط - أوقفها في الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// إنشاء المجلدات إذا لم تكن موجودة
$directories = [UPLOAD_PATH, VIDEOS_PATH, THUMBNAILS_PATH, PROFILES_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
