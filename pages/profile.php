<?php
/**
 * صفحة الملف الشخصي
 * Profile Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    set_flash_message('يجب تسجيل الدخول أولاً', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$db = getDB();
$user = get_current_user_data();

$errors = [];
$success = '';

// تحديث البيانات الشخصية
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }

    // التحقق من البريد إذا تغيّر
    if (empty($errors) && $email !== $user['email']) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني مستخدم مسبقاً';
        }
    }

    // التحقق من كلمة المرور
    $hashed_password = null;
    if (!empty($password)) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'كلمة المرور يجب أن تكون ' . PASSWORD_MIN_LENGTH . ' أحرف على الأقل';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'كلمات المرور غير متطابقة';
        }
        if (empty($errors)) {
            $hashed_password = hash_password($password);
        }
    }

    // رفع الصورة الشخصية
    $profile_image = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_result = upload_image($_FILES['profile_image'], 'profiles');
        if ($upload_result['success']) {
            $profile_image = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    if (empty($errors)) {
        try {
            if ($hashed_password) {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $hashed_password, $profile_image, $user['id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $profile_image, $user['id']]);
            }

            set_flash_message('تم تحديث الملف الشخصي بنجاح', 'success');
            redirect(SITE_URL . '/pages/profile.php');
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء تحديث الملف الشخصي';
        }
    }
}

$page_title = 'الملف الشخصي';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-lg text-center">
                <div class="card-body">
                    <?php
                    $avatar = $user['profile_image'] ? SITE_URL . '/uploads/profiles/' . $user['profile_image'] : 'https://via.placeholder.com/150?text=User';
                    ?>
                    <img src="<?php echo $avatar; ?>" alt="الصورة الشخصية" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted mb-1"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if (!empty($user['phone'])): ?>
                        <p class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                    <div class="mt-3">
                        <span class="badge bg-primary">
                            <?php echo htmlspecialchars($user['role_name'] ?? 'مستخدم'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-info-circle"></i> معلومات الحساب</h6>
                    <ul class="list-unstyled text-muted small">
                        <li><i class="fas fa-calendar"></i> تاريخ التسجيل: <?php echo format_arabic_date($user['created_at']); ?></li>
                        <li><i class="fas fa-shield-alt"></i> حالة الحساب: <?php echo $user['is_active'] ? 'مفعل' : 'معطل'; ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> تعديل الملف الشخصي</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">الاسم الكامل *</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رقم الجوال</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الصورة الشخصية</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(this, 'profile-preview')">
                            <img id="profile-preview" src="" alt="" style="display:none; margin-top:10px; max-width:120px; border-radius:50%;">
                        </div>

                        <hr>

                        <h6 class="mb-3"><i class="fas fa-lock"></i> تغيير كلمة المرور (اختياري)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" name="password" class="form-control" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
