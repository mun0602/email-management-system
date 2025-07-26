<?php
require_once '../includes/functions.php';
requireAdmin();

$db = new Database();
$message = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_single_mail') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($email) || empty($password)) {
            $message = '<div class="alert alert-danger">Vui lòng nhập đầy đủ email và mật khẩu!</div>';
        } else {
            try {
                $db->query("INSERT INTO emails (email, password) VALUES (?, ?)", [$email, $password]);
                $message = '<div class="alert alert-success">Thêm mail thành công!</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Email đã tồn tại!</div>';
            }
        }
    }
    
    elseif ($action === 'add_bulk_mails') {
        $bulk_mails = trim($_POST['bulk_mails'] ?? '');
        
        if (empty($bulk_mails)) {
            $message = '<div class="alert alert-danger">Vui lòng nhập danh sách mail!</div>';
        } else {
            $lines = explode("\n", $bulk_mails);
            $added = 0;
            $errors = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $parts = explode('|', $line, 2);
                if (count($parts) !== 2) {
                    $errors[] = "Dòng không đúng định dạng: " . htmlspecialchars($line);
                    continue;
                }
                
                $email = trim($parts[0]);
                $password = trim($parts[1]);
                
                if (empty($email) || empty($password)) {
                    $errors[] = "Email hoặc mật khẩu trống: " . htmlspecialchars($line);
                    continue;
                }
                
                try {
                    $db->query("INSERT INTO emails (email, password) VALUES (?, ?)", [$email, $password]);
                    $added++;
                } catch (PDOException $e) {
                    $errors[] = "Email đã tồn tại: " . htmlspecialchars($email);
                }
            }
            
            $message = '<div class="alert alert-success">Đã thêm ' . $added . ' mail thành công!</div>';
            if (!empty($errors)) {
                $message .= '<div class="alert alert-warning"><strong>Một số lỗi:</strong><br>' . implode('<br>', $errors) . '</div>';
            }
        }
    }
    
    elseif ($action === 'delete_mail') {
        $mail_id = (int)($_POST['mail_id'] ?? 0);
        
        if ($mail_id > 0) {
            $db->query("DELETE FROM emails WHERE id = ?", [$mail_id]);
            $message = '<div class="alert alert-success">Xóa mail thành công!</div>';
        }
    }
}

// Get mail statistics and list
$mailStats = getMailStats();
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "email LIKE ?";
    $params[] = "%$search%";
}

