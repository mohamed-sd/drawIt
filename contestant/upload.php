<?php
/**
 * صفحة رفع عمل جديد
 * Upload Drawing Page
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

// الحصول على المرحلة النشطة
$active_stage = get_active_stage();

if (!$active_stage) {
    set_flash_message('لا توجد مرحلة نشطة حالياً', 'warning');
    redirect(SITE_URL . '/contestant/dashboard.php');
}

$errors = [];
$success = '';

$is_eligible = true;
$eligibility_message = '';
if ((int)$active_stage['stage_number'] > 1) {
    $stmt = $db->prepare("SELECT id FROM stages WHERE stage_number = ? LIMIT 1");
    $stmt->execute([(int)$active_stage['stage_number'] - 1]);
    $previous_stage = $stmt->fetch();

    if ($previous_stage) {
        $stmt = $db->prepare("SELECT id FROM drawings WHERE user_id = ? AND stage_id = ? AND is_qualified = 1 AND status = 'approved' AND is_published = 1 LIMIT 1");
        $stmt->execute([$user['id'], $previous_stage['id']]);
        $qualified_drawing = $stmt->fetch();

        if (!$qualified_drawing) {
            $is_eligible = false;
            $eligibility_message = 'لا يمكنك رفع عمل في هذه المرحلة إلا إذا تم ترشيح عملك في المرحلة السابقة.';
        }
    } else {
        $is_eligible = false;
        $eligibility_message = 'لا يمكن تحديد المرحلة السابقة لهذه المرحلة حالياً.';
    }
}

$stmt = $db->prepare("SELECT id, status FROM drawings WHERE user_id = ? AND stage_id = ? AND status IN ('pending', 'approved') LIMIT 1");
$stmt->execute([$user['id'], $active_stage['id']]);
$existing_drawing = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_eligible) {
        $errors[] = $eligibility_message ?: 'غير مؤهل لرفع عمل في هذه المرحلة.';
    }

    if ($existing_drawing) {
        $errors[] = 'لا يمكنك رفع أكثر من عمل واحد في هذه المرحلة طالما يوجد عمل قيد المراجعة أو معتمد.';
    }

    $title = clean_input($_POST['title'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    
    // التحقق من المدخلات
    if (empty($title)) {
        $errors[] = 'عنوان العمل مطلوب';
    }
    
    if (empty($_FILES['video']['name'])) {
        $errors[] = 'يجب رفع فيديو العمل';
    }
    
    // رفع الفيديو
    if (empty($errors) && !empty($_FILES['video']['name'])) {
        $upload_result = upload_video($_FILES['video']);
        
        if (!$upload_result['success']) {
            $errors[] = $upload_result['message'];
        }
    }
    
    // حفظ العمل في قاعدة البيانات
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO drawings (user_id, stage_id, title, description, video_path, status) 
                                  VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $user['id'],
                $active_stage['id'],
                $title,
                $description,
                $upload_result['filename']
            ]);
            
            $drawing_id = $db->lastInsertId();
            
            // إنشاء سجلات موافقة المدراء
            $stmt = $db->prepare("SELECT id FROM users WHERE role_id IN (3, 4) AND is_active = 1");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            $stmt = $db->prepare("INSERT INTO admin_approvals (drawing_id, admin_id, approval_status) VALUES (?, ?, 'pending')");
            foreach ($admins as $admin) {
                $stmt->execute([$drawing_id, $admin['id']]);
            }
            
            set_flash_message('تم رفع عملك بنجاح! سيتم مراجعته من قبل الإدارة', 'success');
            redirect(SITE_URL . '/contestant/dashboard.php');
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء حفظ العمل';
        }
    }
}

$page_title = 'رفع عمل جديد';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-upload"></i> رفع عمل جديد
                    </h4>
                </div>
                <div class="card-body p-4">
                    <!-- Current Stage Info -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> المرحلة الحالية</h5>
                        <p class="mb-0">
                            <span class="stage-badge stage-<?php echo $active_stage['stage_number']; ?>">
                                <?php echo htmlspecialchars($active_stage['name']); ?>
                            </span>
                            <br>
                            <?php echo htmlspecialchars($active_stage['description']); ?>
                        </p>
                    </div>

                    <?php if ($existing_drawing): ?>
                        <div class="alert alert-warning">
                            لديك عمل في هذه المرحلة قيد المراجعة أو معتمد بالفعل، ولا يمكن رفع عمل جديد حتى يتم رفض العمل الحالي او الترشيح للمرحلة القادمة.
                        </div>
                    <?php endif; ?>

                    <?php if (!$is_eligible): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($eligibility_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading"></i> عنوان العمل *
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                   maxlength="200" required>
                            <small class="text-muted">اختر عنواناً جذاباً يعبر عن عملك الفني</small>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-right"></i> وصف العمل
                            </label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" maxlength="1000"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="text-muted">صف عملك وما يميزه (اختياري)</small>
                        </div>

                        <!-- Video Upload -->
                        <div class="mb-4">
                            <label for="video" class="form-label">
                                <i class="fas fa-video"></i> فيديو عملية الرسم *
                            </label>
                            <input type="file" class="form-control" id="video" name="video" 
                                   accept="video/*" required onchange="previewVideo(this, 'video-preview')">
                            <small class="text-muted">
                                الحد الأقصى: <?php echo MAX_VIDEO_SIZE / (1024*1024); ?> ميجابايت | الأنواع المدعومة: MP4, AVI, MOV
                            </small>
                            
                            <!-- Video Preview -->
                            <div class="mt-3">
                                <video id="video-preview" class="w-100" style="display:none; max-height:400px; border-radius:10px;" controls></video>
                            </div>
                        </div>

                        <!-- Important Notes -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> ملاحظات هامة:</h6>
                            <ul class="mb-0">
                                <li>يجب أن يُظهر الفيديو عملية الرسم بشكل واضح</li>
                                <li>لن يُنشر عملك إلا بعد موافقة جميع المدراء</li>
                                <li>تأكد من جودة الفيديو قبل الرفع</li>
                                <li>يُفضل أن يكون الفيديو بجودة عالية</li>
                            </ul>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" <?php echo ($existing_drawing || !$is_eligible) ? 'disabled' : ''; ?>>
                                <i class="fas fa-upload"></i> رفع العمل
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5><i class="fas fa-lightbulb text-warning"></i> نصائح لعمل مميز:</h5>
                    <ul>
                        <li>صوّر عملية الرسم من البداية للنهاية</li>
                        <li>استخدم إضاءة جيدة للحصول على فيديو واضح</li>
                        <li>احرص على ثبات الكاميرا أثناء التصوير</li>
                        <li>يمكنك تسريع الفيديو لتقليل المدة</li>
                        <li>أضف موسيقى خلفية مناسبة (اختياري)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
