<?php
require_once '../includes/functions.php';
requireAdmin();

$db = new Database();

// Get comprehensive statistics
$mailStats = getMailStats();

// User statistics
$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$activeUsers = $db->fetch(
    "SELECT COUNT(DISTINCT user_id) as count FROM mail_history WHERE taken_at >= datetime('now', '-30 days')"
)['count'];

// Mail usage by app
$mailsByApp = getMailsByApp();

// Top users by mail usage
$topUsers = $db->fetchAll(
    "SELECT u.username, COUNT(h.id) as mail_count, u.mail_limit,
            MAX(h.taken_at) as last_activity
     FROM users u 
     LEFT JOIN mail_history h ON u.id = h.user_id 
     WHERE u.role = 'user'
     GROUP BY u.id 
     ORDER BY mail_count DESC 
     LIMIT 10"
);

// Daily mail usage for the last 7 days
$dailyStats = $db->fetchAll(
    "SELECT DATE(taken_at) as date, COUNT(*) as count 
     FROM mail_history 
     WHERE taken_at >= datetime('now', '-7 days')
     GROUP BY DATE(taken_at) 
     ORDER BY date DESC"
);

// Monthly trends for the last 6 months
$monthlyStats = $db->fetchAll(
    "SELECT strftime('%Y-%m', taken_at) as month, COUNT(*) as count 
     FROM mail_history 
     WHERE taken_at >= datetime('now', '-6 months')
     GROUP BY month 
     ORDER BY month DESC"
);

// Shared mail statistics
$shareStats = $db->fetch(
    "SELECT COUNT(*) as total_shares, 
            SUM(accessed_count) as total_accesses
     FROM shared_mails"
);

$popularShares = $db->fetchAll(
    "SELECT s.share_token, s.accessed_count, s.created_at, u.username,
            LENGTH(s.mail_ids) - LENGTH(REPLACE(s.mail_ids, ',', '')) + 1 as mail_count
     FROM shared_mails s
     JOIN users u ON s.created_by = u.id
     ORDER BY s.accessed_count DESC
     LIMIT 5"
);

$pageTitle = getPageTitle('admin_statistics');
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> Thống kê tổng quan</h2>
    <div>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> In báo cáo
        </button>
    </div>
</div>

<!-- Overview Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number"><?= $mailStats['total'] ?></div>
                <div class="stats-label">Tổng số Mail</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $mailStats['available'] ?></div>
                <div class="stats-label">Mail khả dụng</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $totalUsers ?></div>
                <div class="stats-label">Tổng thành viên</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $activeUsers ?></div>
                <div class="stats-label">Hoạt động 30 ngày</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Phân bổ Mail theo ứng dụng</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="appChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Hoạt động 7 ngày qua</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Xu hướng 6 tháng</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Top 10 người dùng</h5>
            </div>
            <div class="card-body">
                <?php if (empty($topUsers)): ?>
                    <p class="text-muted text-center py-3">Chưa có dữ liệu</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($topUsers, 0, 5) as $index => $user): ?>
                        <div class="list-group-item border-0 px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    Hạn mức: <?= $user['mail_limit'] ?> • 
                                    <?= $user['last_activity'] ? formatDate($user['last_activity']) : 'Chưa hoạt động' ?>
                                </small>
                            </div>
                            <span class="badge bg-primary fs-6"><?= $user['mail_count'] ?> mail</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tables -->
<div class="row">
    <!-- Top Users Table -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Bảng xếp hạng thành viên</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên</th>
                                <th>Đã dùng</th>
                                <th>Hạn mức</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topUsers as $index => $user): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= $user['mail_count'] ?></td>
                                <td><?= $user['mail_limit'] ?></td>
                                <td>
                                    <?php 
                                    $percentage = $user['mail_limit'] > 0 ? round(($user['mail_count'] / $user['mail_limit']) * 100, 1) : 0;
                                    ?>
                                    <span class="badge <?= $percentage >= 80 ? 'bg-danger' : ($percentage >= 50 ? 'bg-warning' : 'bg-success') ?>">
                                        <?= $percentage ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Share Statistics -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-share"></i> Thống kê chia sẻ</h5>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <div class="h4 text-primary"><?= $shareStats['total_shares'] ?? 0 ?></div>
                        <small class="text-muted">Link chia sẻ</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-success"><?= $shareStats['total_accesses'] ?? 0 ?></div>
                        <small class="text-muted">Lượt truy cập</small>
                    </div>
                </div>
                
                <?php if (!empty($popularShares)): ?>
                <h6>Link phổ biến:</h6>
                <div class="list-group list-group-flush">
                    <?php foreach ($popularShares as $share): ?>
                    <div class="list-group-item border-0 px-0 small">
                        <div class="d-flex justify-content-between">
                            <span>
                                <?= htmlspecialchars($share['username']) ?> 
                                (<?= $share['mail_count'] ?> mail)
                            </span>
                            <span class="badge bg-info"><?= $share['accessed_count'] ?> lượt</span>
                        </div>
                        <small class="text-muted"><?= formatDate($share['created_at']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Chưa có chia sẻ nào</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// App distribution chart
const appData = <?= json_encode(array_column($mailsByApp, 'count')) ?>;
const appLabels = <?= json_encode(array_column($mailsByApp, 'app_name')) ?>;

if (appData.length > 0) {
    createPieChart('appChart', appData, appLabels);
} else {
    document.getElementById('appChart').parentElement.innerHTML = 
        '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
}

// Daily activity chart
const dailyData = <?= json_encode(array_reverse(array_column($dailyStats, 'count'))) ?>;
const dailyLabels = <?= json_encode(array_reverse(array_map(function($item) { 
    return date('d/m', strtotime($item['date'])); 
}, $dailyStats))) ?>;

if (dailyData.length > 0) {
    createBarChart('dailyChart', dailyData, dailyLabels);
} else {
    document.getElementById('dailyChart').parentElement.innerHTML = 
        '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
}

// Monthly trend chart
const monthlyData = <?= json_encode(array_reverse(array_column($monthlyStats, 'count'))) ?>;
const monthlyLabels = <?= json_encode(array_reverse(array_column($monthlyStats, 'month'))) ?>;

if (monthlyData.length > 0) {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Mail đã lấy',
                data: monthlyData,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
} else {
    document.getElementById('monthlyChart').parentElement.innerHTML = 
        '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
}
</script>

<?php include '../includes/footer.php'; ?>