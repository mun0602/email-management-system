<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Thống kê - ' . APP_NAME;
$currentPage = 'statistics';

// Lấy thống kê tổng quan
$stats = getTotalStats();
$appStats = getAppStats();

// Thống kê theo thành viên
$userStats = $database->fetchAll(
    "SELECT u.username, u.email_limit, 
            COUNT(eu.id) as total_used,
            (u.email_limit - COUNT(eu.id)) as remaining
     FROM users u 
     LEFT JOIN email_usage eu ON u.id = eu.user_id 
     WHERE u.role = 'member'
     GROUP BY u.id 
     ORDER BY total_used DESC"
);

// Thống kê theo ngày (30 ngày gần đây)
$dailyStats = $database->fetchAll(
    "SELECT DATE(used_at) as date, COUNT(*) as count 
     FROM email_usage 
     WHERE used_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(used_at) 
     ORDER BY date ASC"
);

// Thống kê theo giờ trong ngày
$hourlyStats = $database->fetchAll(
    "SELECT HOUR(used_at) as hour, COUNT(*) as count 
     FROM email_usage 
     WHERE used_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY HOUR(used_at) 
     ORDER BY hour ASC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-graph-up text-primary"></i>
                Thống kê chi tiết
            </h1>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" onclick="exportStats()">
                    <i class="bi bi-download"></i>
                    Xuất báo cáo
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Làm mới
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Overview Statistics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-left-primary h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Tổng thành viên
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
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
                            Email khả dụng
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($stats['available_emails']) ?>
                        </div>
                        <div class="progress progress-sm mt-2">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= $stats['total_emails'] > 0 ? ($stats['available_emails'] / $stats['total_emails'] * 100) : 0 ?>%">
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-success text-white">
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
                            Email đã dùng
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($stats['used_emails']) ?>
                        </div>
                        <div class="progress progress-sm mt-2">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?= $stats['total_emails'] > 0 ? ($stats['used_emails'] / $stats['total_emails'] * 100) : 0 ?>%">
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-warning text-white">
                            <i class="bi bi-envelope-x"></i>
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
                            Tổng lượt sử dụng
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($stats['total_usage']) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-icon bg-info text-white">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Usage Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-graph-up"></i>
                    Thống kê sử dụng theo ngày (30 ngày gần đây)
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 320px;">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- App Usage Distribution -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-pie-chart"></i>
                    Phân bố theo ứng dụng
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2" style="height: 245px;">
                    <canvas id="appPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Hourly Usage Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-clock"></i>
                    Thống kê sử dụng theo giờ (7 ngày gần đây)
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 320px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-award"></i>
                    Thành viên tích cực nhất
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($userStats)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-people fs-1"></i>
                        <p class="mt-2">Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($userStats, 0, 5) as $index => $user): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="fw-bold">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            Đã sử dụng: <?= number_format($user['total_used']) ?> / <?= number_format($user['email_limit']) ?>
                                        </div>
                                        <div class="progress progress-sm mt-1">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= $user['email_limit'] > 0 ? ($user['total_used'] / $user['email_limit'] * 100) : 0 ?>%">
                                            </div>
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

<!-- Detailed Statistics Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-table"></i>
                    Thống kê chi tiết theo thành viên
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Thành viên</th>
                                <th>Giới hạn</th>
                                <th>Đã sử dụng</th>
                                <th>Còn lại</th>
                                <th>Tỷ lệ sử dụng</th>
                                <th>Tiến độ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($userStats)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-people fs-1 text-muted"></i>
                                        <p class="text-muted mt-2">Chưa có thành viên nào</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($userStats as $user): ?>
                                    <tr>
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
                                            <?php 
                                            $percentage = $user['email_limit'] > 0 ? ($user['total_used'] / $user['email_limit'] * 100) : 0;
                                            ?>
                                            <?= number_format($percentage, 1) ?>%
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar <?= $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success') ?>" 
                                                     role="progressbar" style="width: <?= $percentage ?>%">
                                                </div>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Usage Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyData = <?= json_encode($dailyStats) ?>;
    
    if (dailyData.length > 0) {
        const dailyLabels = dailyData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('vi-VN');
        });
        const dailyValues = dailyData.map(item => item.count);
        
        app.createChart(dailyCtx, 'line', {
            labels: dailyLabels,
            datasets: [{
                label: 'Số lượt sử dụng',
                data: dailyValues,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4
            }]
        });
    } else {
        dailyCtx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-graph-up fs-1"></i><p class="mt-2">Chưa có dữ liệu</p></div>';
    }
    
    // App Pie Chart
    const appCtx = document.getElementById('appPieChart').getContext('2d');
    const appData = <?= json_encode($appStats) ?>;
    
    if (appData.length > 0) {
        const appLabels = appData.map(item => item.app_name);
        const appValues = appData.map(item => item.count);
        const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1'];
        
        app.createChart(appCtx, 'doughnut', {
            labels: appLabels,
            datasets: [{
                data: appValues,
                backgroundColor: colors.slice(0, appValues.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        });
    } else {
        appCtx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-pie-chart fs-1"></i><p class="mt-2">Chưa có dữ liệu</p></div>';
    }
    
    // Hourly Usage Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hourlyData = <?= json_encode($hourlyStats) ?>;
    
    // Tạo dữ liệu đầy đủ 24 giờ
    const hourlyValues = new Array(24).fill(0);
    hourlyData.forEach(item => {
        hourlyValues[item.hour] = item.count;
    });
    
    const hourlyLabels = Array.from({length: 24}, (_, i) => i + ':00');
    
    app.createChart(hourlyCtx, 'bar', {
        labels: hourlyLabels,
        datasets: [{
            label: 'Số lượt sử dụng',
            data: hourlyValues,
            backgroundColor: 'rgba(0, 123, 255, 0.6)',
            borderColor: '#007bff',
            borderWidth: 1
        }]
    });
});

function exportStats() {
    // Tạo và tải xuống CSV
    let csv = 'Thành viên,Giới hạn,Đã sử dụng,Còn lại,Tỷ lệ sử dụng\n';
    
    <?php foreach ($userStats as $user): ?>
        csv += '<?= htmlspecialchars($user['username']) ?>,<?= $user['email_limit'] ?>,<?= $user['total_used'] ?>,<?= $user['remaining'] ?>,<?= number_format($user['email_limit'] > 0 ? ($user['total_used'] / $user['email_limit'] * 100) : 0, 1) ?>%\n';
    <?php endforeach; ?>
    
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'thong_ke_' + new Date().toISOString().slice(0, 10) + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Đã tải xuống báo cáo thống kê', 'success');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>