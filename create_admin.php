<?php
/**
 * إنشاء المدير الرئيسي الأول
 * Create First Super Admin
 * 
 * قم بتشغيل هذا الملف مرة واحدة فقط لإنشاء المدير الرئيسي
 * ثم احذف هذا الملف أو انقله خارج المجلد العام
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getDB();

// التحقق من عدم وجود مدير رئيسي مسبقاً
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 4");
$super_admin_count = $stmt->fetchColumn();

if ($super_admin_count > 0) {
    die("يوجد مدير رئيسي بالفعل في النظام. لا يمكن إنشاء مدير جديد من خلال هذا الملف.");
}

// بيانات المدير الرئيسي الأول
$full_name = "المدير الرئيسي";
$username = "superadmin";
$email = "admin@drawit.com";
$password = "Admin@123"; // غيّر هذا إلى كلمة مرور قوية
$phone = "0500000000";

try {
    $hashed_password = hash_password($password);
    $role_id = 4; // super_admin
    
    $stmt = $db->prepare("INSERT INTO users (full_name, username, email, phone, password, role_id, is_active) 
                          VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$full_name, $username, $email, $phone, $hashed_password, $role_id]);
    
    echo "✅ تم إنشاء المدير الرئيسي بنجاح!<br><br>";
    echo "<strong>بيانات الدخول:</strong><br>";
    echo "اسم المستخدم: $username<br>";
    echo "البريد الإلكتروني: $email<br>";
    echo "كلمة المرور: $password<br><br>";
    echo "<strong>⚠️ مهم جداً:</strong><br>";
    echo "1. احفظ بيانات الدخول في مكان آمن<br>";
    echo "2. غيّر كلمة المرور بعد أول تسجيل دخول<br>";
    echo "3. احذف هذا الملف (create_admin.php) فوراً من السيرفر<br><br>";
    echo '<a href="auth/login.php" class="btn btn-primary">تسجيل الدخول الآن</a>';
    
} catch (PDOException $e) {
    die("❌ خطأ في إنشاء المدير: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء المدير الرئيسي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            padding: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: white;
            color: #333;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 30px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
