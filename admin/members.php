<?php
require_once '../includes/functions.php';
requireAdmin();

$db = new Database();
$message = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_member') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        
        if (empty($username) || empty($password)) {
            $message = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
        } else {
            try {
                $db->getConnection()->beginTransaction();
                
                // Create user
                $db->query(
                    "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'user')",
                    [$username, md5($password), $full_name]
                );
                $userId = $db->lastInsertId();
                
                // Create default limits for all apps
                $apps = getAllApps();
                foreach ($apps as $app) {
                    $db->query(
                        "INSERT INTO user_limits (user_id, app_id, daily_limit, used_today) VALUES (?, ?, ?, ?)",
                        [$userId, $app['id'], 25, 0]
                    );
                }
                
                $db->getConnection()->commit();
                $message = '<div class="alert alert-success">Thêm thành viên thành công!</div>';
                
            } catch (PDOException $e) {
                $db->getConnection()->rollback();
                $message = '<div class="alert alert-danger">Tên đăng nhập đã tồn tại!</div>';
            }
        }
    }
    
    elseif ($action === 'update_limits') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $limits = $_POST['limits'] ?? [];
        
        if ($user_id > 0 && !empty($limits)) {
            try {
                $db->getConnection()->beginTransaction();
                
                foreach ($limits as $app_id => $limit) {
                    $daily_limit = (int)$limit;
                    if ($daily_limit >= 0) {
                        $db->query(
                            "UPDATE user_limits SET daily_limit = ? WHERE user_id = ? AND app_id = ?",
                            [$daily_limit, $user_id, $app_id]
                        );
                    }
                }
                
                $db->getConnection()->commit();
                $message = '<div class="alert alert-success">Cập nhật hạn mức thành công!</div>';
                
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                $message = '<div class="alert alert-danger">Có lỗi xảy ra: ' . $e->getMessage() . '</div>';
            }
        }
    }
    
    elseif ($action === 'delete_member') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                $db->getConnection()->beginTransaction();
                
                // Delete user limits
                $db->query("DELETE FROM user_limits WHERE user_id = ?", [$user_id]);
                
                // Delete user (but keep history for integrity)
                $db->query("DELETE FROM users WHERE id = ? AND role = 'user'", [$user_id]);
                
                $db->getConnection()->commit();
                $message = '<div class="alert alert-success">Xóa thành viên thành công!</div>';
                
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                $message = '<div class="alert alert-danger">Có lỗi xảy ra: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Get all users with their statistics
$users = $db->fetchAll(
    "SELECT u.*, 
            COUNT(h.id) as total_emails_taken,
            MAX(h.taken_at) as last_activity
     FROM users u 
     LEFT JOIN email_history h ON u.id = h.user_id 
     WHERE u.role = 'user' 
     GROUP BY u.id 
     ORDER BY u.created_at DESC"
);

// Get apps for limits management
$apps = getAllApps();

$pageTitle = 'Quản lý thành viên';
include '../includes/header.php';
?>

<?= $message ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Quản lý thành viên</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="bi bi-person-plus"></i> Thêm thành viên
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Thành viên</th>
                        <th>Tổng email đã lấy</th>
                        <th>Hạn mức hôm nay</th>
                        <th>Hoạt động cuối</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Chưa có thành viên nào
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <?php
                            // Get user's today limits
                            $userLimits = $db->fetchAll(
                                "SELECT ul.*, a.name as app_name 
                                 FROM user_limits ul 
                                 JOIN apps a ON ul.app_id = a.id 
                                 WHERE ul.user_id = ?",
                                [$user['id']]
                            );
                            $totalRemaining = array_sum(array_map(function($l) { 
                                return max(0, $l['daily_limit'] - $l['used_today']); 
                            }, $userLimits));
                            $totalUsedToday = array_sum(array_column($userLimits, 'used_today'));
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <?php if ($user['full_name']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($user['full_name']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $user['total_emails_taken'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $totalRemaining ?> còn lại</span>
                                <span class="badge bg-warning"><?= $totalUsedToday ?> đã dùng</span>
                            </td>
                            <td>
                                <?= $user['last_activity'] ? formatDate($user['last_activity']) : '<span class="text-muted">Chưa có</span>' ?>
                            </td>
                            <td><?= formatDate($user['created_at']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editLimits(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="bi bi-gear"></i> Hạn mức
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thành viên này? Lịch sử email sẽ được giữ lại.')">
                                    <input type="hidden" name="action" value="delete_member">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
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
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm thành viên mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_member">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ tên (tùy chọn)</label>
                        <input type="text" class="form-control" id="full_name" name="full_name">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Thành viên mới sẽ được thiết lập hạn mức mặc định là 25 email/ngày cho mỗi ứng dụng.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm thành viên</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Limits Modal -->
<div class="modal fade" id="editLimitsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quản lý hạn mức</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_limits">
                    <input type="hidden" name="user_id" id="limits_user_id">
                    
                    <h6 id="limits_username"></h6>
                    <div class="mb-3">
                        <small class="text-muted">Thiết lập hạn mức email hàng ngày cho từng ứng dụng</small>
                    </div>
                    
                    <div id="limits_container">
                        <!-- Dynamic content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật hạn mức</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLimits(userId, username) {
    document.getElementById('limits_user_id').value = userId;
    document.getElementById('limits_username').textContent = 'Hạn mức cho: ' + username;
    
    // Fetch user limits via AJAX
    fetch('/api/users.php?action=get_limits&user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                data.limits.forEach(limit => {
                    html += `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">${limit.app_name}</label>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="number" class="form-control" 
                                           name="limits[${limit.app_id}]" 
                                           value="${limit.daily_limit}" 
                                           min="0" max="999">
                                    <span class="input-group-text">email/ngày</span>
                                </div>
                                <small class="text-muted">Đã dùng hôm nay: ${limit.used_today}</small>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('limits_container').innerHTML = html;
                new bootstrap.Modal(document.getElementById('editLimitsModal')).show();
            } else {
                alert('Không thể tải dữ liệu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải dữ liệu');
        });
}
</script>

<?php include '../includes/footer.php'; ?>