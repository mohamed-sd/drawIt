<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

http_response_code(403);
$page_title = '403 - ممنوع الوصول';
require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container text-center">
        <div class="display-1 text-danger mb-4">
            <i class="fas fa-ban"></i>
        </div>
        <h1 class="mb-3">403 - ممنوع الوصول</h1>
        <p class="lead text-muted mb-4">
            عذراً، لا تملك الصلاحية للوصول إلى هذه الصفحة.
        </p>
        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-home"></i> العودة للرئيسية
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
