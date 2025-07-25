<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/share-functions.php';

$shareToken = $_GET['token'] ?? '';
$error = '';

if (empty($shareToken)) {
    http_response_code(404);
    die('Share not found');
}

$collection = getSharedCollection($shareToken);
if (!$collection) {
    http_response_code(404);
    die('Share not found or inactive');
}

// Check if password is required
if (empty($collection['password_hash'])) {
    header("Location: index.php?token=" . urlencode($shareToken));
    exit();
}

// Check if already authenticated
if (isset($_SESSION['shared_authenticated'][$shareToken]) && 
    $_SESSION['shared_authenticated'][$shareToken] > time()) {
    header("Location: index.php?token=" . urlencode($shareToken));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter the password.';
    } else {
        if (password_verify($password, $collection['password_hash'])) {
            // Set authentication session
            $_SESSION['shared_authenticated'][$shareToken] = time() + (24 * 60 * 60); // 24 hours
            header("Location: index.php?token=" . urlencode($shareToken));
            exit();
        } else {
            $error = 'Invalid password. Please try again.';
        }
    }
}

$title = 'Enter Password - ' . htmlspecialchars($collection['collection_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="public-share-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card public-share-card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-lock text-warning"></i>
                            Password Protected
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h6><?php echo htmlspecialchars($collection['collection_name']); ?></h6>
                            <p class="text-muted">
                                This shared collection is password protected. 
                                Please enter the password to access it.
                            </p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-unlock"></i> Access Collection
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt"></i>
                            Your session will be valid for 24 hours
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Auto-focus password field
    document.getElementById('password').focus();
    </script>
</body>
</html>