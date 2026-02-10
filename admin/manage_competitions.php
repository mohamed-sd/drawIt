<?php
/**
 * إدارة المسابقات
 * Manage Competitions
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_super_admin()) {
    set_flash_message('يجب تسجيل الدخول كمدير رئيسي للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$db = getDB();
$user = get_current_user_data();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_competition') {
    $name = clean_input($_POST['name'] ?? '');
    $slug = clean_input($_POST['slug'] ?? '');
    $category = clean_input($_POST['category'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $rules = clean_input($_POST['rules'] ?? '');
    $stage_count = (int)($_POST['stage_count'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$name) {
        $errors[] = 'اسم المسابقة مطلوب';
    }
    if ($stage_count < 1) {
        $errors[] = 'عدد المراحل يجب أن يكون 1 على الأقل';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO competitions (name, slug, description, rules, category, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug ?: null, $description, $rules, $category, $is_active, $user['id']]);
            $competition_id = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO competition_admins (competition_id, admin_id) VALUES (?, ?)");
            $stmt->execute([$competition_id, $user['id']]);

            $stage_stmt = $db->prepare("INSERT INTO stages (competition_id, name, stage_number, description, is_free_voting, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            for ($i = 1; $i <= $stage_count; $i++) {
                $stage_name = 'المرحلة ' . $i;
                $stage_description = 'مرحلة رقم ' . $i;
                $is_free = $i === 1 ? 1 : 0;
                $is_stage_active = $i === 1 ? 1 : 0;
                $stage_stmt->execute([$competition_id, $stage_name, $i, $stage_description, $is_free, $is_stage_active]);
            }

            $db->commit();
            set_flash_message('تم إنشاء المسابقة بنجاح', 'success');
            redirect(SITE_URL . '/admin/manage_competitions.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'حدث خطأ أثناء إنشاء المسابقة';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_competition') {
    $competition_id = (int)($_POST['competition_id'] ?? 0);
    $name = clean_input($_POST['name'] ?? '');
    $slug = clean_input($_POST['slug'] ?? '');
    $category = clean_input($_POST['category'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $rules = clean_input($_POST['rules'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($competition_id && $name) {
        $stmt = $db->prepare("UPDATE competitions SET name = ?, slug = ?, description = ?, rules = ?, category = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $slug ?: null, $description, $rules, $category, $is_active, $competition_id]);
        set_flash_message('تم تحديث المسابقة بنجاح', 'success');
        redirect(SITE_URL . '/admin/manage_competitions.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_admins') {
    $competition_id = (int)($_POST['competition_id'] ?? 0);
    $admin_ids = $_POST['admin_ids'] ?? [];

    if ($competition_id) {
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM competition_admins WHERE competition_id = ?");
        $stmt->execute([$competition_id]);

        if (!empty($admin_ids)) {
            $insert_stmt = $db->prepare("INSERT INTO competition_admins (competition_id, admin_id) VALUES (?, ?)");
            foreach ($admin_ids as $admin_id) {
                $insert_stmt->execute([$competition_id, (int)$admin_id]);
            }
        }

        $db->commit();
        set_flash_message('تم تحديث لجنة التحكيم بنجاح', 'success');
        redirect(SITE_URL . '/admin/manage_competitions.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_committee_admin') {
    $competition_id = (int)($_POST['competition_id'] ?? 0);
    $full_name = clean_input($_POST['full_name'] ?? '');
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$competition_id || !$full_name || !$username) {
        $errors[] = 'يجب تعبئة جميع بيانات الحساب';
    }
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'كلمة المرور يجب أن تكون ' . PASSWORD_MIN_LENGTH . ' أحرف على الأقل';
    }

    if (empty($errors)) {
        $email = $username . '@committee.local';
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'اسم المستخدم مستخدم مسبقاً';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $hashed_password = hash_password($password);
            $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password, role_id, is_active) VALUES (?, ?, ?, ?, 3, 1)");
            $stmt->execute([$full_name, $username, $email, $hashed_password]);
            $admin_id = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO competition_admins (competition_id, admin_id) VALUES (?, ?)");
            $stmt->execute([$competition_id, $admin_id]);

            $db->commit();
            set_flash_message('تم إنشاء حساب لجنة التحكيم وربطه بالمسابقة', 'success');
            redirect(SITE_URL . '/admin/manage_competitions.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'حدث خطأ أثناء إنشاء الحساب';
        }
    }
}

$competitions = $db->query("SELECT * FROM competitions ORDER BY created_at DESC")->fetchAll();
$admins = $db->query("SELECT id, full_name, username, email FROM users WHERE role_id IN (3,4) AND is_active = 1 ORDER BY full_name ASC")->fetchAll();

$page_title = 'إدارة المسابقات';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-award"></i> إدارة المسابقات</h2>
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

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> إنشاء مسابقة جديدة</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="action" value="create_competition">
                    <div class="col-md-4">
                        <label class="form-label">اسم المسابقة *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Slug (اختياري)</label>
                        <input type="text" name="slug" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التصنيف</label>
                        <input type="text" name="category" class="form-control" placeholder="مثال: رسم، رقص، فيديو مضحك">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عدد المراحل *</label>
                        <input type="number" name="stage_count" class="form-control" min="1" value="3" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="comp_active" checked>
                            <label class="form-check-label" for="comp_active">تفعيل المسابقة</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">وصف المسابقة</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">قواعد المسابقة</label>
                        <textarea name="rules" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> إنشاء المسابقة
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($competitions as $competition): ?>
            <?php
            $stmt = $db->prepare("SELECT COUNT(*) FROM stages WHERE competition_id = ?");
            $stmt->execute([$competition['id']]);
            $stages_count = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM competition_contestants WHERE competition_id = ?");
            $stmt->execute([$competition['id']]);
            $contestants_count = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT admin_id FROM competition_admins WHERE competition_id = ?");
            $stmt->execute([$competition['id']]);
            $assigned_admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($competition['name']); ?></h5>
                        <small class="text-muted">المراحل: <?php echo $stages_count; ?> | المتسابقون: <?php echo $contestants_count; ?></small>
                    </div>
                    <span class="badge <?php echo $competition['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo $competition['is_active'] ? 'نشطة' : 'غير نشطة'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="row g-3">
                        <input type="hidden" name="action" value="update_competition">
                        <input type="hidden" name="competition_id" value="<?php echo $competition['id']; ?>">
                        <div class="col-md-4">
                            <label class="form-label">اسم المسابقة *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($competition['name']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($competition['slug'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">التصنيف</label>
                            <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($competition['category'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($competition['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">القواعد</label>
                            <textarea name="rules" class="form-control" rows="2"><?php echo htmlspecialchars($competition['rules'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="active_<?php echo $competition['id']; ?>" <?php echo $competition['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="active_<?php echo $competition['id']; ?>">تفعيل المسابقة</label>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>

                    <hr>

                    <form method="POST" action="" class="row g-3">
                        <input type="hidden" name="action" value="update_admins">
                        <input type="hidden" name="competition_id" value="<?php echo $competition['id']; ?>">
                        <div class="col-12">
                            <label class="form-label">لجنة التحكيم</label>
                            <div class="row">
                                <?php foreach ($admins as $admin): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="admin_ids[]" value="<?php echo $admin['id']; ?>" id="admin_<?php echo $competition['id'] . '_' . $admin['id']; ?>"
                                                <?php echo in_array($admin['id'], $assigned_admins, true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="admin_<?php echo $competition['id'] . '_' . $admin['id']; ?>">
                                                <?php echo htmlspecialchars($admin['full_name']); ?> - <?php echo htmlspecialchars($admin['username'] ?? $admin['email']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-users-cog"></i> تحديث لجنة التحكيم
                            </button>
                        </div>
                    </form>

                    <hr>

                    <form method="POST" action="" class="row g-3">
                        <input type="hidden" name="action" value="create_committee_admin">
                        <input type="hidden" name="competition_id" value="<?php echo $competition['id']; ?>">
                        <div class="col-12">
                            <h6 class="mb-2"><i class="fas fa-user-plus"></i> إنشاء حساب لجنة تحكيم جديد</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الاسم الكامل *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">اسم المستخدم *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">كلمة المرور *</label>
                            <input type="password" name="password" class="form-control" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> إنشاء الحساب وربطه
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
