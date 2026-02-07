<?php
/**
 * صفحة التصويت (مجاني)
 * Free Voting Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = getDB();

// الحصول على معلومات العمل
$drawing_id = isset($_GET['drawing_id']) ? (int)$_GET['drawing_id'] : 0;

if (!$drawing_id) {
    set_flash_message('عمل غير صحيح', 'error');
    redirect(SITE_URL . '/pages/drawings.php');
}

$stmt = $db->prepare("SELECT d.*, u.full_name, s.name as stage_name, s.stage_number, s.is_free_voting
                      FROM drawings d 
                      JOIN users u ON d.user_id = u.id 
                      JOIN stages s ON d.stage_id = s.id 
                      WHERE d.id = ? AND d.is_published = 1 AND d.status = 'approved'");
$stmt->execute([$drawing_id]);
$drawing = $stmt->fetch();

if (!$drawing) {
    set_flash_message('العمل غير موجود أو غير منشور', 'error');
    redirect(SITE_URL . '/pages/drawings.php');
}

// التحقق من أن التصويت مجاني
if (!$drawing['is_free_voting']) {
    set_flash_message('هذه المرحلة تتطلب تصويتاً مدفوعاً', 'warning');
    redirect(SITE_URL . '/pages/payment.php?drawing_id=' . $drawing_id);
}

// التحقق من التصويت السابق
$voter_ip = get_client_ip();
if (has_voted($drawing_id, $drawing['stage_id'])) {
    set_flash_message('لقد صوّت لهذا العمل مسبقاً من هذا الجهاز', 'warning');
    redirect(SITE_URL . '/pages/drawings.php');
}

// معالجة التصويت
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // إضافة الصوت
        $stmt = $db->prepare("INSERT INTO votes (drawing_id, user_id, voter_ip, is_paid, vote_type) 
                              VALUES (?, ?, ?, 0, 'free')");
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
        $stmt->execute([$drawing_id, $user_id, $voter_ip]);
        
        // إضافة قيد منع التكرار
        $stmt = $db->prepare("INSERT INTO vote_restrictions (drawing_id, voter_ip, stage_id) 
                              VALUES (?, ?, ?)");
        $stmt->execute([$drawing_id, $voter_ip, $drawing['stage_id']]);
        
        // تحديث عدد الأصوات
        $stmt = $db->prepare("UPDATE drawings SET total_votes = total_votes + 1 WHERE id = ?");
        $stmt->execute([$drawing_id]);
        
        // إرسال تنبيه للمتسابق
        send_notification(
            $drawing['user_id'],
            'صوت جديد على عملك!',
            'حصل عملك "' . $drawing['title'] . '" على صوت جديد!',
            'vote_received',
            $drawing_id
        );
        
        $db->commit();
        
        set_flash_message('شكراً لك! تم تسجيل تصويتك بنجاح', 'success');
        redirect(SITE_URL . '/pages/drawings.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        set_flash_message('حدث خطأ أثناء التصويت، حاول مرة أخرى', 'error');
        redirect(SITE_URL . '/pages/drawings.php');
    }
}

$page_title = 'تأكيد التصويت';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-heart"></i> تصويت مجاني
                    </h4>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <video controls class="w-100" style="max-height:300px; border-radius:10px;">
                            <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                        </video>
                    </div>

                    <h5 class="mb-3"><?php echo htmlspecialchars($drawing['title']); ?></h5>
                    <p class="text-muted">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($drawing['full_name']); ?>
                    </p>

                    <div class="alert alert-info">
                        <p class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            هل تريد التصويت لهذا العمل؟
                        </p>
                    </div>

                    <form method="POST" action="">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-heart"></i> تأكيد التصويت
                            </button>
                            <a href="drawings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
