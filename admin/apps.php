<?php
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Quản lý ứng dụng';
$db = new Database();
$message = '';

// Handle form submissions
if ($_POST) {
    if ($_POST['action'] === 'add_app') {
        $appName = trim($_POST['app_name'] ?? '');
        if (empty($appName)) {
            $message = '<div class="alert alert-danger">Vui lòng nhập tên ứng dụng!</div>';
        } else {
            // Check if app exists
            $existing = $db->fetch("SELECT id FROM apps WHERE name = ?", [$appName]);
            if ($existing) {
                $message = '<div class="alert alert-danger">Ứng dụng đã tồn tại!</div>';
            } else {
                try {
                    $db->query("INSERT INTO apps (name) VALUES (?)", [$appName]);
                    $appId = $db->lastInsertId();
                    
                    // Create default limits for all users
                    $users = $db->fetchAll("SELECT id FROM users WHERE role = 'user'");
                    foreach ($users as $user) {
                        $db->query(
                            "INSERT INTO user_limits (user_id, app_id, daily_limit, used_today) VALUES (?, ?, ?, ?)",
                            [$user['id'], $appId, 25, 0]
                        );
                    }
                    
                    $message = '<div class="alert alert-success">Thêm ứng dụng thành công!</div>';
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Có lỗi xảy ra: ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif ($_POST['action'] === 'delete_app') {
        $appId = (int)($_POST['app_id'] ?? 0);
        if ($appId) {
            // Check if app has associated emails
            $emailCount = $db->fetch("SELECT COUNT(*) as count FROM emails WHERE app_id = ?", [$appId])['count'];
            if ($emailCount > 0) {
                $message = '<div class="alert alert-danger">Không thể xóa ứng dụng có email liên kết!</div>';
            } else {
                try {
                    $db->query("DELETE FROM user_limits WHERE app_id = ?", [$appId]);
                    $db->query("DELETE FROM apps WHERE id = ?", [$appId]);
                    $message = '<div class="alert alert-success">Xóa ứng dụng thành công!</div>';
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Có lỗi xảy ra: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
}

$apps = getAllApps();
include '../includes/header.php';
?>

<?= $message ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Danh sách ứng dụng</h5>
            </div>
            <div class="card-body">
                <?php if (empty($apps)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-grid-3x3-gap text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">Chưa có ứng dụng nào</h5>
                        <p class="text-muted">Thêm ứng dụng đầu tiên để bắt đầu</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên ứng dụng</th>
                                    <th>Tổng email</th>
                                    <th>Email đã dùng</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apps as $app): ?>
                                <?php
                                    $emailStats = $db->fetch(
                                        "SELECT 
                                            COUNT(*) as total,
                                            SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used
                                         FROM emails WHERE app_id = ? OR (app_id IS NULL AND ? = (SELECT MIN(id) FROM apps))",
                                        [$app['id'], $app['id']]
                                    );
                                ?>
                                <tr>
                                    <td><?= $app['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($app['name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $emailStats['total'] ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= $emailStats['used'] ?? 0 ?></span>
                                    </td>
                                    <td><?= formatDate($app['created_at']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Bạn có chắc muốn xóa ứng dụng này?')">
                                            <input type="hidden" name="action" value="delete_app">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Thêm ứng dụng mới</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_app">
                    
                    <div class="mb-3">
                        <label for="app_name" class="form-label">Tên ứng dụng</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" 
                               placeholder="Ví dụ: Instagram, Facebook..." required>
                        <div class="form-text">Tên ứng dụng sẽ hiển thị cho người dùng khi chọn</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Thêm ứng dụng
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Thông tin</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Tổng ứng dụng:</label>
                    <div><span class="badge bg-primary"><?= count($apps) ?></span></div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted">Ứng dụng mặc định:</label>
                    <div>
                        <?php 
                        $defaultApps = ['TanTan', 'HelloTalk'];
                        foreach ($defaultApps as $defaultApp) {
                            $exists = false;
                            foreach ($apps as $app) {
                                if ($app['name'] === $defaultApp) {
                                    echo '<span class="badge bg-success me-1">' . $defaultApp . '</span>';
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists) {
                                echo '<span class="badge bg-secondary me-1">' . $defaultApp . ' (chưa có)</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="small text-muted">
                    <h6>Lưu ý:</h6>
                    <ul class="mb-0">
                        <li>Khi thêm ứng dụng mới, hệ thống sẽ tự động tạo giới hạn mặc định (25 email/ngày) cho tất cả người dùng</li>
                        <li>Không thể xóa ứng dụng đã có email liên kết</li>
                        <li>Tên ứng dụng phải là duy nhất</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Thống kê sử dụng theo ứng dụng</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($apps)): ?>
                    <div class="row">
                        <?php foreach ($apps as $app): ?>
                        <?php
                            $usage = $db->fetch(
                                "SELECT COUNT(*) as count FROM email_history WHERE app_id = ?",
                                [$app['id']]
                            )['count'];
                        ?>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <div class="display-6 text-primary"><?= $usage ?></div>
                                <div class="text-muted"><?= htmlspecialchars($app['name']) ?></div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?= $usage > 0 ? min(100, ($usage / max(1, array_sum(array_column($apps, 'id')))) * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <p class="text-muted">Chưa có dữ liệu thống kê</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>