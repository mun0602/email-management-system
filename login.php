<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    $redirect = isAdmin() ? '/admin/share-manager.php' : '/member/share-manager.php';
    header("Location: $redirect");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $redirect = $user['role'] === 'admin' ? '/admin/share-manager.php' : '/member/share-manager.php';
                header("Location: $redirect");
                exit();
            }
        }
        $error = 'Invalid username or password.';
    }
}

$title = 'Login - ' . APP_NAME;
?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center bg-primary text-white">
                <h4><i class="fas fa-sign-in-alt"></i> Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        Demo credentials:<br>
                        Admin: admin / admin123<br>
                        User: user1 / user123
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>