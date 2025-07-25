<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Quản lý Email - ' . APP_NAME;
$currentPage = 'emails';

$message = '';
$messageType = '';

// Xử lý thêm email bulk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token bảo mật không hợp lệ';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'bulk_add') {
            $emailsText = $_POST['emails_text'] ?? '';
            
            if (empty($emailsText)) {
                $message = 'Vui lòng nhập danh sách email';
                $messageType = 'danger';
            } else {
                $lines = explode("\n", $emailsText);
                $emailData = array_filter(array_map('trim', $lines));
                
                if (empty($emailData)) {
                    $message = 'Không có email hợp lệ nào được tìm thấy';
                    $messageType = 'danger';
                } else {
                    $result = addEmails($emailData);
                    
                    if ($result['success'] > 0) {
                        $message = "Đã thêm thành công {$result['success']} email";
                        if (!empty($result['errors'])) {
                            $message .= ". Có " . count($result['errors']) . " lỗi.";
                        }
                        $messageType = 'success';
                    } else {
                        $message = 'Không thể thêm email nào. ' . implode(', ', $result['errors']);
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
}

// Xử lý xóa email
if (isset($_GET['delete']) && verifyCSRFToken($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    try {
        $database->query("DELETE FROM emails WHERE id = ?", [$id]);
        $message = 'Xóa email thành công';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Không thể xóa email';
        $messageType = 'danger';
    }
}

// Lấy danh sách emails với phân trang
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;
$search = sanitizeInput($_GET['search'] ?? '');

$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE email LIKE ?";
    $params[] = "%$search%";
}

$totalEmails = $database->fetchOne(
    "SELECT COUNT(*) as count FROM emails $whereClause", 
    $params
)['count'];

$totalPages = ceil($totalEmails / $limit);

$emails = $database->fetchAll(
    "SELECT e.*, 
            u.username as used_by,
            eu.app_name,
            eu.used_at
     FROM emails e 
     LEFT JOIN email_usage eu ON e.id = eu.email_id 
     LEFT JOIN users u ON eu.user_id = u.id 
     $whereClause
     ORDER BY e.created_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

$stats = [
    'total' => $database->fetchOne("SELECT COUNT(*) as count FROM emails")['count'],
    'used' => $database->fetchOne("SELECT COUNT(*) as count FROM emails WHERE is_used = 1")['count'],
    'available' => $database->fetchOne("SELECT COUNT(*) as count FROM emails WHERE is_used = 0")['count']
];

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-envelope text-primary"></i>
                Quản lý Email
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                <i class="bi bi-envelope-plus"></i>
                Thêm Email
            </button>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="stat-icon bg-primary text-white mx-auto mb-3">
                    <i class="bi bi-envelope"></i>
                </div>
                <h3 class="mb-1"><?= number_format($stats['total']) ?></h3>
                <p class="text-muted mb-0">Tổng email</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="stat-icon bg-success text-white mx-auto mb-3">
                    <i class="bi bi-envelope-check"></i>
                </div>
                <h3 class="mb-1"><?= number_format($stats['available']) ?></h3>
                <p class="text-muted mb-0">Khả dụng</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="stat-icon bg-warning text-white mx-auto mb-3">
                    <i class="bi bi-envelope-x"></i>
                </div>
                <h3 class="mb-1"><?= number_format($stats['used']) ?></h3>
                <p class="text-muted mb-0">Đã sử dụng</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="m-0 font-weight-bold text-primary">Danh sách Email</h6>
            </div>
            <div class="col-auto">
                <form method="GET" class="d-flex">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Tìm kiếm email..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-secondary">Tìm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Trạng thái</th>
                        <th>Sử dụng bởi</th>
                        <th>Ứng dụng</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($emails)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-envelope fs-1 text-muted"></i>
                                <p class="text-muted mt-2">
                                    <?= !empty($search) ? 'Không tìm thấy email nào' : 'Chưa có email nào' ?>
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($emails as $email): ?>
                            <tr>
                                <td><?= $email['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?= htmlspecialchars($email['email']) ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                                data-copy="<?= htmlspecialchars($email['email']) ?>" 
                                                title="Sao chép email">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <code class="me-2"><?= htmlspecialchars($email['password']) ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                                data-copy="<?= htmlspecialchars($email['password']) ?>" 
                                                title="Sao chép password">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($email['is_used']): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle"></i> Đã sử dụng
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Khả dụng
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($email['used_by']): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($email['used_by']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($email['app_name']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($email['app_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= formatDate($email['created_at']) ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="?delete=<?= $email['id'] ?>&token=<?= generateCSRFToken() ?>" 
                                       class="btn btn-sm btn-outline-danger confirm-action"
                                       data-message="Bạn có chắc chắn muốn xóa email '<?= htmlspecialchars($email['email']) ?>'?">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Email Modal -->
<div class="modal fade" id="addEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-envelope-plus text-primary"></i>
                    Thêm Email hàng loạt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="bulk_add">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Hướng dẫn:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Mỗi dòng chứa một email theo định dạng: <code>email|password</code></li>
                            <li>Ví dụ: <code>test@example.com|password123</code></li>
                            <li>Có thể thêm nhiều email cùng lúc</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label for="emailsText" class="form-label">Danh sách Email</label>
                        <textarea class="form-control" id="emailsText" name="emails_text" 
                                  rows="10" placeholder="test1@example.com|password123&#10;test2@example.com|password456&#10;test3@example.com|password789" required></textarea>
                        <div class="form-text">
                            Nhập danh sách email, mỗi email một dòng theo định dạng email|password
                        </div>
                        <div class="invalid-feedback">
                            Vui lòng nhập danh sách email
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Thêm Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>