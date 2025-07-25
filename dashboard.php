<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();

// Get user's email count
$stmt = $pdo->prepare("SELECT COUNT(*) as email_count FROM emails WHERE user_id = ? AND is_active = 1");
$stmt->execute([$userId]);
$emailCount = $stmt->fetch()['email_count'];

// Get user's shared collections count
$stmt = $pdo->prepare("SELECT COUNT(*) as share_count FROM shared_collections WHERE user_id = ?");
$stmt->execute([$userId]);
$shareCount = $stmt->fetch()['share_count'];

// If user has no emails, redirect to add some sample data
if ($emailCount == 0) {
    header('Location: setup-sample-data.php');
    exit();
}

$title = 'Dashboard - ' . APP_NAME;
?>
<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p class="text-muted">Manage your emails and shared collections</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary"><?php echo $emailCount; ?></h5>
                <p class="card-text">My Emails</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success"><?php echo $shareCount; ?></h5>
                <p class="card-text">Shared Collections</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info">
                    <?php if (isAdmin()): ?>
                    <i class="fas fa-crown"></i> Admin
                    <?php else: ?>
                    <i class="fas fa-user"></i> User
                    <?php endif; ?>
                </h5>
                <p class="card-text">Role</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning">
                    <i class="fas fa-share-alt"></i>
                </h5>
                <p class="card-text">
                    <a href="<?php echo isAdmin() ? '/admin/share-manager.php' : '/member/share-manager.php'; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        Manage Shares
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-envelope"></i> Recent Emails</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM emails WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$userId]);
                $recentEmails = $stmt->fetchAll();
                ?>
                
                <?php if (empty($recentEmails)): ?>
                <p class="text-muted">No emails yet.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentEmails as $email): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($email['email_address']); ?></strong>
                            <?php if ($email['description']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($email['description']); ?></small>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" 
                                onclick="copyToClipboard('<?php echo htmlspecialchars($email['email_address']); ?>', this)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-share-alt"></i> Recent Shares</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM shared_collections WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$userId]);
                $recentShares = $stmt->fetchAll();
                ?>
                
                <?php if (empty($recentShares)): ?>
                <p class="text-muted">No shared collections yet.</p>
                <a href="<?php echo isAdmin() ? '/admin/share-manager.php' : '/member/share-manager.php'; ?>" 
                   class="btn btn-primary btn-sm">
                    Create Your First Share
                </a>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentShares as $share): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($share['collection_name']); ?></strong>
                            <br><small class="text-muted">Views: <?php echo $share['view_count']; ?></small>
                            <span class="badge <?php echo $share['is_active'] ? 'bg-success' : 'bg-secondary'; ?> ms-2">
                                <?php echo $share['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" 
                                onclick="copyToClipboard('<?php echo getShareUrl($share['share_token']); ?>', this)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>