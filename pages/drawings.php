<?php
/**
 * صفحة عرض جميع الأعمال
 * All Drawings Page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = getDB();
$competition_id = isset($_GET['competition_id']) && is_numeric($_GET['competition_id']) && (int)$_GET['competition_id'] > 0
    ? (int)$_GET['competition_id']
    : null;
$competition = null;
if ($competition_id) {
    $stmt = $db->prepare("SELECT * FROM competitions WHERE id = ? LIMIT 1");
    $stmt->execute([$competition_id]);
    $competition = $stmt->fetch();
    if (!$competition) {
        $competition_id = null;
    }
}

// الفلاتر
$stage_filter = isset($_GET['stage']) ? (int)$_GET['stage'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page > 0 ? $page : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

// بناء الاستعلام
$where_sql = " WHERE d.is_published = 1 AND d.status = 'approved'";
$params = [];

if ($competition_id) {
    $where_sql .= " AND d.competition_id = ?";
    $params[] = $competition_id;
}

if ($stage_filter > 0) {
    if ($competition_id) {
        $stmt = $db->prepare("SELECT 1 FROM stages WHERE id = ? AND competition_id = ?");
        $stmt->execute([$stage_filter, $competition_id]);
        if ($stmt->fetchColumn()) {
            $where_sql .= " AND d.stage_id = ?";
            $params[] = $stage_filter;
        } else {
            $stage_filter = 0;
        }
    }
}

// الترتيب
switch ($sort) {
    case 'newest':
        $order_sql = " ORDER BY d.created_at DESC";
        break;
    case 'oldest':
        $order_sql = " ORDER BY d.created_at ASC";
        break;
    case 'votes':
    default:
        $order_sql = " ORDER BY d.total_votes DESC, d.created_at DESC";
}

$count_sql = "SELECT COUNT(*) FROM drawings d" . $where_sql;
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_drawings = (int)$stmt->fetchColumn();

$sql = "SELECT d.*, u.full_name, s.name as stage_name, s.stage_number, s.is_free_voting, c.name as competition_name
    FROM drawings d 
    JOIN users u ON d.user_id = u.id 
    JOIN stages s ON d.stage_id = s.id
    JOIN competitions c ON d.competition_id = c.id" . $where_sql . $order_sql . " LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$drawings = $stmt->fetchAll();

// قائمة المسابقات للفلترة
$stmt = $db->query("SELECT id, name FROM competitions WHERE is_active = 1 ORDER BY created_at DESC");
$competitions = $stmt->fetchAll();

// الحصول على المراحل للفلتر
$stages = [];
if ($competition_id) {
    $stmt = $db->prepare("SELECT * FROM stages WHERE competition_id = ? ORDER BY stage_number");
    $stmt->execute([$competition_id]);
    $stages = $stmt->fetchAll();
}

$page_title = 'جميع الأعمال';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">
            <i class="fas fa-images text-primary"></i> المشاركات
        </h1>
        <?php if ($competition): ?>
            <p class="lead text-muted">مسابقة: <?php echo htmlspecialchars($competition['name']); ?></p>
        <?php else: ?>
            <p class="lead text-muted">استعرض المشاركات واختر المسابقة التي تريدها</p>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="competition_id" class="form-label">
                        <i class="fas fa-award"></i> تصفية حسب المسابقة
                    </label>
                    <select class="form-select" id="competition_id" name="competition_id" onchange="if (document.getElementById('stage')) { document.getElementById('stage').value = 0; } this.form.submit()">
                        <option value="0" <?php echo !$competition_id ? 'selected' : ''; ?>>جميع المسابقات</option>
                        <?php foreach ($competitions as $item): ?>
                            <option value="<?php echo $item['id']; ?>" <?php echo $competition_id == $item['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Stage Filter -->
                <div class="col-md-4">
                    <label for="stage" class="form-label">
                        <i class="fas fa-filter"></i> تصفية حسب المرحلة
                    </label>
                    <select class="form-select" id="stage" name="stage" onchange="this.form.submit()">
                        <option value="0" <?php echo $stage_filter == 0 ? 'selected' : ''; ?>>جميع المراحل</option>
                        <?php foreach ($stages as $stage): ?>
                            <option value="<?php echo $stage['id']; ?>" 
                                    <?php echo $stage_filter == $stage['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stage['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div class="col-md-4">
                    <label for="sort" class="form-label">
                        <i class="fas fa-sort"></i> الترتيب
                    </label>
                    <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>الأحدث</option>
                        <option value="votes" <?php echo $sort == 'votes' ? 'selected' : ''; ?>>الأكثر تصويتاً</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>الأقدم</option>
                    </select>
                </div>

                <!-- Results Count -->
                <div class="col-md-4">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        عدد المشاركات: <strong><?php echo number_format($total_drawings); ?></strong>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Drawings Grid -->
    <?php if (empty($drawings)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">لا توجد مشاركات منشورة حالياً</h4>
            <p class="text-muted">تحقق مرة أخرى قريباً</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($drawings as $drawing): ?>
                <div class="col-md-4">
                    <div class="card drawing-card h-100">
                        <!-- Video -->
                        <div class="video-container">
                            <video controls poster="">
                                <source src="<?php echo SITE_URL; ?>/uploads/videos/<?php echo htmlspecialchars($drawing['video_path']); ?>" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو
                            </video>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <h5 class="card-title mb-2">
                                <?php echo htmlspecialchars($drawing['title']); ?>
                            </h5>
                            
                            <p class="text-muted mb-2">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($drawing['full_name']); ?>
                            </p>

                            <?php if ($drawing['description']): ?>
                                <p class="card-text small text-muted">
                                    <?php echo nl2br(htmlspecialchars(substr($drawing['description'], 0, 100))); ?>
                                    <?php echo strlen($drawing['description']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>

                            <!-- Stage & Votes -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="stage-badge stage-<?php echo $drawing['stage_number']; ?>">
                                    <?php echo htmlspecialchars($drawing['stage_name']); ?>
                                </span>
                                <span class="vote-count">
                                    <i class="fas fa-heart text-danger"></i>
                                    <?php echo number_format($drawing['total_votes']); ?>
                                </span>
                            </div>

                            <!-- Vote Button -->
                            <?php
                            $can_vote = true;
                            $vote_message = '';
                            
                            if (has_voted($drawing['id'], $drawing['stage_id'])) {
                                $can_vote = false;
                                $vote_message = 'لقد صوّت لهذا العمل مسبقاً';
                            }
                            ?>

                            <?php if ($drawing['is_free_voting']): ?>
                                <button onclick="window.location.href='vote.php?drawing_id=<?php echo $drawing['id']; ?>'" 
                                        class="vote-btn btn" 
                                        <?php echo !$can_vote ? 'disabled' : ''; ?>>
                                    <i class="fas fa-heart"></i>
                                    <?php echo $can_vote ? 'صوّت مجاناً' : $vote_message; ?>
                                </button>
                            <?php else: ?>
                                <button onclick="window.location.href='../pages/payment.php?drawing_id=<?php echo $drawing['id']; ?>'" 
                                        class="vote-btn btn" 
                                        <?php echo !$can_vote ? 'disabled' : ''; ?>>
                                    <i class="fas fa-coins"></i>
                                    <?php echo $can_vote ? 'صوّت (' . VOTE_PRICE . ' ريال)' : $vote_message; ?>
                                </button>
                            <?php endif; ?>

                            <a href="drawing_details.php?id=<?php echo $drawing['id']; ?>" 
                               class="btn btn-outline-primary w-100 mt-2">
                                <i class="fas fa-eye"></i> عرض التفاصيل
                            </a>
                        </div>

                        <!-- Card Footer -->
                        <div class="card-footer bg-light text-muted small">
                            <i class="fas fa-clock"></i>
                            <?php echo time_elapsed($drawing['created_at']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (($offset + $per_page) < $total_drawings): ?>
            <div class="text-center mt-4">
                <?php
                $next_params = [
                    'stage' => $stage_filter,
                    'sort' => $sort,
                    'page' => $page + 1
                ];
                if ($competition_id) {
                    $next_params['competition_id'] = $competition_id;
                }
                $next_url = 'drawings.php?' . http_build_query($next_params);
                ?>
                <a href="<?php echo $next_url; ?>" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-plus"></i> المزيد
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
