<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = 'Dashboard Thành viên - ' . APP_NAME;
$currentPage = 'dashboard';

$user = getCurrentUser();
$userStats = getUserEmailStats($user['id']);

// Lấy thống kê sử dụng gần đây
$recentUsage = $database->fetchAll(
    "SELECT e.email, eu.app_name, eu.used_at 
     FROM email_usage eu
     JOIN emails e ON eu.email_id = e.id 
     WHERE eu.user_id = ? 
     ORDER BY eu.used_at DESC 
     LIMIT 5",
    [$user['id']]
);

// Thống kê theo ứng dụng của user
$appUsage = $database->fetchAll(
    "SELECT app_name, COUNT(*) as count 
     FROM email_usage 
     WHERE user_id = ? 
     GROUP BY app_name 
     ORDER BY count DESC",
    [$user['id']]
);

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-house text-primary"></i>
                Chào mừng, <?= htmlspecialchars($user['username']) ?>!
            </h1>
            <div class="text-muted">
                <i class="bi bi-clock"></i>
                <?= date('d/m/Y H:i:s') ?>
            </div>
        </div>
    </div>
</div>

<!-- User Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card text-center h-100">
            <div class="card-body">
                <div class="stat-icon bg-info text-white mx-auto mb-3">
                    <i class="bi bi-envelope-check"></i>
                </div>
                <h3 class="mb-1"><?= number_format($userStats['limit']) ?></h3>
                <p class="text-muted mb-0">Giới hạn email</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center h-100">
            <div class="card-body">
                <div class="stat-icon bg-warning text-white mx-auto mb-3">
                    <i class="bi bi-envelope-x"></i>
                </div>
                <h3 class="mb-1"><?= number_format($userStats['used']) ?></h3>
                <p class="text-muted mb-0">Đã sử dụng</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center h-100">
            <div class="card-body">
                <div class="stat-icon bg-<?= $userStats['remaining'] > 0 ? 'success' : 'danger' ?> text-white mx-auto mb-3">
                    <i class="bi bi-envelope"></i>
                </div>
                <h3 class="mb-1"><?= number_format($userStats['remaining']) ?></h3>
                <p class="text-muted mb-0">Còn lại</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Usage Progress -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-graph-up"></i>
                    Tiến độ sử dụng
                </h6>
            </div>
            <div class="card-body">
                <?php 
                $percentage = $userStats['limit'] > 0 ? ($userStats['used'] / $userStats['limit'] * 100) : 0;
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Đã sử dụng: <?= number_format($userStats['used']) ?> / <?= number_format($userStats['limit']) ?></span>
                        <span><?= number_format($percentage, 1) ?>%</span>
                    </div>
                    <div class="progress mt-2" style="height: 12px;">
                        <div class="progress-bar <?= $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success') ?>" 
                             role="progressbar" style="width: <?= $percentage ?>%">
                        </div>
                    </div>
                </div>
                
                <?php if ($userStats['remaining'] > 0): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        Bạn còn lại <strong><?= number_format($userStats['remaining']) ?></strong> email có thể sử dụng.
                    </div>
                    <div class="text-center">
                        <a href="/member/get-email.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-download"></i>
                            Lấy Email ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Bạn đã sử dụng hết quota email. Vui lòng liên hệ admin để tăng giới hạn.
                    </div>
                <?php endif; ?>
                
                <!-- App Usage Chart -->
                <?php if (!empty($appUsage)): ?>
                    <hr class="my-4">
                    <h6 class="font-weight-bold text-secondary">Thống kê theo ứng dụng</h6>
                    <div style="height: 200px;">
                        <canvas id="userAppChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-clock-history"></i>
                    Hoạt động gần đây
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($recentUsage)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có hoạt động nào</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentUsage as $usage): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="small fw-bold">
                                            <?= htmlspecialchars($usage['email']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            Ứng dụng: <?= htmlspecialchars($usage['app_name']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?= timeAgo($usage['used_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="/member/history.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i>
                            Xem tất cả
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-lightning"></i>
                    Thao tác nhanh
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($userStats['remaining'] > 0): ?>
                        <a href="/member/get-email.php" class="btn btn-primary">
                            <i class="bi bi-download"></i>
                            Lấy Email
                        </a>
                    <?php endif; ?>
                    <a href="/member/history.php" class="btn btn-outline-secondary">
                        <i class="bi bi-clock-history"></i>
                        Lịch sử sử dụng
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                        Làm mới
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tips and Help -->
<div class="row">
    <div class="col-12">
        <div class="card bg-light border-0">
            <div class="card-body">
                <h6 class="card-title text-primary">
                    <i class="bi bi-lightbulb"></i>
                    Mẹo sử dụng
                </h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-1-circle text-primary me-2 mt-1"></i>
                            <div>
                                <strong>Chọn ứng dụng phù hợp</strong>
                                <p class="small text-muted mb-0">Chọn đúng ứng dụng bạn muốn sử dụng email để thống kê chính xác.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-2-circle text-primary me-2 mt-1"></i>
                            <div>
                                <strong>Sao chép nhanh chóng</strong>
                                <p class="small text-muted mb-0">Sử dụng nút copy để sao chép email và password một cách nhanh chóng.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-3-circle text-primary me-2 mt-1"></i>
                            <div>
                                <strong>Quản lý quota</strong>
                                <p class="small text-muted mb-0">Theo dõi số lượng email còn lại để sử dụng hiệu quả.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($appUsage)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('userAppChart').getContext('2d');
    const appData = <?= json_encode($appUsage) ?>;
    
    const labels = appData.map(item => item.app_name);
    const data = appData.map(item => item.count);
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1'];
    
    app.createChart(ctx, 'doughnut', {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors.slice(0, data.length),
            borderColor: '#fff',
            borderWidth: 2
        }]
    }, {
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>