if ($status_filter !== '') {
    if ($status_filter === 'used') {
        $where_conditions[] = "is_used = 1";
    } else {
        $where_conditions[] = "is_used = 0";
    }
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

$total_mails = $db->fetch(
    "SELECT COUNT(*) as count FROM emails $where_clause",
    $params
)['count'];

$mails = $db->fetchAll(
    "SELECT e.*, 
            CASE WHEN e.is_used = 1 THEN 'used' ELSE 'available' END as status,
            e.used_at as taken_at, 
            u.username,
            a.name as app_name
     FROM emails e 
     LEFT JOIN users u ON e.used_by = u.id 
     LEFT JOIN apps a ON e.app_id = a.id
     $where_clause 
     ORDER BY e.created_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

$total_pages = ceil($total_mails / $limit);

$pageTitle = getPageTitle('admin_mails');
include '../includes/header.php';
?>

<?= $message ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-envelope-plus"></i> Quản lý Mail</h2>
    <div>
        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addSingleModal">
            <i class="bi bi-plus-circle"></i> Thêm 1 Mail
        </button>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBulkModal">
            <i class="bi bi-plus-square"></i> Thêm hàng loạt
        </button>
    </div>
</div>

<!-- Statistics cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number"><?= $mailStats['total'] ?></div>
                <div class="stats-label">Tổng số Mail</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $mailStats['available'] ?></div>
                <div class="stats-label">Mail khả dụng</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $mailStats['used'] ?></div>
                <div class="stats-label">Mail đã sử dụng</div>
            </div>
        </div>
    </div>
</div>

<!-- Search and filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" 
                       placeholder="Tìm kiếm email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="available" <?= $status_filter === 'available' ? 'selected' : '' ?>>Khả dụng</option>
                    <option value="used" <?= $status_filter === 'used' ? 'selected' : '' ?>>Đã sử dụng</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Tìm kiếm
                </button>
            </div>
            <div class="col-md-2">
                <a href="mails.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Mail list -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Danh sách Mail (<?= number_format($total_mails) ?> mail)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Email</th>
                        <th>Mật khẩu</th>
                        <th>Trạng thái</th>
                        <th>Người sử dụng</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mails)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Không tìm thấy mail nào
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($mails as $mail): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="mail-checkbox" value="<?= $mail['id'] ?>">
                            </td>
                            <td>
                                <code><?= htmlspecialchars($mail['email']) ?></code>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-copy ms-1" 
                                        data-target="#email-<?= $mail['id'] ?>" title="Copy email">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <span id="email-<?= $mail['id'] ?>" style="display: none;"><?= htmlspecialchars($mail['email']) ?></span>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($mail['password']) ?></code>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-copy ms-1" 
                                        data-target="#password-<?= $mail['id'] ?>" title="Copy password">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <span id="password-<?= $mail['id'] ?>" style="display: none;"><?= htmlspecialchars($mail['password']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $mail['status'] === 'used' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= $mail['status'] === 'used' ? 'Đã sử dụng' : 'Khả dụng' ?>
                                </span>
                                <?php if ($mail['app_name']): ?>
                                    <br><small class="text-muted">App: <?= htmlspecialchars($mail['app_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $mail['username'] ? htmlspecialchars($mail['username']) : '<span class="text-muted">-</span>' ?>
                                <?php if ($mail['taken_at']): ?>
                                    <br><small class="text-muted"><?= formatDate($mail['taken_at']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= formatDate($mail['created_at']) ?></td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mail này?')">
                                    <input type="hidden" name="action" value="delete_mail">
                                    <input type="hidden" name="mail_id" value="<?= $mail['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <!-- Share selected button -->
        <div class="text-center mt-3">
            <button type="button" class="btn btn-info" id="shareSelected" disabled data-bs-toggle="modal" data-bs-target="#shareModal">
                <i class="bi bi-share"></i> Chia sẻ đã chọn (<span class="badge bg-light text-dark">0</span>)
            </button>
        </div>
    </div>
</div>

<!-- Add Single Mail Modal -->
<div class="modal fade" id="addSingleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Mail mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_single_mail">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="text" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm mail</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Bulk Mails Modal -->
<div class="modal fade" id="addBulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Mail hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_bulk_mails">
                    
                    <div class="mb-3">
                        <label for="bulk_mails" class="form-label">Danh sách Mail</label>
                        <textarea class="form-control" id="bulk_mails" name="bulk_mails" rows="10" 
                                  placeholder="Nhập mỗi dòng theo định dạng: email|password&#10;Ví dụ:&#10;test1@gmail.com|password123&#10;test2@gmail.com|mypass456" required></textarea>
                        <div class="form-text">
                            Mỗi dòng một cặp email|password. Ví dụ: <code>test@gmail.com|password123</code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Thêm tất cả</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chia sẻ Mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="shareForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="share_password" class="form-label">Mật khẩu bảo vệ (tùy chọn)</label>
                        <input type="password" class="form-control" id="share_password" name="password">
                        <div class="form-text">Để trống nếu không muốn đặt mật khẩu</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo link chia sẻ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Share Link Modal -->
<div class="modal fade" id="shareLinkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link chia sẻ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="shareLink" class="form-label">Link chia sẻ</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareLink" readonly>
                        <button type="button" class="btn btn-outline-primary" id="copyShareLink">
                            <i class="bi bi-clipboard"></i> Sao chép link
                        </button>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Link này có thể được chia sẻ công khai. Người khác có thể truy cập mà không cần đăng nhập.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>