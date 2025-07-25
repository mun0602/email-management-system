<?php
require_once '../includes/functions.php';
requireLogin();

$db = new Database();
$message = '';
$user = getCurrentUser();

// Handle mail request
if ($_POST && $_POST['action'] === 'take_mails') {
    $count = (int)($_POST['count'] ?? 1);
    $app_name = trim($_POST['app_name'] ?? '');
    $custom_app = trim($_POST['custom_app'] ?? '');
    
    // Determine app name
    if ($app_name === 'other' && !empty($custom_app)) {
        $app_name = $custom_app;
    }
    
    if (empty($app_name)) {
        $message = '<div class="alert alert-danger">Vui lòng chọn ứng dụng liên kết!</div>';
    } elseif ($count < 1 || $count > 10) {
        $message = '<div class="alert alert-danger">Số lượng mail phải từ 1 đến 10!</div>';
    } else {
        // Check user limit
        $userMailCount = getUserMailCount($user['id']);
        $remainingLimit = $user['mail_limit'] - $userMailCount;
        
        if ($count > $remainingLimit) {
            $message = '<div class="alert alert-danger">Bạn chỉ có thể lấy thêm ' . $remainingLimit . ' mail!</div>';
        } else {
            // Get available mails
            $availableMails = $db->fetchAll(
                "SELECT * FROM mails WHERE status = 'available' ORDER BY created_at ASC LIMIT ?",
                [$count]
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
                            "UPDATE mails SET status = 'used', used_at = CURRENT_TIMESTAMP WHERE id = ?",
                            [$mail['id']]
                        );
                        
                        // Add to history
                        $db->query(
                            "INSERT INTO mail_history (user_id, mail_id, app_name) VALUES (?, ?, ?)",
                            [$user['id'], $mail['id'], $app_name]
                        );
                        
                        $allocatedMails[] = $mail;
                    }
                    
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

// Get user statistics
$userStats = [
    'used' => getUserMailCount($user['id']),
    'limit' => $user['mail_limit']
];
$userStats['remaining'] = $userStats['limit'] - $userStats['used'];

$mailStats = getMailStats();

$pageTitle = getPageTitle('user_dashboard');
include '../includes/header.php';
?>

<?= $message ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number"><?= $userStats['remaining'] ?></div>
                <div class="stats-label">Mail còn lại</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $userStats['used'] ?></div>
                <div class="stats-label">Mail đã lấy</div>
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
                <?php if ($userStats['remaining'] <= 0): ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                        <h5>Bạn đã hết hạn mức lấy mail!</h5>
                        <p>Vui lòng liên hệ Admin để tăng hạn mức.</p>
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
                            <label for="count" class="form-label">Số lượng mail muốn lấy</label>
                            <select class="form-select" id="count" name="count">
                                <?php for ($i = 1; $i <= min(10, $userStats['remaining'], $mailStats['available']); $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> mail</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="app_name" class="form-label">Ứng dụng liên kết</label>
                            <select class="form-select" id="app_name" name="app_name" required>
                                <option value="">Chọn ứng dụng...</option>
                                <option value="TanTan">TanTan</option>
                                <option value="HelloTalk">HelloTalk</option>
                                <option value="other">Loại khác</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="custom_app_group" style="display: none;">
                            <label for="custom_app" class="form-label">Tên ứng dụng khác</label>
                            <input type="text" class="form-control" id="custom_app" name="custom_app" 
                                   placeholder="Nhập tên ứng dụng...">
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
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Thông tin</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Tài khoản:</label>
                    <div><strong><?= htmlspecialchars($user['username']) ?></strong></div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted">Hạn mức:</label>
                    <div>
                        <span class="badge bg-primary"><?= $userStats['limit'] ?> mail</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted">Đã sử dụng:</label>
                    <div>
                        <span class="badge bg-warning"><?= $userStats['used'] ?> mail</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted">Còn lại:</label>
                    <div>
                        <span class="badge bg-success"><?= $userStats['remaining'] ?> mail</span>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <label class="text-muted">Hệ thống:</label>
                    <div>
                        <span class="badge bg-info"><?= $mailStats['available'] ?> mail khả dụng</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Hướng dẫn</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Chọn số lượng mail muốn lấy</li>
                    <li>Chọn ứng dụng sẽ sử dụng mail</li>
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
    const appSelect = document.getElementById('app_name');
    const customInput = document.getElementById('custom_app_group');
    const customField = document.getElementById('custom_app');
    
    if (appSelect && customInput) {
        appSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customInput.style.display = 'block';
                if (customField) customField.required = true;
            } else {
                customInput.style.display = 'none';
                if (customField) {
                    customField.required = false;
                    customField.value = '';
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>