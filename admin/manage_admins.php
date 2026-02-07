<?php
/**
 * إدارة المدراء (للمدير الرئيسي فقط)
 * Manage Admins Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول كمدير رئيسي
if (!is_logged_in() || !is_super_admin()) {
    set_flash_message('يجب تسجيل الدخول كمدير رئيسي للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$user = get_current_user_data();
$db = getDB();

$errors = [];
$success = '';

// إضافة مدير جديد
if (isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = 3; // admin

    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'كلمة المرور يجب أن تكون ' . PASSWORD_MIN_LENGTH . ' أحرف على الأقل';
    }

    // تحقق من عدد المدراء الحالي
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id IN (3,4)");
    $current_admins_count = $stmt->fetchColumn();
    if ($current_admins_count >= MAX_ADMINS) {
        $errors[] = 'تم الوصول للحد الأقصى للمدراء (' . MAX_ADMINS . ')';
    }

    // التحقق من عدم تكرار البريد
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني مستخدم مسبقاً';
        }
    }

    if (empty($errors)) {
        try {
            $hashed_password = hash_password($password);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password, role_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$full_name, $email, $phone, $hashed_password, $role_id]);
            set_flash_message('تم إضافة المدير بنجاح', 'success');
            redirect(SITE_URL . '/admin/manage_admins.php');
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء إضافة المدير';
        }
    }
}

// تفعيل/تعطيل مدير
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $admin_id = (int)$_GET['toggle'];

    // منع تعطيل المدير الرئيسي الحالي
    if ($admin_id === (int)$user['id']) {
        set_flash_message('لا يمكنك تعطيل حسابك', 'warning');
        redirect(SITE_URL . '/admin/manage_admins.php');
    }

    $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ? AND role_id = 3");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if ($admin) {
        $new_status = $admin['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $admin_id]);
        set_flash_message('تم تحديث حالة المدير بنجاح', 'success');
    }

    redirect(SITE_URL . '/admin/manage_admins.php');
}

// حذف مدير
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $admin_id = (int)$_GET['delete'];

    if ($admin_id === (int)$user['id']) {
        set_flash_message('لا يمكنك حذف حسابك', 'warning');
        redirect(SITE_URL . '/admin/manage_admins.php');
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role_id = 3");
    $stmt->execute([$admin_id]);
    set_flash_message('تم حذف المدير بنجاح', 'success');
    redirect(SITE_URL . '/admin/manage_admins.php');
}

// قائمة المدراء
$stmt = $db->query("SELECT id, full_name, email, phone, is_active, created_at FROM users WHERE role_id = 3 ORDER BY created_at DESC");
$admins = $stmt->fetchAll();

$page_title = 'إدارة المدراء';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users-cog"></i> إدارة المدراء</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Add Admin Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> إضافة مدير جديد</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="col-md-4">
                        <label class="form-label">الاسم الكامل *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم الجوال</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">كلمة المرور *</label>
                        <input type="password" name="password" class="form-control" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة المدير
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Admins List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> قائمة المدراء</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد</th>
                                <th>الجوال</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">لا يوجد مدراء إضافيون</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['phone'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($admin['is_active']): ?>
                                                <span class="status-badge status-approved">مفعل</span>
                                            <?php else: ?>
                                                <span class="status-badge status-rejected">معطل</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo format_arabic_date($admin['created_at']); ?></td>
                                        <td>
                                            <a href="?toggle=<?php echo $admin['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('تأكيد تغيير الحالة؟')">
                                                <i class="fas fa-power-off"></i>
                                            </a>
                                            <a href="?delete=<?php echo $admin['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('تأكيد حذف المدير؟')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
