<?php
/**
 * ملف الدوال المساعدة العامة
 * Helper Functions
 */

// بدء الجلسة إذا لم تكن بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * تنظيف المدخلات من السكريبتات الضارة
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * تشفير كلمة المرور
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * التحقق من تسجيل دخول المستخدم
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * الحصول على معلومات المستخدم الحالي
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.name as role_name FROM users u 
                          LEFT JOIN roles r ON u.role_id = r.id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * التحقق من صلاحيات المستخدم
 */
function has_role($role_name) {
    $user = get_current_user_data();
    return $user && $user['role_name'] === $role_name;
}

/**
 * التحقق من أن المستخدم مدير
 */
function is_admin() {
    $user = get_current_user_data();
    return $user && ($user['role_name'] === 'admin' || $user['role_name'] === 'super_admin');
}

/**
 * التحقق من أن المستخدم مدير رئيسي
 */
function is_super_admin() {
    return has_role('super_admin');
}

/**
 * التحقق من أن المستخدم متسابق
 */
function is_contestant() {
    return has_role('contestant');
}

/**
 * إعادة التوجيه
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * عرض رسالة فلاش
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * الحصول على رسالة فلاش وحذفها
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * الحصول على عنوان IP الخاص بالزائر
 */
function get_client_ip() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * رفع ملف فيديو
 */
function upload_video($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }
    
    if ($file['size'] > MAX_VIDEO_SIZE) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً (الحد الأقصى 100 ميجابايت)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_VIDEO_TYPES)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('video_') . '_' . time() . '.' . $ext;
    $filepath = VIDEOS_PATH . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الملف'];
}

/**
 * رفع صورة
 */
function upload_image($file, $folder = 'profiles') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الصورة'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'نوع الصورة غير مسموح به'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '_' . time() . '.' . $ext;
    
    $upload_path = UPLOAD_PATH . '/' . $folder;
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $filepath = $upload_path . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الصورة'];
}

/**
 * الحصول على المرحلة النشطة الحالية
 */
function get_active_stage() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM stages WHERE is_active = 1 ORDER BY stage_number ASC LIMIT 1");
    return $stmt->fetch();
}

/**
 * إرسال تنبيه لمستخدم
 */
function send_notification($user_id, $title, $message, $type = 'general', $drawing_id = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, drawing_id, type, title, message) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $drawing_id, $type, $title, $message]);
}

/**
 * تنسيق التاريخ بالعربي
 */
function format_arabic_date($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y - h:i A', $timestamp);
}

/**
 * حساب الوقت المنقضي
 */
function time_elapsed($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d >= 7) {
        return format_arabic_date($datetime);
    } elseif ($diff->d > 0) {
        return 'منذ ' . $diff->d . ' يوم';
    } elseif ($diff->h > 0) {
        return 'منذ ' . $diff->h . ' ساعة';
    } elseif ($diff->i > 0) {
        return 'منذ ' . $diff->i . ' دقيقة';
    } else {
        return 'الآن';
    }
}

/**
 * التحقق من التصويت السابق
 */
function has_voted($drawing_id, $stage_id) {
    $db = getDB();
    $ip = get_client_ip();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM vote_restrictions WHERE drawing_id = ? AND voter_ip = ? AND stage_id = ?");
    $stmt->execute([$drawing_id, $ip, $stage_id]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * حماية من CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
