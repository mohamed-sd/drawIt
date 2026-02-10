<?php
/**
 * لوحة تحكم المدير
 * Admin Dashboard
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول كمدير
if (!is_logged_in() || !is_admin()) {
    set_flash_message('يجب تسجيل الدخول كمدير للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$user = get_current_user_data();
$db = getDB();
$admin_competitions = get_admin_competitions($user['id']);
$active_competition = get_admin_active_competition($user['id']);
$competition_id = $active_competition['id'] ?? null;
$active_stage = $competition_id ? get_active_stage($competition_id) : null;

// إحصائيات عامة
$pending_drawings = 0;
$published_drawings = 0;
$total_contestants = 0;
$total_votes = 0;

if ($competition_id) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM drawings WHERE competition_id = ? AND status = 'pending'");
    $stmt->execute([$competition_id]);
    $pending_drawings = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM drawings WHERE competition_id = ? AND is_published = 1");
    $stmt->execute([$competition_id]);
    $published_drawings = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM competition_contestants WHERE competition_id = ? AND status = 'active'");
    $stmt->execute([$competition_id]);
    $total_contestants = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM votes v JOIN drawings d ON v.drawing_id = d.id WHERE d.competition_id = ?");
    $stmt->execute([$competition_id]);
    $total_votes = $stmt->fetchColumn();
}

// الأعمال قيد المراجعة التي تحتاج موافقة المدير الحالي
$pending_reviews = [];
if ($competition_id) {
    $stmt = $db->prepare("SELECT d.*, u.full_name, s.name as stage_name,
                          (SELECT COUNT(*) FROM admin_approvals WHERE drawing_id = d.id AND approval_status = 'approved') as approved_count,
                          (SELECT COUNT(*) FROM admin_approvals WHERE drawing_id = d.id) as total_admins,
                          (SELECT approval_status FROM admin_approvals WHERE drawing_id = d.id AND admin_id = ?) as my_status
                          FROM drawings d
                          JOIN users u ON d.user_id = u.id
                          JOIN stages s ON d.stage_id = s.id
                          WHERE d.status = 'pending' AND d.competition_id = ?
                          ORDER BY d.created_at DESC
                          LIMIT 10");
    $stmt->execute([$user['id'], $competition_id]);
    $pending_reviews = $stmt->fetchAll();
}

$page_title = 'لوحة تحكم المدير';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <!-- Welcome Section -->
    <div class="dashboard-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-3">
                    <i class="fas fa-user-shield text-primary"></i>
                    مرحباً، <?php echo htmlspecialchars($user['full_name']); ?>!
                </h2>
                <p class="lead text-muted mb-0">
                    أهلاً بك في لوحة التحكم الإدارية. من هنا يمكنك إدارة المسابقات ومراجعة الأعمال.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if (is_super_admin()): ?>
                    <a href="manage_admins.php" class="btn btn-primary">
                        <i class="fas fa-users-cog"></i> إدارة المدراء
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($admin_competitions)): ?>
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="fas fa-award"></i> المسابقة الحالية</h5>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($active_competition['name'] ?? ''); ?></p>
                    <small class="text-muted">
                        المرحلة الحالية: <?php echo htmlspecialchars($active_stage['name'] ?? 'غير محددة'); ?>
                    </small>
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
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            لا توجد مسابقات مرتبطة بحسابك حالياً. تواصل مع المدير الرئيسي لتعيينك ضمن لجنة تحكيم.
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-warning">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3><?php echo number_format($pending_drawings); ?></h3>
                <p>أعمال قيد المراجعة</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-success">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h3><?php echo number_format($published_drawings); ?></h3>
                <p>أعمال منشورة</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-primary">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h3><?php echo number_format($total_contestants); ?></h3>
                <p>متسابق</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-heart fa-3x mb-3"></i>
                <h3><?php echo number_format($total_votes); ?></h3>
                <p>إجمالي الأصوات</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <a href="review_drawings.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100 hover-shadow">
                    <i class="fas fa-tasks fa-3x text-warning mb-3"></i>
                    <h5>مراجعة الأعمال</h5>
                    <p class="text-muted mb-0">مراجعة وقبول/رفض الأعمال</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="manage_stages.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100 hover-shadow">
                    <i class="fas fa-layer-group fa-3x text-primary mb-3"></i>
                    <h5>إدارة المراحل</h5>
                    <p class="text-muted mb-0">إدارة مراحل المسابقة</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="qualifications.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100 hover-shadow">
                    <i class="fas fa-trophy fa-3x text-success mb-3"></i>
                    <h5>الترقيات</h5>
                    <p class="text-muted mb-0">ترقية المتسابقين للمراحل التالية</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reports.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100 hover-shadow">
                    <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                    <h5>التقارير</h5>
                    <p class="text-muted mb-0">عرض التقارير والإحصائيات</p>
                </div>
            </a>
        </div>
        <?php if (is_super_admin()): ?>
            <div class="col-md-3">
                <a href="manage_competitions.php" class="text-decoration-none">
                    <div class="card text-center p-4 h-100 hover-shadow">
                        <i class="fas fa-award fa-3x text-secondary mb-3"></i>
                        <h5>إدارة المسابقات</h5>
                        <p class="text-muted mb-0">إنشاء المسابقات وتعيين اللجان</p>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pending Reviews -->
    <?php if ($competition_id && !empty($pending_reviews)): ?>
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-clock"></i> أعمال تحتاج مراجعة</h3>
                <a href="review_drawings.php" class="btn btn-outline-primary">
                    عرض الكل
                </a>
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
                            <th>التاريخ</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_reviews as $drawing): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($drawing['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($drawing['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($drawing['stage_name']); ?></td>
                                <td>
                                    <div class="progress" style="height: 25px;">
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
                                    <a href="review_drawing.php?id=<?php echo $drawing['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> مراجعة
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($competition_id): ?>
        <div class="dashboard-card text-center py-5">
            <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
            <h4>لا توجد أعمال قيد المراجعة حالياً</h4>
            <p class="text-muted">جميع الأعمال تمت مراجعتها</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
