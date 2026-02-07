<?php
/**
 * إدارة المراحل
 * Manage Stages
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    set_flash_message('يجب تسجيل الدخول كمدير للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$db = getDB();

// تفعيل مرحلة
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $stage_id = (int)$_GET['activate'];

    $db->beginTransaction();
    $db->exec("UPDATE stages SET is_active = 0");
    $stmt = $db->prepare("UPDATE stages SET is_active = 1 WHERE id = ?");
    $stmt->execute([$stage_id]);
    $db->commit();

    set_flash_message('تم تفعيل المرحلة بنجاح', 'success');
    redirect(SITE_URL . '/admin/manage_stages.php');
}

// تحديث خصائص مرحلة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_stage') {
    $stage_id = (int)($_POST['stage_id'] ?? 0);
    $name = clean_input($_POST['name'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $max_qualifiers = (int)($_POST['max_qualifiers'] ?? 0);
    $is_free_voting = isset($_POST['is_free_voting']) ? 1 : 0;

    if ($stage_id > 0 && $name) {
        $stmt = $db->prepare("UPDATE stages SET name = ?, description = ?, max_qualifiers = ?, is_free_voting = ? WHERE id = ?");
        $stmt->execute([$name, $description, $max_qualifiers, $is_free_voting, $stage_id]);
        set_flash_message('تم تحديث المرحلة بنجاح', 'success');
    }
    redirect(SITE_URL . '/admin/manage_stages.php');
}

// قائمة المراحل
$stmt = $db->query("SELECT * FROM stages ORDER BY stage_number ASC");
$stages = $stmt->fetchAll();

$page_title = 'إدارة المراحل';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-layer-group"></i> إدارة المراحل</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>

        <?php foreach ($stages as $stage): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="stage-badge stage-<?php echo $stage['stage_number']; ?>">
                            <?php echo htmlspecialchars($stage['name']); ?>
                        </span>
                        <?php if ($stage['is_active']): ?>
                            <span class="badge bg-success ms-2">نشطة</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!$stage['is_active']): ?>
                            <a href="?activate=<?php echo $stage['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-bolt"></i> تفعيل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_stage">
                        <input type="hidden" name="stage_id" value="<?php echo $stage['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">اسم المرحلة</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($stage['name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">الحد الأقصى للمتأهلين</label>
                                <input type="number" name="max_qualifiers" class="form-control" value="<?php echo (int)$stage['max_qualifiers']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">حالة التصويت</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_free_voting" id="free_<?php echo $stage['id']; ?>" <?php echo $stage['is_free_voting'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="free_<?php echo $stage['id']; ?>">
                                        تصويت مجاني
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($stage['description']); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
