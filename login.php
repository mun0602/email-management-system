<?php
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'http://localhost:8000/admin/' : 'http://localhost:8000/user/'));
    exit;
}

$error = '';
$debug = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $debug = "POST received - Username: '$username', Password length: " . strlen($password);
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        if (login($username, $password)) {
            $debug .= ' - Login successful';
            header('Location: ' . (isAdmin() ? 'http://localhost:8000/admin/' : 'http://localhost:8000/user/'));
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            $debug .= ' - Login failed';
        }
    }
}

$pageTitle = getPageTitle('login');
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
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-envelope-check"></i>
                <h3 class="mb-0">Hệ thống quản lý Mail</h3>
                <p class="text-muted">Đăng nhập để tiếp tục</p>
            </div>
            
            <div class="login-body">
                <?php if ($debug): ?>
                <div class="alert alert-info">
                    <small>Debug: <?= htmlspecialchars($debug) ?></small>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Đăng nhập
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        Demo accounts:<br>
                        Admin: <code>admin</code> / <code>admin123</code><br>
                        User: <code>user1</code> / <code>user123</code>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>