<?php
/**
 * ترشيح المتسابقين للمراحل التالية
 * Qualifications Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    set_flash_message('يجب تسجيل الدخول كمدير للوصول لهذه الصفحة', 'error');
    redirect(SITE_URL . '/auth/login.php');
}

$db = getDB();
$user = get_current_user_data();

// المرحلة الحالية والمرحلة التالية
$current_stage = get_active_stage();
$next_stage = null;
if ($current_stage) {
    $stmt = $db->prepare("SELECT * FROM stages WHERE stage_number = ? LIMIT 1");
    $stmt->execute([$current_stage['stage_number'] + 1]);
    $next_stage = $stmt->fetch();
}
$is_final_stage = $current_stage && !$next_stage;

// إرسال قرار الترشيح
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'decide') {
    $drawing_id = (int)($_POST['drawing_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';

    if ($drawing_id > 0 && in_array($decision, ['approved', 'rejected'], true) && $current_stage && $is_final_stage) {
        $stmt = $db->prepare("SELECT id, user_id, title FROM drawings WHERE id = ? AND stage_id = ? AND status = 'approved' AND is_published = 1");
        $stmt->execute([$drawing_id, $current_stage['id']]);
        $drawing = $stmt->fetch();

        if ($drawing) {
            if ($decision === 'approved') {
                $stmt = $db->prepare("UPDATE drawings SET is_qualified = 1 WHERE id = ?");
                $stmt->execute([$drawing_id]);

                send_notification(
                    $drawing['user_id'],
                    'تم ترشيحك للفوز النهائي',
                    'تم ترشيح عملك "' . $drawing['title'] . '" للفوز النهائي. بالتوفيق!',
                    'stage_qualified',
                    $drawing_id
                );
            } else {
                $stmt = $db->prepare("UPDATE drawings SET is_qualified = 0 WHERE id = ?");
                $stmt->execute([$drawing_id]);

                send_notification(
                    $drawing['user_id'],
                    'لم يتم ترشيح عملك للفوز النهائي',
                    'لم يتم ترشيح عملك "' . $drawing['title'] . '" للفوز النهائي هذه المرة.',
                    'stage_not_qualified',
                    $drawing_id
                );
            }

            set_flash_message('تم حفظ القرار', 'success');
            redirect(SITE_URL . '/admin/qualifications.php');
        }
    }

    if ($drawing_id > 0 && in_array($decision, ['approved', 'rejected'], true) && $current_stage && $next_stage) {
        // تأكيد وجود سجل الترشيح للمدير الحالي
        $stmt = $db->prepare("SELECT id FROM stage_qualifications WHERE drawing_id = ? AND from_stage_id = ? AND to_stage_id = ? AND admin_id = ?");
        $stmt->execute([$drawing_id, $current_stage['id'], $next_stage['id'], $user['id']]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $db->prepare("UPDATE stage_qualifications SET approval_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$decision, $exists['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO stage_qualifications (drawing_id, from_stage_id, to_stage_id, admin_id, approval_status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$drawing_id, $current_stage['id'], $next_stage['id'], $user['id'], $decision]);
        }

        // التحقق من موافقات الجميع
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved
                              FROM stage_qualifications WHERE drawing_id = ? AND from_stage_id = ? AND to_stage_id = ?");
        $stmt->execute([$drawing_id, $current_stage['id'], $next_stage['id']]);
        $stats = $stmt->fetch();

        if ($stats && $stats['total'] > 0) {
            // عدد المدراء
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id IN (3,4) AND is_active = 1");
            $admins_count = (int)$stmt->fetchColumn();

            if ((int)$stats['approved'] === $admins_count) {
                // تأهيل العمل
                $stmt = $db->prepare("UPDATE drawings SET is_qualified = 1 WHERE id = ?");
                $stmt->execute([$drawing_id]);

                // إشعار المتسابق
                $stmt = $db->prepare("SELECT user_id, title FROM drawings WHERE id = ?");
                $stmt->execute([$drawing_id]);
                $drawing = $stmt->fetch();
                if ($drawing) {
                    send_notification($drawing['user_id'], 'تهانينا! تم تأهيلك', 'تم تأهيل عملك "' . $drawing['title'] . '" للمرحلة التالية', 'stage_qualified', $drawing_id);
                }
            }
        }

        set_flash_message('تم حفظ القرار', 'success');
        redirect(SITE_URL . '/admin/qualifications.php');
    }
}

// الأعمال المنشورة في المرحلة الحالية
$drawings = [];
if ($current_stage && $next_stage) {
    $stmt = $db->prepare("SELECT d.*, u.full_name,
                          (SELECT approval_status FROM stage_qualifications WHERE drawing_id = d.id AND from_stage_id = ? AND to_stage_id = ? AND admin_id = ? LIMIT 1) as my_status,
                          (SELECT COUNT(*) FROM stage_qualifications WHERE drawing_id = d.id AND from_stage_id = ? AND to_stage_id = ? AND approval_status = 'approved') as approved_count
                          FROM drawings d
                          JOIN users u ON d.user_id = u.id
                          WHERE d.stage_id = ? AND d.is_published = 1 AND d.status = 'approved' AND d.is_qualified = 0
                          ORDER BY d.total_votes DESC, d.created_at DESC");
    $stmt->execute([$current_stage['id'], $next_stage['id'], $user['id'], $current_stage['id'], $next_stage['id'], $current_stage['id']]);
    $drawings = $stmt->fetchAll();
} elseif ($current_stage && $is_final_stage) {
    $stmt = $db->prepare("SELECT d.*, u.full_name
                          FROM drawings d
                          JOIN users u ON d.user_id = u.id
                          WHERE d.stage_id = ? AND d.is_published = 1 AND d.status = 'approved'
                          ORDER BY d.total_votes DESC, d.created_at DESC");
    $stmt->execute([$current_stage['id']]);
    $drawings = $stmt->fetchAll();
}

$page_title = 'ترشيح المتسابقين';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-trophy"></i> ترشيح المتسابقين</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>

        <?php if (!$current_stage): ?>
            <div class="alert alert-warning">لا توجد مرحلة نشطة حالياً.</div>
        <?php elseif ($next_stage): ?>
            <div class="alert alert-info">
                ترشيح من <strong><?php echo htmlspecialchars($current_stage['name']); ?></strong>
                إلى <strong><?php echo htmlspecialchars($next_stage['name']); ?></strong>
            </div>

            <div class="admin-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>المتسابق</th>
                            <th>الأصوات</th>
                            <th>موافقتي</th>
                            <th>الموافقات</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drawings)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد أعمال مؤهلة للترشيح</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drawings as $drawing): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($drawing['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($drawing['full_name']); ?></td>
                                    <td><span class="badge bg-danger"><i class="fas fa-heart"></i> <?php echo number_format($drawing['total_votes']); ?></span></td>
                                    <td>
                                        <?php
                                        switch ($drawing['my_status']) {
                                            case 'approved':
                                                echo '<span class="status-badge status-approved">موافق</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="status-badge status-rejected">مرفوض</span>';
                                                break;
                                            default:
                                                echo '<span class="status-badge status-pending">قيد الانتظار</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo (int)$drawing['approved_count']; ?> موافقة</span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="decide">
                                            <input type="hidden" name="drawing_id" value="<?php echo $drawing['id']; ?>">
                                            <button type="submit" name="decision" value="approved" class="btn btn-sm btn-success">موافقة</button>
                                            <button type="submit" name="decision" value="rejected" class="btn btn-sm btn-danger">رفض</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                ترشيح أعمال <strong><?php echo htmlspecialchars($current_stage['name']); ?></strong> للفوز النهائي
            </div>

            <div class="admin-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>المتسابق</th>
                            <th>الأصوات</th>
                            <th>حالة الترشيح</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drawings)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">لا توجد أعمال مؤهلة للترشيح</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drawings as $drawing): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($drawing['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($drawing['full_name']); ?></td>
                                    <td><span class="badge bg-danger"><i class="fas fa-heart"></i> <?php echo number_format($drawing['total_votes']); ?></span></td>
                                    <td>
                                        <?php if ((int)$drawing['is_qualified'] === 1): ?>
                                            <span class="status-badge status-approved">مرشح للفوز النهائي</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">غير مرشح</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="decide">
                                            <input type="hidden" name="drawing_id" value="<?php echo $drawing['id']; ?>">
                                            <button type="submit" name="decision" value="approved" class="btn btn-sm btn-success">ترشيح</button>
                                            <button type="submit" name="decision" value="rejected" class="btn btn-sm btn-danger">استبعاد</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
