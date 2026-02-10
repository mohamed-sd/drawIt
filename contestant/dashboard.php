<?php
/**
 * لوحة تحكم المتسابق
 * Contestant Dashboard
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول كمتسابق
if (!is_logged_in() || !is_contestant()) {
    set_flash_message('يجب تسجيل الدخول كمتسابق للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$user = get_current_user_data();
$db = getDB();

// الحصول على أعمال المتسابق
$stmt = $db->prepare("SELECT d.*, s.name as stage_name, s.stage_number, s.is_free_voting, w.position as winner_position, c.name as competition_name
                      FROM drawings d 
                      JOIN stages s ON d.stage_id = s.id
                      JOIN competitions c ON d.competition_id = c.id
                      LEFT JOIN winners w ON w.drawing_id = d.id
                      WHERE d.user_id = ? 
                      ORDER BY d.created_at DESC");
$stmt->execute([$user['id']]);
$my_drawings = $stmt->fetchAll();

// إحصائيات المتسابق
$stmt = $db->prepare("SELECT COUNT(*) FROM drawings WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_my_drawings = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT SUM(total_votes) FROM drawings WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_my_votes = $stmt->fetchColumn() ?: 0;

$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user['id']]);
$unread_notifications = $stmt->fetchColumn();

// مسابقات المتسابق
$stmt = $db->prepare("SELECT c.*, s.id as active_stage_id, s.name as active_stage_name, s.stage_number
                      FROM competitions c
                      JOIN competition_contestants cc ON cc.competition_id = c.id
                      LEFT JOIN stages s ON s.competition_id = c.id AND s.is_active = 1
                      WHERE cc.user_id = ? AND cc.status = 'active'
                      ORDER BY c.created_at DESC");
$stmt->execute([$user['id']]);
$my_competitions = $stmt->fetchAll();

$page_title = 'لوحة تحكم المتسابق';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <!-- Welcome Section -->
    <div class="dashboard-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-3">مرحباً، <?php echo htmlspecialchars($user['full_name']); ?>! <i class="fas fa-hand-sparkles text-warning"></i></h2>
                <p class="lead text-muted mb-0">
                    أهلاً بك في لوحة التحكم الخاصة بك. من هنا يمكنك إدارة أعمالك ومتابعة تقدمك في المسابقة.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="upload.php" class="btn btn-primary btn-lg pulse">
                    <i class="fas fa-upload"></i> ارفع عملك الجديد
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($my_competitions)): ?>
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-award"></i> مسابقاتي</h3>
                <a href="../pages/competitions.php" class="btn btn-outline-primary btn-sm">عرض المسابقات</a>
            </div>
            <div class="row g-3">
                <?php foreach ($my_competitions as $competition): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="mb-2"><?php echo htmlspecialchars($competition['name']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($competition['category'] ?? ''); ?></p>
                                <?php if (!empty($competition['active_stage_id'])): ?>
                                    <span class="stage-badge stage-<?php echo (int)$competition['stage_number']; ?>">
                                        <?php echo htmlspecialchars($competition['active_stage_name']); ?>
                                    </span>
                                    <a href="upload.php?competition_id=<?php echo $competition['id']; ?>" class="btn btn-sm btn-primary mt-3">
                                        <i class="fas fa-upload"></i> رفع عمل للمسابقة
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">لا توجد مرحلة نشطة حالياً</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card stat-primary">
                <i class="fas fa-palette fa-3x mb-3"></i>
                <h3><?php echo number_format($total_my_drawings); ?></h3>
                <p>مشاركاتي</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-success">
                <i class="fas fa-heart fa-3x mb-3"></i>
                <h3><?php echo number_format($total_my_votes); ?></h3>
                <p>إجمالي الأصوات</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-warning">
                <i class="fas fa-bell fa-3x mb-3"></i>
                <h3><?php echo number_format($unread_notifications); ?></h3>
                <p>تنبيهات جديدة</p>
            </div>
        </div>
    </div>

    <!-- My Drawings -->
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-images"></i> مشاركاتي</h3>
            <a href="upload.php" class="btn btn-outline-primary">
                <i class="fas fa-plus"></i> إضافة مشاركة جديدة
            </a>
        </div>

        <?php if (empty($my_drawings)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
                <h4 class="text-muted">لم ترفع أي مشاركات بعد</h4>
                <p class="text-muted">ابدأ الآن بتحميل أول مشاركة لك في المسابقة</p>
                <a href="upload.php" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-upload"></i> ارفع مشاركتك الأولى
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>المسابقة</th>
                            <th>المرحلة</th>
                            <th>الحالة</th>
                            <th>الأصوات</th>
                            <th>تاريخ الرفع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_drawings as $drawing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($drawing['competition_name']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($drawing['title']); ?></strong>
                                    <?php if (!empty($drawing['winner_position'])): ?>
                                        <span class="badge bg-warning text-dark ms-2"><i class="fas fa-crown"></i> فائز</span>
                                    <?php elseif ((int)$drawing['is_qualified'] === 1): ?>
                                        <span class="badge bg-success ms-2"><i class="fas fa-trophy"></i> مرشح</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?>">
                                        <?php echo htmlspecialchars($drawing['stage_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    $status_icon = '';
                                    
                                    switch ($drawing['status']) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'قيد المراجعة';
                                            $status_icon = 'fa-clock';
                                            break;
                                        case 'approved':
                                            if ($drawing['is_published']) {
                                                $status_class = 'status-approved';
                                                $status_text = 'منشور';
                                                $status_icon = 'fa-check-circle';
                                            } else {
                                                $status_class = 'status-pending';
                                                $status_text = 'معتمد - بانتظار النشر';
                                                $status_icon = 'fa-clock';
                                            }
                                            break;
                                        case 'rejected':
                                            $status_class = 'status-rejected';
                                            $status_text = 'مرفوض';
                                            $status_icon = 'fa-times-circle';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-danger">
                                        <i class="fas fa-heart"></i>
                                        <?php echo number_format($drawing['total_votes']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo time_elapsed($drawing['created_at']); ?>
                                </td>
                                <td>
                                    <a href="drawing_details.php?id=<?php echo $drawing['id']; ?>" 
                                       class="btn btn-sm btn-info" title="التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($drawing['status'] === 'pending'): ?>
                                        <a href="edit_drawing.php?id=<?php echo $drawing['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Notifications -->
    <?php
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $recent_notifications = $stmt->fetchAll();
    ?>

    <?php if (!empty($recent_notifications)): ?>
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-bell"></i> آخر التنبيهات</h3>
                <a href="../pages/notifications.php" class="btn btn-outline-primary btn-sm">
                    عرض الكل
                </a>
            </div>

            <?php foreach ($recent_notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon <?php echo $notification['type']; ?>">
                            <?php
                            $icon = 'fa-bell';
                            switch ($notification['type']) {
                                case 'vote_received': $icon = 'fa-heart'; break;
                                case 'drawing_approved': $icon = 'fa-check'; break;
                                case 'drawing_rejected': $icon = 'fa-times'; break;
                                case 'stage_qualified': $icon = 'fa-trophy'; break;
                                case 'winner': $icon = 'fa-crown'; break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?php echo time_elapsed($notification['created_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
