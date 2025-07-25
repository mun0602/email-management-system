<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = 'Lấy Email - ' . APP_NAME;
$currentPage = 'get-email';

$user = getCurrentUser();
$userStats = getUserEmailStats($user['id']);

$message = '';
$messageType = '';
$retrievedEmails = [];

// Xử lý yêu cầu lấy email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token bảo mật không hợp lệ';
        $messageType = 'danger';
    } else {
        $quantity = max(1, min(10, (int)($_POST['quantity'] ?? 1)));
        $appName = sanitizeInput($_POST['app_name'] ?? 'TanTan');
        
        if ($_POST['app_name'] === 'Khác') {
            $customAppName = sanitizeInput($_POST['custom_app_name'] ?? '');
            if (!empty($customAppName)) {
                $appName = $customAppName;
            }
        }
        
        // Kiểm tra quota
        if ($userStats['remaining'] < $quantity) {
            $message = "Bạn chỉ còn {$userStats['remaining']} email có thể sử dụng";
            $messageType = 'warning';
        } else {
            // Lấy email khả dụng
            $availableEmails = getAvailableEmails($quantity);
            
            if (count($availableEmails) < $quantity) {
                $message = 'Không đủ email khả dụng. Hiện tại chỉ có ' . count($availableEmails) . ' email';
                $messageType = 'warning';
            } else {
                // Đánh dấu email đã sử dụng và ghi nhận lịch sử
                $successCount = 0;
                foreach ($availableEmails as $email) {
                    if (markEmailAsUsed($email['id'], $user['id'], $appName)) {
                        $retrievedEmails[] = $email;
                        $successCount++;
                    }
                }
                
                if ($successCount > 0) {
                    $message = "Đã lấy thành công $successCount email cho ứng dụng $appName";
                    $messageType = 'success';
                    
                    // Cập nhật lại stats
                    $userStats = getUserEmailStats($user['id']);
                } else {
                    $message = 'Có lỗi xảy ra khi lấy email';
                    $messageType = 'danger';
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-download text-primary"></i>
                Lấy Email
            </h1>
            <div class="text-muted">
                Còn lại: <strong class="text-<?= $userStats['remaining'] > 0 ? 'success' : 'danger' ?>">
                    <?= number_format($userStats['remaining']) ?>
                </strong> email
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>-fill"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Get Email Form -->
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-gear"></i>
                    Tùy chọn lấy email
                </h6>
            </div>
            <div class="card-body">
                <?php if ($userStats['remaining'] > 0): ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Số lượng</label>
                            <select class="form-select" id="quantity" name="quantity" required>
                                <?php for ($i = 1; $i <= min(10, $userStats['remaining']); $i++): ?>
                                    <option value="<?= $i ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $i ?> email</option>
                                <?php endfor; ?>
                            </select>
                            <div class="form-text">
                                Tối đa <?= min(10, $userStats['remaining']) ?> email mỗi lần
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appName" class="form-label">Ứng dụng</label>
                            <select class="form-select" id="appName" name="app_name" required onchange="toggleCustomApp()">
                                <?php foreach (DEFAULT_APP_OPTIONS as $key => $value): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $key === 'TanTan' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($value) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3 d-none" id="customAppDiv">
                            <label for="customAppName" class="form-label">Tên ứng dụng khác</label>
                            <input type="text" class="form-control" id="customAppName" name="custom_app_name" 
                                   placeholder="Nhập tên ứng dụng...">
                            <div class="invalid-feedback">
                                Vui lòng nhập tên ứng dụng
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-download"></i>
                                Lấy Email
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                        <h5 class="mt-3">Đã hết quota</h5>
                        <p class="text-muted">Bạn đã sử dụng hết số email được phép. Vui lòng liên hệ admin để tăng giới hạn.</p>
                        <a href="/member/history.php" class="btn btn-outline-primary">
                            <i class="bi bi-clock-history"></i>
                            Xem lịch sử
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Stats -->
        <div class="card shadow mt-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-graph-up"></i>
                    Thống kê của bạn
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h4 class="text-info"><?= number_format($userStats['limit']) ?></h4>
                            <small class="text-muted">Giới hạn</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h4 class="text-warning"><?= number_format($userStats['used']) ?></h4>
                            <small class="text-muted">Đã dùng</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <h4 class="text-<?= $userStats['remaining'] > 0 ? 'success' : 'danger' ?>">
                            <?= number_format($userStats['remaining']) ?>
                        </h4>
                        <small class="text-muted">Còn lại</small>
                    </div>
                </div>
                
                <?php 
                $percentage = $userStats['limit'] > 0 ? ($userStats['used'] / $userStats['limit'] * 100) : 0;
                ?>
                <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar <?= $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success') ?>" 
                         role="progressbar" style="width: <?= $percentage ?>%">
                    </div>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted"><?= number_format($percentage, 1) ?>% đã sử dụng</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Retrieved Emails -->
    <div class="col-lg-8">
        <?php if (!empty($retrievedEmails)): ?>
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="bi bi-check-circle"></i>
                        Email đã lấy thành công
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($retrievedEmails as $index => $email): ?>
                        <div class="email-item fade-in" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="email-content">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">
                                            <i class="bi bi-envelope"></i>
                                            Email #<?= $index + 1 ?>
                                        </h5>
                                        <div class="mb-2">
                                            <strong>Email:</strong>
                                            <code class="text-white bg-transparent border rounded px-2 py-1">
                                                <?= htmlspecialchars($email['email']) ?>
                                            </code>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Password:</strong>
                                            <code class="text-white bg-transparent border rounded px-2 py-1">
                                                <?= htmlspecialchars($email['password']) ?>
                                            </code>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-light copy-btn" 
                                                    data-copy="<?= htmlspecialchars($email['email']) ?>">
                                                <i class="bi bi-copy"></i>
                                                Copy Email
                                            </button>
                                            <button type="button" class="btn btn-outline-light copy-btn" 
                                                    data-copy="<?= htmlspecialchars($email['password']) ?>">
                                                <i class="bi bi-copy"></i>
                                                Copy Password
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <div class="btn-group">
                            <button type="button" class="btn btn-light" onclick="copyAllEmails()">
                                <i class="bi bi-copy"></i>
                                Copy tất cả Email
                            </button>
                            <button type="button" class="btn btn-outline-light" onclick="copyAllPasswords()">
                                <i class="bi bi-copy"></i>
                                Copy tất cả Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="bi bi-envelope fs-1 text-muted"></i>
                    <h4 class="mt-3 text-muted">Chưa có email nào được lấy</h4>
                    <p class="text-muted">Sử dụng form bên trái để lấy email mới</p>
                    
                    <?php if ($userStats['remaining'] > 0): ?>
                        <div class="mt-4">
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="alert alert-info">
                                        <i class="bi bi-lightbulb"></i>
                                        <strong>Mẹo:</strong> Bạn có thể lấy tối đa 10 email mỗi lần. 
                                        Hãy chọn ứng dụng phù hợp để thống kê chính xác.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Usage -->
        <?php
        $recentUsage = $database->fetchAll(
            "SELECT e.email, eu.app_name, eu.used_at 
             FROM email_usage eu
             JOIN emails e ON eu.email_id = e.id 
             WHERE eu.user_id = ? 
             ORDER BY eu.used_at DESC 
             LIMIT 3",
            [$user['id']]
        );
        ?>
        
        <?php if (!empty($recentUsage)): ?>
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-clock-history"></i>
                        Email gần đây
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentUsage as $usage): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($usage['email']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-app"></i>
                                            <?= htmlspecialchars($usage['app_name']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?= timeAgo($usage['used_at']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="/member/history.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i>
                            Xem tất cả
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleCustomApp() {
    const appSelect = document.getElementById('appName');
    const customDiv = document.getElementById('customAppDiv');
    const customInput = document.getElementById('customAppName');
    
    if (appSelect.value === 'Khác') {
        customDiv.classList.remove('d-none');
        customInput.required = true;
    } else {
        customDiv.classList.add('d-none');
        customInput.required = false;
        customInput.value = '';
    }
}

function copyAllEmails() {
    const emails = <?= json_encode(array_column($retrievedEmails, 'email')) ?>;
    const emailText = emails.join('\n');
    app.copyToClipboard(emailText);
}

function copyAllPasswords() {
    const passwords = <?= json_encode(array_column($retrievedEmails, 'password')) ?>;
    const passwordText = passwords.join('\n');
    app.copyToClipboard(passwordText);
}

// Auto refresh stats every 30 seconds
setInterval(() => {
    // Update remaining count in header
    fetch('/api/get-user-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const remainingElement = document.querySelector('.text-success, .text-danger');
                if (remainingElement) {
                    remainingElement.textContent = data.remaining.toLocaleString('vi-VN');
                    remainingElement.className = data.remaining > 0 ? 'text-success' : 'text-danger';
                }
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}, 30000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>