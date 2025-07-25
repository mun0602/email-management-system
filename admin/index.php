<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Dashboard Admin - ' . APP_NAME;
$currentPage = 'dashboard';

// Lấy thống kê tổng quan
$stats = getTotalStats();
$appStats = getAppStats();

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                Dashboard Admin
            </h1>
            <div class="text-muted">
                <i class="bi bi-clock"></i>
                Cập nhật: <?= date('d/m/Y H:i:s') ?>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-left-primary h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Tổng thành viên
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_users">
                            <?= number_format($stats['total_users']) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-primary text-white">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-left-success h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Tổng email
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_emails">
                            <?= number_format($stats['total_emails']) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-success text-white">
                            <i class="bi bi-envelope"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-left-info h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Email khả dụng
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="available_emails">
                            <?= number_format($stats['available_emails']) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-info text-white">
                            <i class="bi bi-envelope-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-left-warning h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Lượt sử dụng
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_usage">
                            <?= number_format($stats['total_usage']) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-warning text-white">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart: Thống kê theo ứng dụng -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-bar-chart"></i>
                    Thống kê sử dụng theo ứng dụng
                </h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                        <div class="dropdown-header">Tuỳ chọn:</div>
                        <a class="dropdown-item" href="/admin/statistics.php">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 300px;">
                    <canvas id="appStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-clock-history"></i>
                    Hoạt động gần đây
                </h6>
            </div>
            <div class="card-body">
                <?php
                $recentActivity = $database->fetchAll(
                    "SELECT u.username, eu.app_name, eu.used_at 
                     FROM email_usage eu 
                     JOIN users u ON eu.user_id = u.id 
                     ORDER BY eu.used_at DESC 
                     LIMIT 8"
                );
                ?>
                
                <?php if (empty($recentActivity)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có hoạt động nào</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="small fw-bold">
                                            <?= htmlspecialchars($activity['username']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            Sử dụng email cho <?= htmlspecialchars($activity['app_name']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?= timeAgo($activity['used_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-lightning"></i>
                    Thao tác nhanh
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="/admin/users.php" class="btn btn-primary btn-block">
                            <i class="bi bi-person-plus"></i>
                            Thêm thành viên
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/emails.php" class="btn btn-success btn-block">
                            <i class="bi bi-envelope-plus"></i>
                            Thêm email
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/statistics.php" class="btn btn-info btn-block">
                            <i class="bi bi-graph-up"></i>
                            Xem thống kê
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <button type="button" class="btn btn-warning btn-block" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise"></i>
                            Làm mới
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart: App Statistics
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('appStatsChart').getContext('2d');
    const appData = <?= json_encode($appStats) ?>;
    
    if (appData.length > 0) {
        const labels = appData.map(item => item.app_name);
        const data = appData.map(item => item.count);
        const colors = [
            '#007bff', '#28a745', '#ffc107', '#dc3545', 
            '#17a2b8', '#6f42c1', '#fd7e14', '#20c997'
        ];
        
        app.createChart(ctx, 'bar', {
            labels: labels,
            datasets: [{
                label: 'Số lượt sử dụng',
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderColor: colors.slice(0, data.length),
                borderWidth: 1
            }]
        });
    } else {
        ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-bar-chart fs-1"></i><p class="mt-2">Chưa có dữ liệu thống kê</p></div>';
    }
});

function refreshDashboard() {
    location.reload();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>