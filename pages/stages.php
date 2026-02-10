<?php
/**
 * صفحة المراحل
 * Stages Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = getDB();
$competition = get_current_competition();
$stages = [];
$active_stage = null;

if ($competition) {
    $stmt = $db->prepare("SELECT * FROM stages WHERE competition_id = ? ORDER BY stage_number ASC");
    $stmt->execute([$competition['id']]);
    $stages = $stmt->fetchAll();

    $active_stage = get_active_stage($competition['id']);
}

$page_title = 'مراحل المسابقة';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold"><i class="fas fa-layer-group text-primary"></i> مراحل المسابقة</h1>
        <?php if ($competition): ?>
            <p class="lead text-muted">مسابقة: <?php echo htmlspecialchars($competition['name']); ?></p>
        <?php else: ?>
            <p class="lead text-muted">لا توجد مسابقة محددة حالياً</p>
        <?php endif; ?>
    </div>

    <?php if ($competition && $active_stage): ?>
        <div class="alert alert-info text-center">
            المرحلة الحالية: <strong><?php echo htmlspecialchars($active_stage['name']); ?></strong>
            <?php if ($active_stage['is_free_voting']): ?>
                <span class="badge bg-success ms-2">تصويت مجاني</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark ms-2">تصويت مدفوع</span>
            <?php endif; ?>
        </div>
    <?php elseif (!$competition): ?>
        <div class="alert alert-warning text-center">
            لا توجد مسابقة محددة حالياً. الرجاء اختيار مسابقة من صفحة المسابقات.
            <div class="mt-2">
                <a href="competitions.php" class="btn btn-outline-primary btn-sm">عرض المسابقات</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($stages as $stage): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <span class="stage-badge stage-<?php echo $stage['stage_number']; ?> fs-5">
                            <?php echo htmlspecialchars($stage['name']); ?>
                        </span>
                        <h4 class="mt-3">المرحلة <?php echo $stage['stage_number']; ?></h4>
                        <p class="text-muted">
                            <?php echo htmlspecialchars($stage['description']); ?>
                        </p>

                        <?php if ($stage['is_free_voting']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> التصويت مجاني
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-coins"></i> التصويت مدفوع (<?php echo VOTE_PRICE; ?> ريال)
                            </div>
                        <?php endif; ?>

                        <?php if ($stage['max_qualifiers']): ?>
                            <p class="mb-0"><i class="fas fa-users"></i> المتأهلون: <?php echo (int)$stage['max_qualifiers']; ?></p>
                        <?php endif; ?>

                        <?php if ($stage['is_active']): ?>
                            <div class="mt-3">
                                <span class="badge bg-primary">مرحلة نشطة</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
        <?php if ($competition): ?>
            <a href="drawings.php?competition_id=<?php echo $competition['id']; ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-images"></i> مشاهدة الأعمال
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
