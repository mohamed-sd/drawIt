<?php
/**
 * صفحة تسجيل الدخول
 * User Login Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// إذا كان مسجل دخول، إعادة توجيه
if (is_logged_in()) {
    redirect(SITE_URL);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = clean_input($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $errors[] = 'اسم المستخدم أو البريد الإلكتروني وكلمة المرور مطلوبان';
    }
    
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        
        if ($user && verify_password($password, $user['password'])) {
            // تسجيل الدخول ناجح
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role_id'];
            
            // إعادة التوجيه حسب الدور
            switch ($user['role_id']) {
                case 2: // متسابق
                    redirect(SITE_URL . '/contestant/dashboard.php');
                    break;
                case 3: // مدير
                case 4: // مدير رئيسي
                    redirect(SITE_URL . '/admin/dashboard.php');
                    break;
                default:
                    redirect(SITE_URL);
            }
        } else {
            $errors[] = 'بيانات الدخول غير صحيحة';
        }
    }
}

$page_title = 'تسجيل الدخول';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h4 class="mb-0"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="login" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="login" name="login" 
                                       value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> دخول
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="mb-0">ليس لديك حساب؟ <a href="register.php" class="fw-bold">سجل الآن</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
