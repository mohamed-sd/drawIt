<?php
/**
 * تفاصيل عمل المتسابق (داخل لوحة المتسابق)
 * Contestant Drawing Details
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_contestant()) {
    set_flash_message('يجب تسجيل الدخول كمتسابق للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$db = getDB();
$user = get_current_user_data();

$drawing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$drawing_id) {
    set_flash_message('عمل غير صحيح', 'error');
    redirect(SITE_URL . '/contestant/dashboard.php');
}

$stmt = $db->prepare("SELECT d.*, s.name as stage_name, s.stage_number
                      FROM drawings d
                      JOIN stages s ON d.stage_id = s.id
                      WHERE d.id = ? AND d.user_id = ?");
$stmt->execute([$drawing_id, $user['id']]);
$drawing = $stmt->fetch();

if (!$drawing) {
    set_flash_message('العمل غير موجود أو لا تملك صلاحية الوصول', 'error');
    redirect(SITE_URL . '/contestant/dashboard.php');
}

// موافقات المدراء
$stmt = $db->prepare("SELECT aa.*, u.full_name as admin_name
                      FROM admin_approvals aa
                      JOIN users u ON aa.admin_id = u.id
                      WHERE aa.drawing_id = ?
                      ORDER BY aa.updated_at DESC");
$stmt->execute([$drawing_id]);
$approvals = $stmt->fetchAll();

$page_title = 'تفاصيل عملي';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-body p-0">
                    <div class="video-container" style="padding-bottom: 56.25%; position: relative;">
                        <video controls style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                            <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو
                        </video>
                    </div>

                    <div class="p-4">
                        <h2 class="mb-3"><?php echo htmlspecialchars($drawing['title']); ?></h2>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <p class="mb-2"><i class="fas fa-clock"></i> <strong>تاريخ الرفع:</strong> <?php echo format_arabic_date($drawing['created_at']); ?></p>
                                <p class="mb-0"><i class="fas fa-layer-group"></i> <strong>المرحلة:</strong>
                                    <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?>">
                                        <?php echo htmlspecialchars($drawing['stage_name']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="vote-count">
                                    <i class="fas fa-heart text-danger"></i>
                                    <?php echo number_format($drawing['total_votes']); ?> صوت
                                </div>
                            </div>
                        </div>

                        <?php if ($drawing['description']): ?>
                            <div class="alert alert-light border">
                                <h6><i class="fas fa-align-right"></i> الوصف:</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($drawing['description'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> حالة العمل:</h6>
                            <?php
                            switch ($drawing['status']) {
                                case 'pending':
                                    echo '<span class="status-badge status-pending">قيد المراجعة</span>';
                                    break;
                                case 'approved':
                                    if ($drawing['is_published']) {
                                        echo '<span class="status-badge status-approved">منشور</span>';
                                    } else {
                                        echo '<span class="status-badge status-pending">معتمد - بانتظار النشر</span>';
                                    }
                                    break;
                                case 'rejected':
                                    echo '<span class="status-badge status-rejected">مرفوض</span>';
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approvals Sidebar -->
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users-cog"></i> موافقات المدراء</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($approvals)): ?>
                        <div class="text-muted">لا توجد موافقات مسجلة بعد.</div>
                    <?php else: ?>
                        <?php foreach ($approvals as $approval): ?>
                            <div class="mb-3 p-3" style="border-right: 4px solid 
                                <?php 
                                echo $approval['approval_status'] === 'approved' ? '#28a745' :
                                     ($approval['approval_status'] === 'rejected' ? '#dc3545' : '#ffc107');
                                ?>; border-radius:5px;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong><?php echo htmlspecialchars($approval['admin_name']); ?></strong>
                                    <?php
                                    $status_icon = '';
                                    $status_text = '';
                                    $status_class = '';
                                    switch ($approval['approval_status']) {
                                        case 'approved':
                                            $status_icon = 'fa-check-circle';
                                            $status_text = 'موافق';
                                            $status_class = 'text-success';
                                            break;
                                        case 'rejected':
                                            $status_icon = 'fa-times-circle';
                                            $status_text = 'غير موافق';
                                            $status_class = 'text-danger';
                                            break;
                                        default:
                                            $status_icon = 'fa-clock';
                                            $status_text = 'قيد الانتظار';
                                            $status_class = 'text-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </span>
                                </div>
                                <?php if (!empty($approval['notes'])): ?>
                                    <small class="text-muted"><i class="fas fa-comment"></i> <?php echo htmlspecialchars($approval['notes']); ?></small>
                                <?php endif; ?>
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-clock"></i> <?php echo time_elapsed($approval['updated_at']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-right"></i> رجوع للوحة التحكم
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
