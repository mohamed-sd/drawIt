<?php
/**
 * صفحة الدفع والتصويت المدفوع
 * Payment & Paid Voting Page
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

// التحقق من التصويت السابق
$voter_ip = get_client_ip();
if (has_voted($drawing_id, $drawing['stage_id'])) {
    set_flash_message('لقد صوّت لهذا العمل مسبقاً من هذا الجهاز', 'warning');
    redirect(SITE_URL . '/pages/drawings.php');
}

$errors = [];
$vote_price = VOTE_PRICE;

// معالجة الدفع (محاكاة)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = clean_input($_POST['payment_method'] ?? '');
    $cardholder_name = clean_input($_POST['cardholder_name'] ?? '');
    
    // التحقق من المدخلات
    if (empty($payment_method)) {
        $errors[] = 'يرجى اختيار طريقة الدفع';
    }
    
    if (empty($cardholder_name)) {
        $errors[] = 'اسم حامل البطاقة مطلوب';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // إنشاء سجل دفع
            $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
            $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
            
            $stmt = $db->prepare("INSERT INTO payments (user_id, drawing_id, amount, payment_method, transaction_id, status) 
                                  VALUES (?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$user_id, $drawing_id, $vote_price, $payment_method, $transaction_id]);
            $payment_id = $db->lastInsertId();
            
            // إضافة الصوت المدفوع
            $stmt = $db->prepare("INSERT INTO votes (drawing_id, user_id, voter_ip, is_paid, payment_id, vote_type) 
                                  VALUES (?, ?, ?, 1, ?, 'paid')");
            $stmt->execute([$drawing_id, $user_id, $voter_ip, $payment_id]);
            
            // إضافة قيد منع التكرار
            $stmt = $db->prepare("INSERT INTO vote_restrictions (drawing_id, voter_ip, stage_id) 
                                  VALUES (?, ?, ?)");
            $stmt->execute([$drawing_id, $voter_ip, $drawing['stage_id']]);
            
            // تحديث عدد الأصوات
            $stmt = $db->prepare("UPDATE drawings SET total_votes = total_votes + 1, total_paid_votes = total_paid_votes + 1 WHERE id = ?");
            $stmt->execute([$drawing_id]);
            
            // إرسال تنبيه للمتسابق
            send_notification(
                $drawing['user_id'],
                'صوت مدفوع جديد!',
                'حصل عملك "' . $drawing['title'] . '" على صوت مدفوع جديد!',
                'vote_received',
                $drawing_id
            );
            
            $db->commit();
            
            set_flash_message('شكراً لك! تم تسجيل تصويتك المدفوع بنجاح', 'success');
            redirect(SITE_URL . '/pages/drawings.php');
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'حدث خطأ أثناء معالجة الدفع';
        }
    }
}

$page_title = 'صفحة الدفع';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-warning text-dark text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card"></i> صفحة الدفع - التصويت المدفوع
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Drawing Info -->
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-3">تفاصيل العمل</h5>
                            <div class="mb-3">
                                <video controls class="w-100" style="max-height:250px; border-radius:10px;">
                                    <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                                </video>
                            </div>
                            <h6><?php echo htmlspecialchars($drawing['title']); ?></h6>
                            <p class="text-muted mb-2">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($drawing['full_name']); ?>
                            </p>
                            <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?>">
                                <?php echo htmlspecialchars($drawing['stage_name']); ?>
                            </span>
                        </div>

                        <!-- Payment Form -->
                        <div class="col-md-6">
                            <h5 class="mb-3">معلومات الدفع</h5>
                            
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>سعر التصويت:</span>
                                    <strong class="fs-4"><?php echo number_format($vote_price, 2); ?> ريال</strong>
                                </div>
                            </div>

                            <form method="POST" action="" class="needs-validation" novalidate>
                                <!-- Payment Method -->
                                <div class="mb-3">
                                    <label class="form-label">طريقة الدفع *</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="credit_card" value="credit_card" required>
                                        <label class="form-check-label" for="credit_card">
                                            <i class="fas fa-credit-card"></i> بطاقة ائتمان
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="mada" value="mada" required>
                                        <label class="form-check-label" for="mada">
                                            <i class="fas fa-credit-card"></i> مدى
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="apple_pay" value="apple_pay" required>
                                        <label class="form-check-label" for="apple_pay">
                                            <i class="fab fa-apple-pay"></i> Apple Pay
                                        </label>
                                    </div>
                                </div>

                                <!-- Cardholder Name -->
                                <div class="mb-3">
                                    <label for="cardholder_name" class="form-label">اسم حامل البطاقة *</label>
                                    <input type="text" class="form-control" id="cardholder_name" 
                                           name="cardholder_name" required>
                                </div>

                                <!-- Card Number (Demo) -->
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">رقم البطاقة *</label>
                                    <input type="text" class="form-control" id="card_number" 
                                           placeholder="**** **** **** 1234" 
                                           maxlength="19" required>
                                </div>

                                <!-- Expiry & CVV (Demo) -->
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label for="expiry" class="form-label">تاريخ الانتهاء *</label>
                                        <input type="text" class="form-control" id="expiry" 
                                               placeholder="MM/YY" maxlength="5" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label for="cvv" class="form-label">CVV *</label>
                                        <input type="text" class="form-control" id="cvv" 
                                               placeholder="123" maxlength="3" required>
                                    </div>
                                </div>

                                <!-- Notice -->
                                <div class="alert alert-warning">
                                    <small>
                                        <i class="fas fa-info-circle"></i>
                                        <strong>ملاحظة:</strong> هذه صفحة دفع تجريبية. لن يتم خصم أي مبلغ فعلي.
                                        في النسخة النهائية، سيتم الربط مع بوابة دفع حقيقية.
                                    </small>
                                </div>

                                <!-- Submit -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="fas fa-lock"></i> إتمام الدفع والتصويت
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

            <!-- Security Notice -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6><i class="fas fa-shield-alt text-success"></i> الأمان والخصوصية</h6>
                    <ul class="mb-0 small text-muted">
                        <li>جميع المعاملات محمية بتشفير SSL</li>
                        <li>لا نقوم بتخزين معلومات بطاقتك الائتمانية</li>
                        <li>الدفع آمن ومضمون 100%</li>
                        <li>يمكنك التواصل معنا في حال وجود أي مشكلة</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format card number
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
});

// Format expiry date
document.getElementById('expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    e.target.value = value;
});

// CVV numbers only
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});
</script>

<?php require_once '../includes/footer.php'; ?>
