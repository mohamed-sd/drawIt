<?php
/**
 * صفحة مراجعة عمل واحد
 * Review Single Drawing
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

// الحصول على معلومات العمل
$drawing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$drawing_id) {
    set_flash_message('عمل غير صحيح', 'error');
    redirect(SITE_URL . '/admin/review_drawings.php');
}

$stmt = $db->prepare("SELECT d.*, u.full_name, u.email, s.name as stage_name, s.stage_number, c.name as competition_name
                      FROM drawings d
                      JOIN users u ON d.user_id = u.id
                      JOIN stages s ON d.stage_id = s.id
                      JOIN competitions c ON d.competition_id = c.id
                      WHERE d.id = ?");
$stmt->execute([$drawing_id]);
$drawing = $stmt->fetch();

if (!$drawing) {
    set_flash_message('العمل غير موجود', 'error');
    redirect(SITE_URL . '/admin/review_drawings.php');
}

if (!admin_has_competition_access($user['id'], (int)$drawing['competition_id'])) {
    set_flash_message('لا تمتلك صلاحية مراجعة هذه المسابقة', 'error');
    redirect(SITE_URL . '/admin/review_drawings.php');
}

// الحصول على جميع موافقات المدراء
$stmt = $db->prepare("SELECT aa.*, u.full_name as admin_name
                      FROM admin_approvals aa
                      JOIN users u ON aa.admin_id = u.id
                      WHERE aa.drawing_id = ?
                      ORDER BY aa.updated_at DESC");
$stmt->execute([$drawing_id]);
$approvals = $stmt->fetchAll();

// الحصول على موافقة المدير الحالي
$my_approval = null;
foreach ($approvals as $approval) {
    if ($approval['admin_id'] == $user['id']) {
        $my_approval = $approval;
        break;
    }
}

$errors = [];

// معالجة قرار المدير
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';
    $notes = clean_input($_POST['notes'] ?? '');
    
    if (empty($decision) || !in_array($decision, ['approved', 'rejected'])) {
        $errors[] = 'يجب اختيار قرار صحيح';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // تحديث موافقة المدير
            $stmt = $db->prepare("UPDATE admin_approvals SET approval_status = ?, notes = ?, updated_at = NOW() 
                                  WHERE drawing_id = ? AND admin_id = ?");
            $stmt->execute([$decision, $notes, $drawing_id, $user['id']]);
            
            // التحقق إذا وافق جميع المدراء
            $stmt = $db->prepare("SELECT COUNT(*) as total, 
                                  SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                                  SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                                  FROM admin_approvals WHERE drawing_id = ?");
            $stmt->execute([$drawing_id]);
            $approval_stats = $stmt->fetch();
            
            // إذا وافق الجميع، نشر العمل
            if ($approval_stats['approved'] == $approval_stats['total']) {
                $stmt = $db->prepare("UPDATE drawings SET status = 'approved', is_published = 1 WHERE id = ?");
                $stmt->execute([$drawing_id]);
                
                // إرسال تنبيه للمتسابق
                send_notification(
                    $drawing['user_id'],
                    'تم الموافقة على عملك!',
                    'تمت الموافقة على عملك "' . $drawing['title'] . '" ونشره في المسابقة!',
                    'drawing_approved',
                    $drawing_id
                );

            }
            // إذا رفض أي مدير، رفض العمل
            elseif ($approval_stats['rejected'] > 0) {
                $stmt = $db->prepare("UPDATE drawings SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$drawing_id]);
                
                // إرسال تنبيه للمتسابق
                send_notification(
                    $drawing['user_id'],
                    'تم رفض عملك',
                    'نأسف، تم رفض عملك "' . $drawing['title'] . '" من قبل الإدارة.',
                    'drawing_rejected',
                    $drawing_id
                );
            }
            
            $db->commit();
            
            set_flash_message('تم حفظ قرارك بنجاح', 'success');
            redirect(SITE_URL . '/admin/review_drawings.php');
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'حدث خطأ أثناء حفظ القرار';
        }
    }
}

$page_title = 'مراجعة العمل';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Drawing Details -->
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-eye"></i> مراجعة العمل
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Video -->
                    <div class="mb-4">
                        <video controls class="w-100" style="max-height:500px; border-radius:10px;">
                            <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو
                        </video>
                    </div>

                    <!-- Drawing Info -->
                    <h3 class="mb-3"><?php echo htmlspecialchars($drawing['title']); ?></h3>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="fas fa-user"></i>
                                <strong>المتسابق:</strong>
                                <?php echo htmlspecialchars($drawing['full_name']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope"></i>
                                <strong>البريد:</strong>
                                <?php echo htmlspecialchars($drawing['email']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="fas fa-layer-group"></i>
                                <strong>المرحلة:</strong>
                                <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?>">
                                    <?php echo htmlspecialchars($drawing['stage_name']); ?>
                                </span>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-award"></i>
                                <strong>المسابقة:</strong>
                                <?php echo htmlspecialchars($drawing['competition_name']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock"></i>
                                <strong>تاريخ الرفع:</strong>
                                <?php echo format_arabic_date($drawing['created_at']); ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($drawing['description']): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> الوصف:</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($drawing['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Decision Form -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($drawing['status'] === 'pending'): ?>
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="fas fa-gavel"></i> قرارك</h5>
                                
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">القرار *</label>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="decision" 
                                                           id="approve" value="approved" required
                                                           <?php echo ($my_approval && $my_approval['approval_status'] === 'approved') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label text-success fw-bold" for="approve">
                                                        <i class="fas fa-check-circle"></i> موافق
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="decision" 
                                                           id="reject" value="rejected" required
                                                           <?php echo ($my_approval && $my_approval['approval_status'] === 'rejected') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label text-danger fw-bold" for="reject">
                                                        <i class="fas fa-times-circle"></i> غير موافق
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">ملاحظات (اختياري)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $my_approval ? htmlspecialchars($my_approval['notes'] ?? '') : ''; ?></textarea>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> حفظ القرار
                                        </button>
                                        <a href="review_drawings.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-right"></i> رجوع
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            هذا العمل تمت مراجعته بالفعل وحالته:
                            <strong>
                                <?php
                                echo $drawing['status'] === 'approved' ? 'معتمد' : 'مرفوض';
                                ?>
                            </strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Approvals Sidebar -->
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog"></i> موافقات المدراء
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $approved_count = 0;
                    $rejected_count = 0;
                    $pending_count = 0;
                    
                    foreach ($approvals as $approval) {
                        switch ($approval['approval_status']) {
                            case 'approved': $approved_count++; break;
                            case 'rejected': $rejected_count++; break;
                            default: $pending_count++;
                        }
                    }
                    
                    $total_admins = count($approvals);
                    ?>

                    <!-- Summary -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>الموافقون:</span>
                            <strong class="text-success"><?php echo $approved_count; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>الرافضون:</span>
                            <strong class="text-danger"><?php echo $rejected_count; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>قيد الانتظار:</span>
                            <strong class="text-warning"><?php echo $pending_count; ?></strong>
                        </div>
                        <hr>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: <?php echo ($approved_count / $total_admins) * 100; ?>%">
                                <?php echo round(($approved_count / $total_admins) * 100); ?>%
                            </div>
                        </div>
                    </div>

                    <!-- Detailed List -->
                    <h6 class="mb-3">التفاصيل:</h6>
                    <?php foreach ($approvals as $approval): ?>
                        <div class="mb-3 p-3 <?php echo $approval['admin_id'] == $user['id'] ? 'bg-light' : ''; ?>" 
                             style="border-right: 4px solid 
                             <?php 
                             echo $approval['approval_status'] === 'approved' ? '#28a745' : 
                                  ($approval['approval_status'] === 'rejected' ? '#dc3545' : '#ffc107'); 
                             ?>; border-radius:5px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>
                                    <?php echo htmlspecialchars($approval['admin_name']); ?>
                                    <?php if ($approval['admin_id'] == $user['id']): ?>
                                        <span class="badge bg-primary">أنت</span>
                                    <?php endif; ?>
                                </strong>
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
                                    <i class="fas <?php echo $status_icon; ?>"></i>
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                            <?php if ($approval['notes']): ?>
                                <small class="text-muted">
                                    <i class="fas fa-comment"></i>
                                    <?php echo htmlspecialchars($approval['notes']); ?>
                                </small>
                            <?php endif; ?>
                            <div class="text-muted small mt-1">
                                <i class="fas fa-clock"></i>
                                <?php echo time_elapsed($approval['updated_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
