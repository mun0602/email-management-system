<?php
// Public shared emails view page (no login required)
require_once '../config/database.php';

$shareCode = trim($_GET['code'] ?? '');
$error = '';
$emails = [];
$shareInfo = null;
$requiresPassword = false;

if (empty($shareCode)) {
    $error = 'Mã chia sẻ không hợp lệ!';
} else {
    $db = new Database();
    
    // Get share record
    $shareInfo = $db->fetch(
        "SELECT * FROM shared_emails WHERE share_code = ?",
        [$shareCode]
    );
    
    if (!$shareInfo) {
        $error = 'Liên kết chia sẻ không tồn tại!';
    } elseif ($shareInfo['expires_at'] && strtotime($shareInfo['expires_at']) < time()) {
        $error = 'Liên kết chia sẻ đã hết hạn!';
    } elseif ($shareInfo['password'] && !isset($_POST['password'])) {
        $requiresPassword = true;
    } elseif ($shareInfo['password'] && isset($_POST['password'])) {
        // Check password
        if (!password_verify($_POST['password'], $shareInfo['password'])) {
            $error = 'Mật khẩu không đúng!';
            $requiresPassword = true;
        } else {
            // Password correct, show emails
            $emailIds = json_decode($shareInfo['email_ids'], true);
            $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';
            
            $emails = $db->fetchAll(
                "SELECT e.email, e.password, a.name as app_name, e.created_at 
                 FROM emails e 
                 LEFT JOIN apps a ON e.app_id = a.id 
                 WHERE e.id IN ($placeholders)
                 ORDER BY e.created_at DESC",
                $emailIds
            );
        }
    } else {
        // No password required, show emails directly
        $emailIds = json_decode($shareInfo['email_ids'], true);
        $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';
        
        $emails = $db->fetchAll(
            "SELECT e.email, e.password, a.name as app_name, e.created_at 
             FROM emails e 
             LEFT JOIN apps a ON e.app_id = a.id 
             WHERE e.id IN ($placeholders)
             ORDER BY e.created_at DESC",
            $emailIds
        );
    }
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email được chia sẻ - Hệ thống quản lý Mail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="share-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="share-card">
                    <div class="card-header text-center py-4">
                        <h3 class="mb-2">
                            <i class="bi bi-share text-primary"></i>
                            Email được chia sẻ
                        </h3>
                        <?php if ($shareInfo): ?>
                            <small class="text-muted">
                                Chia sẻ ngày <?= formatDate($shareInfo['created_at']) ?>
                                <?php if ($shareInfo['expires_at']): ?>
                                    • Hết hạn: <?= formatDate($shareInfo['expires_at']) ?>
                                <?php endif; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger text-center">
                                <i class="bi bi-exclamation-triangle fs-2"></i>
                                <h5 class="mt-2"><?= htmlspecialchars($error) ?></h5>
                            </div>
                        <?php elseif ($requiresPassword): ?>
                            <div class="text-center mb-4">
                                <i class="bi bi-shield-lock text-warning fs-1"></i>
                                <h5 class="mt-2">Email được bảo vệ bằng mật khẩu</h5>
                                <p class="text-muted">Vui lòng nhập mật khẩu để xem nội dung</p>
                            </div>
                            
                            <form method="POST" class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="bi bi-key"></i>
                                        </span>
                                        <input type="password" class="form-control" name="password" 
                                               placeholder="Nhập mật khẩu..." required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-unlock"></i> Mở khóa
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php elseif (!empty($emails)): ?>
                            <div class="text-center mb-4">
                                <i class="bi bi-envelope-check text-success fs-1"></i>
                                <h5 class="mt-2"><?= count($emails) ?> Email được chia sẻ</h5>
                                <p class="text-muted">Sử dụng nút copy để sao chép thông tin</p>
                            </div>
                            
                            <div class="row">
                                <?php foreach ($emails as $index => $email): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card border">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                Email #<?= $index + 1 ?>
                                                <?php if ($email['app_name']): ?>
                                                    <span class="badge bg-primary ms-2"><?= htmlspecialchars($email['app_name']) ?></span>
                                                <?php endif; ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Email:</label>
                                                <div class="mail-credentials">
                                                    <span id="email-<?= $index ?>"><?= htmlspecialchars($email['email']) ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary copy-btn" 
                                                            onclick="copyToClipboard('email-<?= $index ?>', this)">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label text-muted small">Mật khẩu:</label>
                                                <div class="mail-credentials">
                                                    <span id="password-<?= $index ?>"><?= htmlspecialchars($email['password']) ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary copy-btn" 
                                                            onclick="copyToClipboard('password-<?= $index ?>', this)">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= formatDate($email['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Bulk copy options -->
                            <div class="text-center mt-4 p-3 bg-light rounded">
                                <h6>Sao chép hàng loạt:</h6>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="copyAllEmails()">
                                        <i class="bi bi-envelope"></i> Tất cả Email
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="copyAllPasswords()">
                                        <i class="bi bi-key"></i> Tất cả mật khẩu
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="copyAllFormatted()">
                                        <i class="bi bi-list"></i> Định dạng email|password
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle fs-2"></i>
                                <h5 class="mt-2">Không có email nào để hiển thị</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$error && !$requiresPassword): ?>
                    <div class="card-footer text-center py-3">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i>
                            Trang này được chia sẻ công khai từ Hệ thống quản lý Mail
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(elementId, button) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check"></i>';
                button.classList.add('btn-success');
                button.classList.remove('btn-outline-primary');
                
                setTimeout(() => {
                    button.innerHTML = originalHtml;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                }, 2000);
            });
        }
        
        function copyAllEmails() {
            const emails = [];
            <?php if (!empty($emails)): ?>
            <?php foreach ($emails as $index => $email): ?>
            emails.push('<?= addslashes($email['email']) ?>');
            <?php endforeach; ?>
            <?php endif; ?>
            
            navigator.clipboard.writeText(emails.join('\n')).then(() => {
                alert('Đã sao chép ' + emails.length + ' email vào clipboard!');
            });
        }
        
        function copyAllPasswords() {
            const passwords = [];
            <?php if (!empty($emails)): ?>
            <?php foreach ($emails as $index => $email): ?>
            passwords.push('<?= addslashes($email['password']) ?>');
            <?php endforeach; ?>
            <?php endif; ?>
            
            navigator.clipboard.writeText(passwords.join('\n')).then(() => {
                alert('Đã sao chép ' + passwords.length + ' mật khẩu vào clipboard!');
            });
        }
        
        function copyAllFormatted() {
            const formatted = [];
            <?php if (!empty($emails)): ?>
            <?php foreach ($emails as $index => $email): ?>
            formatted.push('<?= addslashes($email['email']) ?>|<?= addslashes($email['password']) ?>');
            <?php endforeach; ?>
            <?php endif; ?>
            
            navigator.clipboard.writeText(formatted.join('\n')).then(() => {
                alert('Đã sao chép ' + formatted.length + ' dòng định dạng email|password vào clipboard!');
            });
        }
    </script>
</body>
</html>