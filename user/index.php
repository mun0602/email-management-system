<?php
require_once '../includes/functions.php';
requireLogin();

$db = new Database();
$message = '';
$user = getCurrentUser();

// Handle mail request
if ($_POST && $_POST['action'] === 'take_mails') {
    $count = (int)($_POST['count'] ?? 1);
    $app_id = (int)($_POST['app_id'] ?? 0);
    $custom_app = trim($_POST['custom_app'] ?? '');
    
    // Handle custom app creation
    if ($app_id === 0 && !empty($custom_app)) {
        // Check if custom app exists
        $existingApp = $db->fetch("SELECT id FROM apps WHERE name = ?", [$custom_app]);
        if ($existingApp) {
            $app_id = $existingApp['id'];
        } else {
            // Create new app
            $db->query("INSERT INTO apps (name) VALUES (?)", [$custom_app]);
            $app_id = $db->lastInsertId();
            
            // Create limits for all users
            $users = $db->fetchAll("SELECT id FROM users WHERE role = 'user'");
            foreach ($users as $userRecord) {
                $db->query(
                    "INSERT INTO user_limits (user_id, app_id, daily_limit, used_today) VALUES (?, ?, ?, ?)",
                    [$userRecord['id'], $app_id, 25, 0]
                );
            }
        }
    }
    
    if (!$app_id) {
        $message = '<div class="alert alert-danger">Vui lòng chọn ứng dụng!</div>';
    } elseif ($count < 1 || $count > 10) {
        $message = '<div class="alert alert-danger">Số lượng mail phải từ 1 đến 10!</div>';
    } else {
        // Check user limit for this app
        $limit = getUserLimitForApp($user['id'], $app_id);
        
        if ($count > $limit['remaining']) {
            $message = '<div class="alert alert-danger">Bạn chỉ có thể lấy thêm ' . $limit['remaining'] . ' mail cho ứng dụng này hôm nay!</div>';
        } else {
            // Get available mails
            $availableMails = $db->fetchAll(
                "SELECT * FROM emails WHERE is_used = 0 AND (app_id IS NULL OR app_id = ?) ORDER BY created_at ASC LIMIT ?",
                [$app_id, $count]
            );
            
            if (count($availableMails) < $count) {
                $message = '<div class="alert alert-danger">Không đủ mail khả dụng! Chỉ còn ' . count($availableMails) . ' mail.</div>';
            } else {
                // Process mail allocation
                $allocatedMails = [];
                
                try {
                    $db->getConnection()->beginTransaction();
                    
                    foreach ($availableMails as $mail) {
                        // Mark mail as used
                        $db->query(
                            "UPDATE emails SET is_used = 1, used_by = ?, used_at = CURRENT_TIMESTAMP, app_id = ? WHERE id = ?",
                            [$user['id'], $app_id, $mail['id']]
                        );
                        
                        // Add to history
                        $db->query(
                            "INSERT INTO email_history (user_id, email_id, app_id) VALUES (?, ?, ?)",
                            [$user['id'], $mail['id'], $app_id]
                        );
                        
                        $allocatedMails[] = $mail;
                    }
                    
                    // Update user limit
                    $db->query(
                        "UPDATE user_limits SET used_today = used_today + ? WHERE user_id = ? AND app_id = ?",
                        [$count, $user['id'], $app_id]
                    );
                    
                    $db->getConnection()->commit();
                    $message = '<div class="alert alert-success">Đã cấp ' . count($allocatedMails) . ' mail thành công!</div>';
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    $message = '<div class="alert alert-danger">Có lỗi xảy ra: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
}

// Get user statistics per app
$apps = getAllApps();
$userStats = [];
$totalRemaining = 0;
$totalUsed = 0;

foreach ($apps as $app) {
    $limit = getUserLimitForApp($user['id'], $app['id']);
    $available = getAvailableEmailsForApp($app['id']);
    
    $userStats[] = [
        'app' => $app,
        'limit' => $limit,
        'available' => $available
    ];
    
    $totalRemaining += $limit['remaining'];
    $totalUsed += $limit['used_today'];
}

$mailStats = getMailStats();

$pageTitle = getPageTitle('user_dashboard');
include '../includes/header.php';
?>

<?= $message ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number"><?= $totalRemaining ?></div>
                <div class="stats-label">Mail còn lại hôm nay</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $totalUsed ?></div>
                <div class="stats-label">Mail đã lấy hôm nay</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-download"></i> Lấy Mail</h5>
            </div>
            <div class="card-body">
                <?php if ($totalRemaining <= 0): ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                        <h5>Bạn đã hết hạn mức lấy mail hôm nay!</h5>
                        <p>Hạn mức sẽ được reset vào ngày mai hoặc liên hệ Admin để tăng hạn mức.</p>
                    </div>
                <?php elseif ($mailStats['available'] <= 0): ?>
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-inbox fs-1"></i>
                        <h5>Hiện tại không có mail khả dụng!</h5>
                        <p>Vui lòng thử lại sau hoặc liên hệ Admin.</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="take_mails">
                        
                        <div class="mb-3">
                            <label for="app_id" class="form-label">Ứng dụng</label>
                            <select class="form-select" id="app_id" name="app_id" required>
                                <option value="">Chọn ứng dụng...</option>
                                <?php foreach ($userStats as $stat): ?>
                                    <option value="<?= $stat['app']['id'] ?>" 
                                            <?= $stat['limit']['remaining'] <= 0 ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($stat['app']['name']) ?> 
                                        (còn <?= $stat['limit']['remaining'] ?>/<?= $stat['limit']['daily_limit'] ?> | 
                                         <?= $stat['available'] ?> khả dụng)
                                    </option>
                                <?php endforeach; ?>
                                <option value="0">Loại khác...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="custom_app_group" style="display: none;">
                            <label for="custom_app" class="form-label">Tên ứng dụng khác</label>
                            <input type="text" class="form-control" id="custom_app" name="custom_app" 
                                   placeholder="Nhập tên ứng dụng...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="count" class="form-label">Số lượng mail muốn lấy</label>
                            <select class="form-select" id="count" name="count">
                                <?php for ($i = 1; $i <= min(10, $totalRemaining, $mailStats['available']); $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> mail</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-download"></i> Lấy Mail
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Display allocated mails if any -->
        <?php if (isset($allocatedMails) && !empty($allocatedMails)): ?>
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Mail đã cấp</h5>
            </div>
            <div class="card-body">
                <?php foreach ($allocatedMails as $index => $mail): ?>
                <div class="mail-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Mail #<?= $index + 1 ?></h6>
                        <small class="text-muted">Vừa lấy</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Email:</label>
                            <div class="mail-credentials">
                                <span id="email-<?= $mail['id'] ?>"><?= htmlspecialchars($mail['email']) ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary copy-btn btn-copy" 
                                        data-target="#email-<?= $mail['id'] ?>">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mật khẩu:</label>
                            <div class="mail-credentials">
                                <span id="password-<?= $mail['id'] ?>"><?= htmlspecialchars($mail['password']) ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary copy-btn btn-copy" 
                                        data-target="#password-<?= $mail['id'] ?>">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-3">
                    <a href="/user/history.php" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history"></i> Xem lịch sử
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Hạn mức theo ứng dụng</h6>
            </div>
            <div class="card-body">
                <?php foreach ($userStats as $stat): ?>
                <div class="mb-3 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong><?= htmlspecialchars($stat['app']['name']) ?></strong>
                        <span class="badge bg-<?= $stat['limit']['remaining'] > 0 ? 'success' : 'danger' ?>">
                            <?= $stat['limit']['remaining'] ?>/<?= $stat['limit']['daily_limit'] ?>
                        </span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $stat['limit']['daily_limit'] > 0 ? ($stat['limit']['used_today'] / $stat['limit']['daily_limit']) * 100 : 0 ?>%"></div>
                    </div>
                    <small class="text-muted">
                        <?= $stat['available'] ?> email khả dụng
                    </small>
                </div>
                <?php endforeach; ?>
                
                <hr>
                
                <div class="small text-muted">
                    <strong>Lưu ý:</strong> Hạn mức sẽ được reset hàng ngày vào 00:00
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Hướng dẫn</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Chọn ứng dụng sẽ sử dụng mail</li>
                    <li>Chọn số lượng mail muốn lấy</li>
                    <li>Nhấn "Lấy Mail" để nhận mail</li>
                    <li>Sử dụng nút copy để sao chép email/password</li>
                    <li>Xem lịch sử để theo dõi mail đã lấy</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const appSelect = document.getElementById('app_id');
    const customGroup = document.getElementById('custom_app_group');
    const customInput = document.getElementById('custom_app');
    
    appSelect.addEventListener('change', function() {
        if (this.value === '0') {
            customGroup.style.display = 'block';
            customInput.required = true;
        } else {
            customGroup.style.display = 'none';
            customInput.required = false;
            customInput.value = '';
        }
    });
    
    // Copy functionality
    document.querySelectorAll('.btn-copy').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const text = document.querySelector(targetId).textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check"></i>';
                this.classList.add('btn-success');
                this.classList.remove('btn-outline-primary');
                
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                }, 2000);
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>