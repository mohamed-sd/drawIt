<?php
/**
 * صفحة تفاصيل عمل واحد
 * Drawing Details Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = getDB();

// الحصول على معلومات العمل
$drawing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$drawing_id) {
    set_flash_message('عمل غير صحيح', 'error');
    redirect(SITE_URL . '/pages/drawings.php');
}

$stmt = $db->prepare("SELECT d.*, u.full_name, u.id as user_id, s.name as stage_name, s.stage_number, s.is_free_voting, c.name as competition_name, c.id as competition_id
                      FROM drawings d
                      JOIN users u ON d.user_id = u.id
                      JOIN stages s ON d.stage_id = s.id
                      JOIN competitions c ON d.competition_id = c.id
                      WHERE d.id = ?");
$stmt->execute([$drawing_id]);
$drawing = $stmt->fetch();

if (!$drawing) {
    set_flash_message('العمل غير موجود', 'error');
    $redirect_url = SITE_URL . '/pages/drawings.php';
    if (!empty($drawing['competition_id'])) {
        $redirect_url .= '?competition_id=' . (int)$drawing['competition_id'];
    }
    redirect($redirect_url);
}

// التحقق من أن العمل منشور (إلا إذا كان المتسابق صاحب العمل أو مدير)
$is_owner = is_logged_in() && get_current_user_data()['id'] == $drawing['user_id'];
$can_view = $drawing['is_published'] || $is_owner || is_admin();

if (!$can_view) {
    set_flash_message('هذا العمل غير منشور بعد', 'warning');
    $redirect_url = SITE_URL . '/pages/drawings.php';
    if (!empty($drawing['competition_id'])) {
        $redirect_url .= '?competition_id=' . (int)$drawing['competition_id'];
    }
    redirect($redirect_url);
}

// الحصول على التصويتات الأخيرة
$stmt = $db->prepare("SELECT v.*, u.full_name 
                      FROM votes v 
                      LEFT JOIN users u ON v.user_id = u.id 
                      WHERE v.drawing_id = ? 
                      ORDER BY v.created_at DESC 
                      LIMIT 10");
$stmt->execute([$drawing_id]);
$recent_votes = $stmt->fetchAll();

$page_title = htmlspecialchars($drawing['title']);
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-body p-0">
                    <!-- Video -->
                    <div class="video-container" style="padding-bottom: 56.25%; position: relative;">
                        <video controls style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                            <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو
                        </video>
                    </div>

                    <!-- Details -->
                    <div class="p-4">
                        <h2 class="mb-3"><?php echo htmlspecialchars($drawing['title']); ?></h2>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <p class="mb-2">
                                    <i class="fas fa-user"></i>
                                    <strong>المتسابق:</strong>
                                    <?php echo htmlspecialchars($drawing['full_name']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock"></i>
                                    <strong>تاريخ الرفع:</strong>
                                    <?php echo format_arabic_date($drawing['created_at']); ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?> d-block mb-2">
                                    <?php echo htmlspecialchars($drawing['stage_name']); ?>
                                </span>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-award"></i> <?php echo htmlspecialchars($drawing['competition_name']); ?>
                                </div>
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

                        <!-- Status for Owner -->
                        <?php if ($is_owner): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> حالة عملك:</h6>
                                <?php
                                switch ($drawing['status']) {
                                    case 'pending':
                                        echo '<p class="mb-0"><span class="status-badge status-pending">قيد المراجعة</span> - عملك تحت المراجعة من قبل الإدارة</p>';
                                        break;
                                    case 'approved':
                                        if ($drawing['is_published']) {
                                            echo '<p class="mb-0"><span class="status-badge status-approved">منشور</span> - عملك معتمد ومنشور للجمهور</p>';
                                        } else {
                                            echo '<p class="mb-0"><span class="status-badge status-pending">معتمد - بانتظار النشر</span></p>';
                                        }
                                        break;
                                    case 'rejected':
                                        echo '<p class="mb-0"><span class="status-badge status-rejected">مرفوض</span> - عملك لم يتم قبوله من الإدارة</p>';
                                        break;
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Voting -->
                        <?php if ($drawing['is_published']): ?>
                            <?php
                            $can_vote = !has_voted($drawing_id, $drawing['stage_id']);
                            ?>
                            <div class="mt-4">
                                <?php if ($drawing['is_free_voting']): ?>
                                    <a href="vote.php?drawing_id=<?php echo $drawing_id; ?>" 
                                       class="btn btn-lg w-100 vote-btn"
                                       <?php echo !$can_vote ? 'disabled' : ''; ?>>
                                        <i class="fas fa-heart"></i>
                                        <?php echo $can_vote ? 'صوّت مجاناً لهذا العمل' : 'لقد صوّت لهذا العمل مسبقاً'; ?>
                                    </a>
                                <?php else: ?>
                                    <a href="payment.php?drawing_id=<?php echo $drawing_id; ?>" 
                                       class="btn btn-lg w-100 vote-btn"
                                       <?php echo !$can_vote ? 'disabled' : ''; ?>>
                                        <i class="fas fa-coins"></i>
                                        <?php echo $can_vote ? 'صوّت لهذا العمل (' . VOTE_PRICE . ' ريال)' : 'لقد صوّت لهذا العمل مسبقاً'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Votes -->
            <?php if (!empty($recent_votes) && $drawing['is_published']): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-heart text-danger"></i> آخر التصويتات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_votes as $vote): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-circle text-muted"></i>
                                        <?php echo $vote['full_name'] ? htmlspecialchars($vote['full_name']) : 'زائر'; ?>
                                        <?php if ($vote['is_paid']): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-coins"></i> مدفوع
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        <?php echo time_elapsed($vote['created_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Stats -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar"></i> إحصائيات العمل
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>إجمالي الأصوات:</span>
                            <strong class="text-danger"><?php echo number_format($drawing['total_votes']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>أصوات مدفوعة:</span>
                            <strong class="text-warning"><?php echo number_format($drawing['total_paid_votes']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>أصوات مجانية:</span>
                            <strong class="text-success"><?php echo number_format($drawing['total_votes'] - $drawing['total_paid_votes']); ?></strong>
                        </div>
                    </div>

                    <div class="alert alert-light mb-0">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            آخر تحديث: <?php echo time_elapsed($drawing['updated_at']); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Share -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-share-alt"></i> شارك العمل
                    </h6>
                </div>
                <div class="card-body text-center">
                    <p class="small text-muted mb-3">ساعد المتسابق بمشاركة عمله</p>
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-primary" onclick="alert('مشاركة على فيسبوك'); return false;">
                            <i class="fab fa-facebook"></i> فيسبوك
                        </a>
                        <a href="#" class="btn btn-info text-white" onclick="alert('مشاركة على تويتر'); return false;">
                            <i class="fab fa-twitter"></i> تويتر
                        </a>
                        <a href="#" class="btn btn-success" onclick="alert('مشاركة على واتساب'); return false;">
                            <i class="fab fa-whatsapp"></i> واتساب
                        </a>
                        <button class="btn btn-outline-secondary" onclick="copyLink()">
                            <i class="fas fa-link"></i> نسخ الرابط
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="text-center mt-4">
        <a href="drawings.php<?php echo !empty($drawing['competition_id']) ? '?competition_id=' . (int)$drawing['competition_id'] : ''; ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-right"></i> رجوع إلى جميع المشاركات
        </a>
    </div>
</div>

<script>
function copyLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(function() {
        alert('تم نسخ الرابط!');
    }, function() {
        alert('فشل نسخ الرابط');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
