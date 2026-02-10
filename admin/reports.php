<?php
/**
 * التقارير والإحصائيات
 * Reports Page
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

// إحصائيات عامة
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_contestants = 0;
$total_admins = 0;
$total_drawings = 0;
$published_drawings = 0;
$pending_drawings = 0;
$total_votes = 0;
$total_paid_votes = 0;
$total_payments = 0;

if ($competition_id) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM competition_contestants WHERE competition_id = ? AND status = 'active'");
    $stmt->execute([$competition_id]);
    $total_contestants = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM competition_admins WHERE competition_id = ?");
    $stmt->execute([$competition_id]);
    $total_admins = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM drawings WHERE competition_id = ?");
    $stmt->execute([$competition_id]);
    $total_drawings = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM drawings WHERE competition_id = ? AND is_published = 1");
    $stmt->execute([$competition_id]);
    $published_drawings = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM drawings WHERE competition_id = ? AND status = 'pending'");
    $stmt->execute([$competition_id]);
    $pending_drawings = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM votes v JOIN drawings d ON v.drawing_id = d.id WHERE d.competition_id = ?");
    $stmt->execute([$competition_id]);
    $total_votes = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM votes v JOIN drawings d ON v.drawing_id = d.id WHERE d.competition_id = ? AND v.is_paid = 1");
    $stmt->execute([$competition_id]);
    $total_paid_votes = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT SUM(p.amount) FROM payments p JOIN drawings d ON p.drawing_id = d.id WHERE d.competition_id = ? AND p.status = 'completed'");
    $stmt->execute([$competition_id]);
    $total_payments = $stmt->fetchColumn();
}

// أفضل الأعمال
$top_drawings = [];
if ($competition_id) {
    $stmt = $db->prepare("SELECT d.title, u.full_name, d.total_votes
                          FROM drawings d
                          JOIN users u ON d.user_id = u.id
                          WHERE d.is_published = 1 AND d.competition_id = ?
                          ORDER BY d.total_votes DESC
                          LIMIT 5");
    $stmt->execute([$competition_id]);
    $top_drawings = $stmt->fetchAll();
}

$page_title = 'التقارير';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-bar"></i> التقارير والإحصائيات</h2>
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

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="stat-card stat-primary"><h3><?php echo number_format($total_users); ?></h3><p>إجمالي المستخدمين</p></div></div>
            <div class="col-md-3"><div class="stat-card stat-success"><h3><?php echo number_format($total_contestants); ?></h3><p>متسابقو المسابقة</p></div></div>
            <div class="col-md-3"><div class="stat-card stat-warning"><h3><?php echo number_format($total_admins); ?></h3><p>لجنة التحكيم</p></div></div>
            <div class="col-md-3"><div class="stat-card" style="background: var(--gradient-secondary);"><h3><?php echo number_format($total_drawings); ?></h3><p>أعمال المسابقة</p></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="stat-card stat-success"><h3><?php echo number_format($published_drawings); ?></h3><p>أعمال منشورة</p></div></div>
            <div class="col-md-3"><div class="stat-card stat-warning"><h3><?php echo number_format($pending_drawings); ?></h3><p>قيد المراجعة</p></div></div>
            <div class="col-md-3"><div class="stat-card stat-primary"><h3><?php echo number_format($total_votes); ?></h3><p>إجمالي الأصوات</p></div></div>
            <div class="col-md-3"><div class="stat-card" style="background: var(--gradient-success);"><h3><?php echo number_format($total_paid_votes); ?></h3><p>أصوات مدفوعة</p></div></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-trophy"></i> أفضل الأعمال</h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_drawings)): ?>
                    <div class="text-muted text-center">لا توجد بيانات بعد</div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($top_drawings as $drawing): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($drawing['title']); ?></strong>
                                    <div class="text-muted small"><?php echo htmlspecialchars($drawing['full_name']); ?></div>
                                </div>
                                <span class="badge bg-danger rounded-pill">
                                    <i class="fas fa-heart"></i> <?php echo number_format($drawing['total_votes']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 alert alert-info">
            إجمالي المدفوعات المكتملة: <strong><?php echo number_format((float)$total_payments, 2); ?> ريال</strong>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
