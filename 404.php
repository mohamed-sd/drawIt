<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

http_response_code(404);
$page_title = '404 - الصفحة غير موجودة';
require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container text-center">
        <div class="display-1 text-warning mb-4">
            <i class="fas fa-search"></i>
        </div>
        <h1 class="mb-3">404 - الصفحة غير موجودة</h1>
        <p class="lead text-muted mb-4">
            عذراً، الصفحة التي تبحث عنها غير موجودة.
        </p>
        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-home"></i> العودة للرئيسية
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
