<?php
require_once __DIR__ . '/../includes/functions.php';

// Nếu đã đăng nhập, chuyển hướng
if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('../admin/index.php');
    } else {
        redirectTo('../member/index.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token bảo mật không hợp lệ';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            $user = $database->fetchOne(
                "SELECT * FROM users WHERE username = ?", 
                [$username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    redirectTo('../admin/index.php');
                } else {
                    redirectTo('../member/index.php');
                }
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm mt-5">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope-fill text-primary fs-1"></i>
                            <h2 class="h4 mt-2"><?= APP_NAME ?></h2>
                            <p class="text-muted">Đăng nhập vào hệ thống</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Đăng nhập
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Demo: admin/admin123 hoặc liên hệ quản trị viên
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>