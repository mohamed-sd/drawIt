<?php
/**
 * صفحة التنبيهات
 * Notifications Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    set_flash_message('يجب تسجيل الدخول أولاً', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$user = get_current_user_data();
$db = getDB();

// تحديد جميع التنبيهات كمقروءة
if (isset($_GET['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    redirect(SITE_URL . '/pages/notifications.php');
}

// تحديد تنبيه واحد كمقروء
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['mark_read'], $user['id']]);
    redirect(SITE_URL . '/pages/notifications.php');
}

// الحصول على جميع التنبيهات
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

// عدد غير المقروءة
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user['id']]);
$unread_count = $stmt->fetchColumn();

$page_title = 'التنبيهات';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-bell"></i> التنبيهات
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </h2>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="btn btn-outline-primary">
                    <i class="fas fa-check-double"></i> تحديد الكل كمقروء
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-5x text-muted mb-3"></i>
                <h4 class="text-muted">لا توجد تنبيهات</h4>
                <p class="text-muted">سنرسل لك تنبيهات عند حدوث أي جديد</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="d-flex align-items-start">
                        <!-- Icon -->
                        <div class="notification-icon 
                            <?php
                            switch ($notification['type']) {
                                case 'vote_received': echo 'success'; break;
                                case 'drawing_approved': echo 'success'; break;
                                case 'drawing_rejected': echo 'danger'; break;
                                case 'stage_qualified': echo 'success'; break;
                                case 'stage_not_qualified': echo 'warning'; break;
                                case 'winner': echo 'success'; break;
                                default: echo 'info';
                            }
                            ?>">
                            <?php
                            $icon = 'fa-bell';
                            switch ($notification['type']) {
                                case 'vote_received': $icon = 'fa-heart'; break;
                                case 'drawing_approved': $icon = 'fa-check'; break;
                                case 'drawing_rejected': $icon = 'fa-times'; break;
                                case 'stage_qualified': $icon = 'fa-trophy'; break;
                                case 'stage_not_qualified': $icon = 'fa-info'; break;
                                case 'winner': $icon = 'fa-crown'; break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>

                        <!-- Content -->
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        <?php echo time_elapsed($notification['created_at']); ?>
                                    </small>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="تحديد كمقروء">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if ($notification['drawing_id']): ?>
                                <a href="<?php echo SITE_URL; ?>/pages/drawing_details.php?id=<?php echo $notification['drawing_id']; ?>" 
                                   class="btn btn-sm btn-link">
                                    <i class="fas fa-eye"></i> عرض العمل
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
