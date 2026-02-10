<?php
/**
 * صفحة المسابقات
 * Competitions Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'join') {
    $competition_id = (int)($_POST['competition_id'] ?? 0);

    if (!is_logged_in() || !is_contestant()) {
        set_flash_message('يجب تسجيل الدخول كمتسابق للاشتراك في المسابقة', 'warning');
        redirect(SITE_URL . '/auth/login.php');
    }

    if ($competition_id > 0) {
        join_competition($_SESSION['user_id'], $competition_id);
        set_flash_message('تم الاشتراك في المسابقة بنجاح', 'success');
        redirect(SITE_URL . '/pages/competitions.php');
    }
}

$stmt = $db->query("SELECT c.*, 
                    (SELECT COUNT(*) FROM stages s WHERE s.competition_id = c.id) as stages_count,
                    (SELECT COUNT(*) FROM competition_contestants cc WHERE cc.competition_id = c.id AND cc.status = 'active') as contestants_count,
                    (SELECT name FROM stages s WHERE s.competition_id = c.id AND s.is_active = 1 LIMIT 1) as active_stage_name
                    FROM competitions c
                    ORDER BY c.created_at DESC");
$competitions = $stmt->fetchAll();

$joined_ids = [];
if (is_logged_in() && is_contestant()) {
    $stmt = $db->prepare("SELECT competition_id FROM competition_contestants WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $joined_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$page_title = 'المسابقات المتاحة';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold"><i class="fas fa-award text-primary"></i> المسابقات المتاحة</h1>
        <p class="lead text-muted">اختر المسابقة المناسبة واشترك لتبدأ رحلتك الإبداعية</p>
    </div>

    <?php if (empty($competitions)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">لا توجد مسابقات متاحة حالياً</h4>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($competitions as $competition): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><?php echo htmlspecialchars($competition['name']); ?></h5>
                                <span class="badge <?php echo $competition['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $competition['is_active'] ? 'نشطة' : 'غير نشطة'; ?>
                                </span>
                            </div>
                            <?php if (!empty($competition['category'])): ?>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($competition['category']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($competition['description'])): ?>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($competition['description']); ?></p>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-3 mb-3 text-muted small">
                                <span><i class="fas fa-layer-group"></i> المراحل: <?php echo (int)$competition['stages_count']; ?></span>
                                <span><i class="fas fa-users"></i> المشاركون: <?php echo (int)$competition['contestants_count']; ?></span>
                                <?php if ($competition['active_stage_name']): ?>
                                    <span><i class="fas fa-bolt"></i> المرحلة الحالية: <?php echo htmlspecialchars($competition['active_stage_name']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="stages.php?competition_id=<?php echo $competition['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-layer-group"></i> عرض المراحل
                                </a>
                                <a href="drawings.php?competition_id=<?php echo $competition['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-images"></i> الأعمال
                                </a>
                                <?php if ($competition['is_active']): ?>
                                    <?php if (in_array($competition['id'], $joined_ids, true)): ?>
                                        <button class="btn btn-success btn-sm" disabled>
                                            <i class="fas fa-check"></i> مشترك
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="join">
                                            <input type="hidden" name="competition_id" value="<?php echo $competition['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-user-plus"></i> اشترك الآن
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>غير متاحة حالياً</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
