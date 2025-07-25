<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Quản lý thành viên - ' . APP_NAME;
$currentPage = 'users';

$message = '';
$messageType = '';

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token bảo mật không hợp lệ';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $username = sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $email_limit = (int)($_POST['email_limit'] ?? 10);
                
                if (empty($username) || empty($password)) {
                    $message = 'Vui lòng nhập đầy đủ thông tin';
                    $messageType = 'danger';
                } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
                    $message = 'Mật khẩu phải có ít nhất ' . PASSWORD_MIN_LENGTH . ' ký tự';
                    $messageType = 'danger';
                } else {
                    $result = createUser($username, $password, 'member', $email_limit);
                    if ($result) {
                        $message = 'Thêm thành viên thành công';
                        $messageType = 'success';
                    } else {
                        $message = 'Không thể thêm thành viên. Tên đăng nhập có thể đã tồn tại';
                        $messageType = 'danger';
                    }
                }
                break;
                
            case 'edit':
                $id = (int)($_POST['user_id'] ?? 0);
                $username = sanitizeInput($_POST['username'] ?? '');
                $email_limit = (int)($_POST['email_limit'] ?? 10);
                $password = $_POST['password'] ?? '';
                
                if (empty($username)) {
                    $message = 'Vui lòng nhập tên đăng nhập';
                    $messageType = 'danger';
                } elseif (!empty($password) && strlen($password) < PASSWORD_MIN_LENGTH) {
                    $message = 'Mật khẩu phải có ít nhất ' . PASSWORD_MIN_LENGTH . ' ký tự';
                    $messageType = 'danger';
                } else {
                    $result = updateUser($id, $username, 'member', $email_limit, $password ?: null);
                    if ($result) {
                        $message = 'Cập nhật thành viên thành công';
                        $messageType = 'success';
                    } else {
                        $message = 'Không thể cập nhật thành viên';
                        $messageType = 'danger';
                    }
                }
                break;
        }
    }
}

// Xử lý xóa user
if (isset($_GET['delete']) && verifyCSRFToken($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    if (deleteUser($id)) {
        $message = 'Xóa thành viên thành công';
        $messageType = 'success';
    } else {
        $message = 'Không thể xóa thành viên';
        $messageType = 'danger';
    }
}

// Lấy danh sách users
$users = $database->fetchAll(
    "SELECT u.*, 
            COUNT(eu.id) as total_used,
            (u.email_limit - COUNT(eu.id)) as remaining
     FROM users u 
     LEFT JOIN email_usage eu ON u.id = eu.user_id 
     WHERE u.role = 'member'
     GROUP BY u.id 
     ORDER BY u.created_at DESC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-people text-primary"></i>
                Quản lý thành viên
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i>
                Thêm thành viên
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

<div class="card shadow">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="m-0 font-weight-bold text-primary">Danh sách thành viên</h6>
            </div>
            <div class="col-auto">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Tìm kiếm..." data-search="users">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Giới hạn email</th>
                        <th>Đã sử dụng</th>
                        <th>Còn lại</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-people fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Chưa có thành viên nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr data-searchable="users">
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= number_format($user['email_limit']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning"><?= number_format($user['total_used']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $user['remaining'] > 0 ? 'success' : 'danger' ?>">
                                        <?= number_format($user['remaining']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= formatDate($user['created_at']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?= $user['id'] ?>&token=<?= generateCSRFToken() ?>" 
                                           class="btn btn-outline-danger confirm-action"
                                           data-message="Bạn có chắc chắn muốn xóa thành viên '<?= htmlspecialchars($user['username']) ?>'?">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus text-primary"></i>
                    Thêm thành viên mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addUsername" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="addUsername" name="username" required>
                        <div class="invalid-feedback">
                            Vui lòng nhập tên đăng nhập
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="addPassword" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="addPassword" name="password" 
                               minlength="<?= PASSWORD_MIN_LENGTH ?>" required>
                        <div class="invalid-feedback">
                            Mật khẩu phải có ít nhất <?= PASSWORD_MIN_LENGTH ?> ký tự
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="addEmailLimit" class="form-label">Giới hạn email</label>
                        <input type="number" class="form-control" id="addEmailLimit" name="email_limit" 
                               value="10" min="1" max="999999" required>
                        <div class="form-text">
                            Số lượng email tối đa mà thành viên có thể lấy
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Thêm thành viên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil text-primary"></i>
                    Chỉnh sửa thành viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                        <div class="invalid-feedback">
                            Vui lòng nhập tên đăng nhập
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                        <input type="password" class="form-control" id="editPassword" name="password" 
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <div class="invalid-feedback">
                            Mật khẩu phải có ít nhất <?= PASSWORD_MIN_LENGTH ?> ký tự
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editEmailLimit" class="form-label">Giới hạn email</label>
                        <input type="number" class="form-control" id="editEmailLimit" name="email_limit" 
                               min="1" max="999999" required>
                        <div class="form-text">
                            Số lượng email tối đa mà thành viên có thể lấy
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmailLimit').value = user.email_limit;
    document.getElementById('editPassword').value = '';
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>