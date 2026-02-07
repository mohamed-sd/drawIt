<?php
/**
 * الصفحة الرئيسية
 * Home Page
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'الرئيسية - ' . SITE_NAME;

// الحصول على المرحلة النشطة
$active_stage = get_active_stage();

// الحصول على أحدث الأعمال المنشورة
$db = getDB();
$stmt = $db->query("SELECT d.*, u.full_name, s.name as stage_name, s.stage_number 
                    FROM drawings d 
                    JOIN users u ON d.user_id = u.id 
                    JOIN stages s ON d.stage_id = s.id 
                    WHERE d.is_published = 1 AND d.status = 'approved'
                    ORDER BY d.total_votes DESC, d.created_at DESC 
                    LIMIT 6");
$top_drawings = $stmt->fetchAll();

// إحصائيات عامة
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 2");
$total_contestants = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM drawings WHERE is_published = 1");
$total_drawings = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM votes");
$total_votes = $stmt->fetchColumn();

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-12 text-center">
                <h1 class="display-3 fw-bold mb-4 fade-in-up">
                    <i class="fas fa-palette"></i> مسابقة DrawIt للرسم
                </h1>
                <p class="lead mb-4 fade-in-up">
                    أطلق العنان لموهبتك الفنية وشارك في أكبر مسابقة رسم على الإنترنت
                </p>
                <div class="fade-in-up">
                    <?php if (!is_logged_in()): ?>
                        <a href="auth/register.php" class="btn btn-light btn-lg me-3 pulse">
                            <i class="fas fa-user-plus"></i> اشترك الآن
                        </a>
                        <a href="pages/drawings.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-images"></i> شاهد الأعمال
                        </a>
                    <?php else: ?>
                        <?php if (is_contestant()): ?>
                            <a href="contestant/upload.php" class="btn btn-light btn-lg me-3 pulse">
                                <i class="fas fa-upload"></i> ارفع عملك
                            </a>
                        <?php endif; ?>
                        <a href="pages/drawings.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-images"></i> شاهد الأعمال
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2><i class="fas fa-info-circle text-primary"></i> نبذة عن المسابقة</h2>
            <p class="text-muted">منصة DrawIt تجمع الموهوبين وتمنحهم فرصة عادلة للظهور والتنافس على جوائز مميزة.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-palette fa-2x text-primary mb-3"></i>
                        <h5>مساحة للمواهب</h5>
                        <p class="text-muted">نعرض الأعمال الفنية باحتراف ونمنح الفنانين منصة عادلة للتنافس.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-shield-alt fa-2x text-success mb-3"></i>
                        <h5>تحكيم موثوق</h5>
                        <p class="text-muted">مراجعة دقيقة للأعمال لضمان النزاهة والالتزام بمعايير الجودة.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-award fa-2x text-warning mb-3"></i>
                        <h5>جوائز وفرص</h5>
                        <p class="text-muted">جوائز قيّمة وفرص ظهور للمواهب المميزة في مراحل متعددة.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2><i class="fas fa-concierge-bell text-primary"></i> خدماتنا</h2>
            <p class="text-muted">نقدم خدمات تنظيم متكاملة لمنافسات إبداعية تصل بالمواهب إلى الجمهور.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <i class="fas fa-bullhorn fa-2x text-danger mb-3"></i>
                    <h6>تسويق الفعاليات</h6>
                    <p class="text-muted">حملات تعريف وإطلاق منظّم للمسابقات.</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <i class="fas fa-users fa-2x text-primary mb-3"></i>
                    <h6>إدارة المشاركين</h6>
                    <p class="text-muted">تنظيم التسجيل والمتابعة والتواصل.</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <i class="fas fa-chart-line fa-2x text-success mb-3"></i>
                    <h6>تحليلات وتقارير</h6>
                    <p class="text-muted">لوحات قياس لنتائج التصويت والأداء.</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <i class="fas fa-crown fa-2x text-warning mb-3"></i>
                    <h6>تكريم الفائزين</h6>
                    <p class="text-muted">إعلان النتائج وإبراز الفائزين باحتراف.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Company Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2><i class="fas fa-building text-primary"></i> من هي شركة الفيصل لتنظيم المسابقات</h2>
            <p class="text-muted">شركة الفيصل متخصصة في تنظيم المسابقات الإبداعية وإدارة التجارب الرقمية للمشاركين والجمهور.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="mb-2">نعمل على تصميم مسابقات عادلة وملهمة، مع منظومة تقنية متكاملة لضمان الشفافية وسهولة المشاركة.</p>
                        <p class="mb-0">هدفنا إبراز المواهب العربية وتقديم منصات حديثة تخلق تجربة ممتعة للمشاركين والمشاهدين.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Current Stage Info -->
<?php if ($active_stage): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <span class="stage-badge stage-<?php echo $active_stage['stage_number']; ?>">
                <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($active_stage['name']); ?>
            </span>
            <h2 class="mt-3"><?php echo htmlspecialchars($active_stage['description']); ?></h2>
            <?php if ($active_stage['is_free_voting']): ?>
                <p class="text-success fw-bold"><i class="fas fa-check-circle"></i> التصويت مجاني في هذه المرحلة</p>
            <?php else: ?>
                <p class="text-warning fw-bold"><i class="fas fa-coins"></i> التصويت مدفوع في هذه المرحلة</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Stats Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stat-card stat-primary">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h3><?php echo number_format($total_contestants); ?></h3>
                    <p>متسابق</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-success">
                    <i class="fas fa-palette fa-3x mb-3"></i>
                    <h3><?php echo number_format($total_drawings); ?></h3>
                    <p>عمل فني</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-warning">
                    <i class="fas fa-heart fa-3x mb-3"></i>
                    <h3><?php echo number_format($total_votes); ?></h3>
                    <p>صوت</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Top Drawings Section -->
<?php if (!empty($top_drawings)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="fas fa-star text-warning"></i> الأعمال الأكثر تصويتاً
        </h2>
        <div class="row g-4">
            <?php foreach ($top_drawings as $drawing): ?>
                <div class="col-md-4">
                    <div class="card drawing-card">
                        <div class="video-container">
                            <video controls poster="">
                                <source src="uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو
                            </video>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($drawing['title']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($drawing['full_name']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?>">
                                    <?php echo htmlspecialchars($drawing['stage_name']); ?>
                                </span>
                                <span class="vote-count">
                                    <i class="fas fa-heart text-danger"></i>
                                    <?php echo number_format($drawing['total_votes']); ?>
                                </span>
                            </div>
                            <a href="pages/drawing_details.php?id=<?php echo $drawing['id']; ?>" class="btn btn-primary mt-3 w-100">
                                <i class="fas fa-eye"></i> عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="pages/drawings.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-th"></i> عرض جميع الأعمال
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- How It Works Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="fas fa-question-circle"></i> كيف تعمل المسابقة؟
        </h2>
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <div class="display-1 text-primary mb-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>سجّل حسابك</h4>
                    <p>أنشئ حساباً مجانياً على المنصة</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <div class="display-1 text-success mb-3">
                        <i class="fas fa-video"></i>
                    </div>
                    <h4>ارفع فيديو الرسم</h4>
                    <p>صوّر عملية الرسم وارفع الفيديو</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <div class="display-1 text-warning mb-3">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <h4>احصل على الأصوات</h4>
                    <p>شارك عملك واحصل على التصويت</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4">
                    <div class="display-1 text-danger mb-3">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h4>اربح الجائزة</h4>
                    <p>تأهل للمراحل التالية واربح</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Competition Stages -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="fas fa-layer-group"></i> مراحل المسابقة
        </h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <span class="stage-badge stage-1 fs-5">المرحلة الأولى</span>
                        <h4 class="mt-3">مرحلة التصفيات</h4>
                        <ul class="list-unstyled text-start mt-3">
                            <li><i class="fas fa-check text-success"></i> مشاركة مفتوحة للجميع</li>
                            <li><i class="fas fa-check text-success"></i> تصويت مجاني</li>
                            <li><i class="fas fa-check text-success"></i> اختيار أفضل 20 عمل</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <span class="stage-badge stage-2 fs-5">المرحلة الثانية</span>
                        <h4 class="mt-3">النصف نهائية</h4>
                        <ul class="list-unstyled text-start mt-3">
                            <li><i class="fas fa-check text-warning"></i> 20 متسابق فقط</li>
                            <li><i class="fas fa-check text-warning"></i> تصويت مدفوع</li>
                            <li><i class="fas fa-check text-warning"></i> اختيار أفضل 10 أعمال</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <span class="stage-badge stage-3 fs-5">المرحلة النهائية</span>
                        <h4 class="mt-3">النهائي الكبير</h4>
                        <ul class="list-unstyled text-start mt-3">
                            <li><i class="fas fa-check text-info"></i> 3 متسابقين فقط</li>
                            <li><i class="fas fa-check text-info"></i> تصويت مدفوع</li>
                            <li><i class="fas fa-check text-info"></i> تحديد الفائز النهائي</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!is_logged_in()): ?>
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-4">هل أنت مستعد لإظهار موهبتك؟</h2>
        <p class="lead mb-4">انضم إلينا الآن وشارك في المسابقة</p>
        <a href="auth/register.php" class="btn btn-light btn-lg pulse">
            <i class="fas fa-user-plus"></i> سجل الآن مجاناً
        </a>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
