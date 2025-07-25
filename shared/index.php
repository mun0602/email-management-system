<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/share-functions.php';

$shareToken = $_GET['token'] ?? '';

if (empty($shareToken)) {
    http_response_code(404);
    die('Share not found');
}

$collection = getSharedCollection($shareToken);
if (!$collection) {
    http_response_code(404);
    die('Share not found or inactive');
}

// Check if password authentication is required
$requiresPassword = !empty($collection['password_hash']);
$isAuthenticated = false;

if ($requiresPassword) {
    $isAuthenticated = isset($_SESSION['shared_authenticated'][$shareToken]) && 
                      $_SESSION['shared_authenticated'][$shareToken] > time();
    
    if (!$isAuthenticated) {
        // Redirect to password page
        header("Location: password.php?token=" . urlencode($shareToken));
        exit();
    }
}

// Log access
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
logShareAccess($collection['id'], $ipAddress, $userAgent);
incrementShareViewCount($collection['id']);

// Get shared emails
$emails = getSharedCollectionEmails($collection['id']);

$title = htmlspecialchars($collection['collection_name']) . ' - Shared Collection';
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
            <div class="col-lg-10">
                <div class="card public-share-card">
                    <div class="card-header text-center">
                        <h2 class="mb-0">
                            <i class="fas fa-share-alt text-primary"></i>
                            <?php echo htmlspecialchars($collection['collection_name']); ?>
                        </h2>
                        <p class="mb-0 text-muted">
                            <?php echo count($emails); ?> email(s) shared
                            <?php if ($requiresPassword): ?>
                            <i class="fas fa-lock ms-2" title="Password Protected"></i>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($emails)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i>
                            No emails in this collection.
                        </div>
                        <?php else: ?>
                        <div class="email-grid">
                            <?php foreach ($emails as $email): ?>
                            <div class="email-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-envelope text-primary"></i>
                                        <?php echo htmlspecialchars($email['email_address']); ?>
                                    </h6>
                                    <button class="btn btn-outline-secondary btn-sm copy-btn" 
                                            onclick="copyToClipboard('<?php echo htmlspecialchars($email['email_address']); ?>', this)"
                                            title="Copy Email">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                
                                <?php if (!empty($email['password'])): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Password:</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <code class="password-field" data-password="<?php echo htmlspecialchars($email['password']); ?>">
                                            ••••••••
                                        </code>
                                        <button class="btn btn-outline-secondary btn-sm copy-btn" 
                                                onclick="copyToClipboard('<?php echo htmlspecialchars($email['password']); ?>', this)"
                                                title="Copy Password">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="togglePassword(this)"
                                                title="Show/Hide Password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($email['description'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        <?php echo htmlspecialchars($email['description']); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($email['category'])): ?>
                                <div>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($email['category']); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button class="btn btn-primary" onclick="copyAllEmails()">
                                <i class="fas fa-copy"></i> Copy All Emails
                            </button>
                            <button class="btn btn-outline-primary" onclick="copyAllPasswords()" 
                                    <?php echo !array_filter($emails, fn($e) => !empty($e['password'])) ? 'disabled' : ''; ?>>
                                <i class="fas fa-key"></i> Copy All Passwords
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>
                            <i class="fas fa-clock"></i>
                            Shared on <?php echo formatDate($collection['created_at']); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Copied to clipboard!', 'success');
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary', 'btn-outline-info');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }).catch(function(err) {
            showToast('Failed to copy to clipboard', 'error');
        });
    }

    function togglePassword(button) {
        const passwordField = button.parentElement.querySelector('.password-field');
        const actualPassword = passwordField.dataset.password;
        const icon = button.querySelector('i');
        
        if (passwordField.textContent === '••••••••') {
            passwordField.textContent = actualPassword;
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.textContent = '••••••••';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function copyAllEmails() {
        const emails = <?php echo json_encode(array_column($emails, 'email_address')); ?>;
        const emailList = emails.join('\n');
        
        navigator.clipboard.writeText(emailList).then(function() {
            showToast(`Copied ${emails.length} emails to clipboard!`, 'success');
        }).catch(function(err) {
            showToast('Failed to copy emails', 'error');
        });
    }

    function copyAllPasswords() {
        const passwords = <?php echo json_encode(array_filter(array_column($emails, 'password'))); ?>;
        if (passwords.length === 0) {
            showToast('No passwords to copy', 'info');
            return;
        }
        
        const passwordList = passwords.join('\n');
        
        navigator.clipboard.writeText(passwordList).then(function() {
            showToast(`Copied ${passwords.length} passwords to clipboard!`, 'success');
        }).catch(function(err) {
            showToast('Failed to copy passwords', 'error');
        });
    }

    function showToast(message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    </script>
</body>
</html>