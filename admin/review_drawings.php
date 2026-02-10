<?php
/**
 * مراجعة جميع الأعمال
 * Review Drawings List
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    set_flash_message('يجب تسجيل الدخول كمدير للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$db = getDB();
$user = get_current_user_data();
$admin_competitions = get_admin_competitions($user['id']);
$active_competition = get_admin_active_competition($user['id']);
$competition_id = $active_competition['id'] ?? null;

// فلترة حسب الحالة
$status_filter = $_GET['status'] ?? 'pending';
$allowed_status = ['pending', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_status, true)) {
    $status_filter = 'pending';
}

$drawings = [];
if ($competition_id) {
    $stmt = $db->prepare("SELECT d.*, u.full_name, s.name as stage_name,
                          (SELECT COUNT(*) FROM admin_approvals WHERE drawing_id = d.id AND approval_status = 'approved') as approved_count,
                          (SELECT COUNT(*) FROM admin_approvals WHERE drawing_id = d.id) as total_admins,
                          (SELECT approval_status FROM admin_approvals WHERE drawing_id = d.id AND admin_id = ?) as my_status
                          FROM drawings d
                          JOIN users u ON d.user_id = u.id
                          JOIN stages s ON d.stage_id = s.id
                          WHERE d.status = ? AND d.competition_id = ?
                          ORDER BY d.created_at DESC");
    $stmt->execute([$user['id'], $status_filter, $competition_id]);
    $drawings = $stmt->fetchAll();
}

$page_title = 'مراجعة الأعمال';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tasks"></i> مراجعة الأعمال</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>

        <?php if (!empty($admin_competitions)): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1"><i class="fas fa-award"></i> المسابقة الحالية</h5>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($active_competition['name'] ?? ''); ?></p>
                </div>
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <label for="competition_id" class="form-label mb-0">تغيير المسابقة</label>
                    <select name="competition_id" id="competition_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($admin_competitions as $competition): ?>
                            <option value="<?php echo $competition['id']; ?>" <?php echo ((int)$competition['id'] === (int)($active_competition['id'] ?? 0)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($competition['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                لا توجد مسابقات مرتبطة بحسابك حالياً.
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <?php $competition_param = $competition_id ? '&competition_id=' . (int)$competition_id : ''; ?>
            <a href="?status=pending<?php echo $competition_param; ?>" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">قيد المراجعة</a>
            <a href="?status=approved<?php echo $competition_param; ?>" class="btn btn-sm <?php echo $status_filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">معتمدة</a>
            <a href="?status=rejected<?php echo $competition_param; ?>" class="btn btn-sm <?php echo $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">مرفوضة</a>
        </div>

        <div class="admin-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>المتسابق</th>
                        <th>المرحلة</th>
                        <th>الموافقات</th>
                        <th>حالتي</th>
                        <th>تاريخ الرفع</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($drawings)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">لا توجد أعمال</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($drawings as $drawing): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($drawing['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($drawing['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($drawing['stage_name']); ?></td>
                                <td>
                                    <div class="progress" style="height: 22px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                             style="width: <?php echo $drawing['total_admins'] ? ($drawing['approved_count'] / $drawing['total_admins']) * 100 : 0; ?>%">
                                            <?php echo $drawing['approved_count']; ?>/<?php echo $drawing['total_admins']; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_badge = '';
                                    switch ($drawing['my_status']) {
                                        case 'approved':
                                            $status_badge = '<span class="status-badge status-approved"><i class="fas fa-check"></i> وافقت</span>';
                                            break;
                                        case 'rejected':
                                            $status_badge = '<span class="status-badge status-rejected"><i class="fas fa-times"></i> رفضت</span>';
                                            break;
                                        default:
                                            $status_badge = '<span class="status-badge status-pending"><i class="fas fa-clock"></i> قيد الانتظار</span>';
                                    }
                                    echo $status_badge;
                                    ?>
                                </td>
                                <td><?php echo time_elapsed($drawing['created_at']); ?></td>
                                <td>
                                    <a href="review_drawing.php?id=<?php echo $drawing['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> مراجعة
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

<?php require_once '../includes/footer.php'; ?>
