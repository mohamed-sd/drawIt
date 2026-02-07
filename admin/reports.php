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

// إحصائيات عامة
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_contestants = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
$total_admins = $db->query("SELECT COUNT(*) FROM users WHERE role_id IN (3,4)")->fetchColumn();
$total_drawings = $db->query("SELECT COUNT(*) FROM drawings")->fetchColumn();
$published_drawings = $db->query("SELECT COUNT(*) FROM drawings WHERE is_published = 1")->fetchColumn();
$pending_drawings = $db->query("SELECT COUNT(*) FROM drawings WHERE status = 'pending'")->fetchColumn();
$total_votes = $db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$total_paid_votes = $db->query("SELECT COUNT(*) FROM votes WHERE is_paid = 1")->fetchColumn();
$total_payments = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetchColumn();

// أفضل الأعمال
$stmt = $db->query("SELECT d.title, u.full_name, d.total_votes
                    FROM drawings d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.is_published = 1
                    ORDER BY d.total_votes DESC
                    LIMIT 5");
$top_drawings = $stmt->fetchAll();

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

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="stat-card stat-primary"><h3><?php echo number_format($total_users); ?></h3><p>إجمالي المستخدمين</p></div></div>
            <div class="col-md-3"><div class="stat-card stat-success"><h3><?php echo number_format($total_contestants); ?></h3><p>المتسابقين</p></div></div>
            <div class="col-md-3"><div class="stat-card stat-warning"><h3><?php echo number_format($total_admins); ?></h3><p>المدراء</p></div></div>
            <div class="col-md-3"><div class="stat-card" style="background: var(--gradient-secondary);"><h3><?php echo number_format($total_drawings); ?></h3><p>الأعمال</p></div></div>
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
