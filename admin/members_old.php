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
                    );
                }
                $message = '<div class="alert alert-success">Cập nhật thành viên thành công!</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Tên đăng nhập đã tồn tại!</div>';
            }
        }
    }
    
    elseif ($action === 'delete_member') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            $db->query("DELETE FROM users WHERE id = ? AND role = 'user'", [$user_id]);
            $message = '<div class="alert alert-success">Xóa thành viên thành công!</div>';
        }
    }
}

// Get all users (except admins)
$users = $db->fetchAll(
    "SELECT u.*, 
            COUNT(h.id) as mail_count,
            MAX(h.taken_at) as last_activity
     FROM users u 
     LEFT JOIN mail_history h ON u.id = h.user_id 
     WHERE u.role = 'user' 
     GROUP BY u.id 
     ORDER BY u.created_at DESC"
);

$pageTitle = getPageTitle('admin_members');
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
                        <th>Tên đăng nhập</th>
                        <th>Giới hạn Mail</th>
                        <th>Đã sử dụng</th>
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
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= $user['mail_limit'] ?> mail</span>
                            </td>
                            <td>
                                <span class="badge <?= $user['mail_count'] >= $user['mail_limit'] ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $user['mail_count'] ?> mail
                                </span>
                            </td>
                            <td>
                                <?= $user['last_activity'] ? formatDate($user['last_activity']) : '<span class="text-muted">Chưa có</span>' ?>
                            </td>
                            <td><?= formatDate($user['created_at']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editMember(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thành viên này?')">
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
                        <label for="mail_limit" class="form-label">Giới hạn Mail</label>
                        <input type="number" class="form-control" id="mail_limit" name="mail_limit" value="10" min="1" required>
                        <div class="form-text">Số lượng mail tối đa mà thành viên có thể lấy</div>
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

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa thành viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_member">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <div class="form-text">Để trống nếu không muốn thay đổi mật khẩu</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_mail_limit" class="form-label">Giới hạn Mail</label>
                        <input type="number" class="form-control" id="edit_mail_limit" name="mail_limit" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMember(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_mail_limit').value = user.mail_limit;
    
    new bootstrap.Modal(document.getElementById('editMemberModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>