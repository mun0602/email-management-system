<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/share-functions.php';

requireAdmin();

$pdo = getDBConnection();

// Get all emails for admin
$stmt = $pdo->prepare("SELECT e.*, u.username FROM emails e JOIN users u ON e.user_id = u.id WHERE e.is_active = 1 ORDER BY e.email_address");
$stmt->execute();
$emails = $stmt->fetchAll();

// Get shared collections
$stmt = $pdo->prepare("
    SELECT sc.*, u.username, COUNT(se.id) as email_count 
    FROM shared_collections sc 
    JOIN users u ON sc.user_id = u.id
    LEFT JOIN shared_emails se ON sc.id = se.collection_id 
    GROUP BY sc.id 
    ORDER BY sc.created_at DESC
");
$stmt->execute();
$sharedCollections = $stmt->fetchAll();

$title = 'Admin Share Manager - ' . APP_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-share-alt"></i> Admin Share Manager</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createShareModal">
        <i class="fas fa-plus"></i> Create New Share
    </button>
</div>

<!-- Shared Collections -->
<div class="row">
    <div class="col-12">
        <h4>Shared Collections</h4>
        <?php if (empty($sharedCollections)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No shared collections yet. Create your first one!
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($sharedCollections as $collection): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card share-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($collection['collection_name']); ?></h6>
                        <span class="badge <?php echo $collection['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $collection['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <small class="text-muted">
                                By: <?php echo htmlspecialchars($collection['username']); ?><br>
                                Created: <?php echo formatDate($collection['created_at']); ?><br>
                                Emails: <?php echo $collection['email_count']; ?><br>
                                Views: <?php echo $collection['view_count']; ?>
                            </small>
                        </p>
                        <div class="mb-2">
                            <span class="share-token"><?php echo $collection['share_token']; ?></span>
                        </div>
                        <div class="d-flex gap-1 flex-wrap">
                            <button class="btn btn-outline-secondary btn-sm copy-btn" 
                                    onclick="copyToClipboard('<?php echo getShareUrl($collection['share_token']); ?>', this)">
                                <i class="fas fa-copy"></i> Copy Link
                            </button>
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="toggleCollection(<?php echo $collection['id']; ?>, <?php echo $collection['is_active'] ? 'true' : 'false'; ?>)">
                                <i class="fas fa-toggle-<?php echo $collection['is_active'] ? 'on' : 'off'; ?>"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="deleteCollection(<?php echo $collection['id']; ?>, '<?php echo htmlspecialchars($collection['collection_name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Share Modal -->
<div class="modal fade" id="createShareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Shared Collection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createShareForm" action="/api/create-share.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="collection_name" class="form-label">Collection Name</label>
                        <input type="text" class="form-control" id="collection_name" name="collection_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (Optional)</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Leave empty for no password protection">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Emails to Share</label>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllEmails()">
                                Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllEmails()">
                                Deselect All
                            </button>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 1rem;">
                            <?php if (empty($emails)): ?>
                            <p class="text-muted">No emails available to share.</p>
                            <?php else: ?>
                            <?php foreach ($emails as $email): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input email-checkbox" type="checkbox" 
                                       name="email_ids[]" value="<?php echo $email['id']; ?>" 
                                       id="email_<?php echo $email['id']; ?>">
                                <label class="form-check-label" for="email_<?php echo $email['id']; ?>">
                                    <strong><?php echo htmlspecialchars($email['email_address']); ?></strong>
                                    <?php if ($email['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($email['description']); ?></small>
                                    <?php endif; ?>
                                    <br><small class="text-muted">Owner: <?php echo htmlspecialchars($email['username']); ?></small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Share</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectAllEmails() {
    document.querySelectorAll('.email-checkbox').forEach(cb => cb.checked = true);
}

function deselectAllEmails() {
    document.querySelectorAll('.email-checkbox').forEach(cb => cb.checked = false);
}

document.getElementById('createShareForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const selectedEmails = document.querySelectorAll('.email-checkbox:checked');
    
    if (selectedEmails.length === 0) {
        showToast('Please select at least one email to share', 'error');
        return;
    }
    
    fetch('/api/create-share.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Share created successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Failed to create share', 'error');
        }
    })
    .catch(error => {
        showToast('Error occurred while creating share', 'error');
    });
});
</script>

<?php include '../includes/footer.php'; ?>