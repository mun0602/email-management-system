<?php
require_once '../includes/functions.php';

$token = trim($_GET['token'] ?? '');
$message = '';
$mails = [];
$shareData = null;

if (empty($token)) {
    $message = '<div class="alert alert-danger">Token chia sẻ không hợp lệ!</div>';
} else {
    $db = new Database();
    
    // Get share data
    $shareData = $db->fetch(
        "SELECT s.*, u.username 
         FROM shared_mails s 
         JOIN users u ON s.created_by = u.id 
         WHERE s.share_token = ?",
        [$token]
    );
    
    if (!$shareData) {
        $message = '<div class="alert alert-danger">Link chia sẻ không tồn tại hoặc đã hết hạn!</div>';
    } else {
        // Check password if required
        if ($shareData['password']) {
            if ($_POST && isset($_POST['share_password'])) {
                $inputPassword = trim($_POST['share_password']);
                
                if (md5($inputPassword) === $shareData['password']) {
                    $_SESSION['share_authenticated_' . $token] = true;
                } else {
                    $message = '<div class="alert alert-danger">Mật khẩu không đúng!</div>';
                }
            }
            
            if (!isset($_SESSION['share_authenticated_' . $token])) {
                // Show password form
                $showPasswordForm = true;
            } else {
                $showPasswordForm = false;
            }
        } else {
            $showPasswordForm = false;
        }
        
        if (!$showPasswordForm) {
            // Get mails
            $mailIds = json_decode($shareData['mail_ids'], true);
            
            if (!empty($mailIds)) {
                $placeholders = str_repeat('?,', count($mailIds) - 1) . '?';
                $mails = $db->fetchAll(
                    "SELECT * FROM mails WHERE id IN ($placeholders) ORDER BY id",
                    $mailIds
                );
                
                // Update access count
                $db->query(
                    "UPDATE shared_mails SET accessed_count = accessed_count + 1, last_accessed = CURRENT_TIMESTAMP WHERE id = ?",
                    [$shareData['id']]
                );
            }
        }
    }
}

$pageTitle = 'Mail được chia sẻ';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="share-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="share-card">
                    <div class="card-header text-center">
                        <h3><i class="bi bi-share"></i> Mail được chia sẻ</h3>
                        <?php if ($shareData): ?>
                        <p class="text-muted mb-0">
                            Chia sẻ bởi: <strong><?= htmlspecialchars($shareData['username']) ?></strong> • 
                            <?= formatDate($shareData['created_at']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <?= $message ?>
                        
                        <?php if (isset($showPasswordForm) && $showPasswordForm): ?>
                        <!-- Password form -->
                        <div class="text-center">
                            <i class="bi bi-lock fs-1 text-warning mb-3"></i>
                            <h5>Nội dung được bảo vệ</h5>
                            <p class="text-muted">Vui lòng nhập mật khẩu để xem mail được chia sẻ</p>
                            
                            <form method="POST" class="mt-4">
                                <div class="row justify-content-center">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="share_password" 
                                                   placeholder="Nhập mật khẩu..." required>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-unlock"></i> Mở khóa
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php elseif (!empty($mails)): ?>
                        <!-- Mail list -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong><?= count($mails) ?> mail</strong> được chia sẻ với bạn. 
                            Sử dụng các nút sao chép để lấy thông tin đăng nhập.
                        </div>
                        
                        <?php foreach ($mails as $index => $mail): ?>
                        <div class="mail-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Mail #<?= $index + 1 ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> 
                                    <?= formatDate($mail['created_at']) ?>
                                </small>
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
                        
                        <div class="text-center mt-4">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Lưu ý:</strong> Mail này đã được chia sẻ công khai. 
                                Vui lòng sử dụng cẩn thận và không chia sẻ lại cho người khác.
                            </div>
                        </div>
                        
                        <?php elseif ($shareData): ?>
                        <div class="text-center">
                            <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                            <h5>Không có mail nào để hiển thị</h5>
                            <p class="text-muted">Có thể mail đã bị xóa hoặc không còn tồn tại.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($shareData && !isset($showPasswordForm)): ?>
                    <div class="card-footer text-center text-muted small">
                        <i class="bi bi-eye"></i> Đã truy cập <?= number_format($shareData['accessed_count']) ?> lần
                        <?php if ($shareData['last_accessed']): ?>
                            • Truy cập cuối: <?= formatDate($shareData['last_accessed']) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    
    <script>
    // Initialize copy functionality on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Copy functionality is already handled by main.js
    });
    </script>
</body>
</html>