<?php
/**
 * صفحة إعلان الفائز
 * Winner Announcement Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = getDB();

// الحصول على الفائز النهائي
$stmt = $db->query("SELECT w.*, d.title, d.description, d.video_path, u.full_name, u.email,
                    s.name as stage_name
                    FROM winners w
                    JOIN drawings d ON w.drawing_id = d.id
                    JOIN users u ON w.user_id = u.id
                    JOIN stages s ON w.stage_id = s.id
                    WHERE w.position = 1
                    ORDER BY w.announced_at DESC
                    LIMIT 1");
$winner = $stmt->fetch();

// الحصول على المراكز الأخرى
$stmt = $db->query("SELECT w.*, d.title, d.video_path, u.full_name,
                    s.name as stage_name
                    FROM winners w
                    JOIN drawings d ON w.drawing_id = d.id
                    JOIN users u ON w.user_id = u.id
                    JOIN stages s ON w.stage_id = s.id
                    WHERE w.position IN (2, 3)
                    ORDER BY w.position ASC");
$other_winners = $stmt->fetchAll();

$page_title = 'إعلان الفائز';
require_once '../includes/header.php';
?>

<?php if ($winner): ?>
    <!-- Winner Section -->
    <section class="winner-section position-relative">
        <!-- Confetti Animation -->
        <div class="confetti-container">
            <?php for ($i = 0; $i < 50; $i++): ?>
                <div class="confetti" style="
                    left: <?php echo rand(0, 100); ?>%;
                    animation-delay: <?php echo rand(0, 3000) / 1000; ?>s;
                    background: <?php echo ['#ffd700', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24'][ array_rand(['#ffd700', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24'])]; ?>;
                "></div>
            <?php endfor; ?>
        </div>

        <div class="container position-relative">
            <div class="text-center">
                <div class="winner-crown">👑</div>
                <h1 class="display-1 fw-bold mb-4">الفائز</h1>
                <h2 class="display-4 mb-4"><?php echo htmlspecialchars($winner['full_name']); ?></h2>
                
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-lg">
                            <div class="card-body p-0">
                                <video controls class="w-100" style="border-radius:15px 15px 0 0;">
                                    <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($winner['video_path']); ?>" type="video/mp4">
                                </video>
                                <div class="p-4 bg-white" style="border-radius:0 0 15px 15px;">
                                    <h3 class="mb-3"><?php echo htmlspecialchars($winner['title']); ?></h3>
                                    <?php if ($winner['description']): ?>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($winner['description'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($winner['prize_description']): ?>
                                        <div class="prize-card mt-4">
                                            <h4>الجائزة</h4>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($winner['prize_description'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Other Winners -->
    <?php if (!empty($other_winners)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5">
                    <i class="fas fa-medal text-warning"></i> المراكز الأخرى
                </h2>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($other_winners as $index => $other_winner): ?>
                        <div class="col-md-5">
                            <div class="card h-100 shadow">
                                <div class="card-body text-center">
                                    <div class="display-1 mb-3">
                                        <?php echo $other_winner['position'] == 2 ? '🥈' : '🥉'; ?>
                                    </div>
                                    <h4>المركز <?php echo $other_winner['position'] == 2 ? 'الثاني' : 'الثالث'; ?></h4>
                                    <h5 class="text-primary"><?php echo htmlspecialchars($other_winner['full_name']); ?></h5>
                                    <div class="mt-3">
                                        <video controls class="w-100" style="max-height:300px; border-radius:10px;">
                                            <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($other_winner['video_path']); ?>" type="video/mp4">
                                        </video>
                                    </div>
                                    <h6 class="mt-3"><?php echo htmlspecialchars($other_winner['title']); ?></h6>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Thank You Section -->
    <section class="py-5">
        <div class="container text-center">
            <h2 class="mb-4">شكراً لجميع المشاركين</h2>
            <p class="lead text-muted mb-4">
                نشكر جميع المتسابقين على مشاركتهم الرائعة وإبداعاتهم المميزة.
                كل عمل كان له بصمته الخاصة وأضاف قيمة للمسابقة.
            </p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body p-4">
                            <i class="fas fa-bullhorn fa-3x mb-3"></i>
                            <h4>ترقبوا المسابقة القادمة</h4>
                            <p>سنعلن قريباً عن موعد المسابقة القادمة مع جوائز أكبر وأفضل!</p>
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-light btn-lg mt-3">
                                <i class="fas fa-home"></i> العودة للرئيسية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php else: ?>
    <!-- No Winner Yet -->
    <section class="py-5">
        <div class="container">
            <div class="text-center py-5">
                <i class="fas fa-trophy fa-5x text-muted mb-4"></i>
                <h2 class="mb-3">لم يتم إعلان الفائز بعد</h2>
                <p class="lead text-muted">المسابقة لا تزال جارية، ترقبوا إعلان الفائز قريباً!</p>
                <a href="<?php echo SITE_URL; ?>/pages/drawings.php" class="btn btn-primary btn-lg mt-4">
                    <i class="fas fa-images"></i> شاهد الأعمال المشاركة
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
