<?php
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = getPageTitle('admin_dashboard');
$mailStats = getMailStats();
$db = new Database();

// Get user statistics
$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$mailsByApp = getMailsByApp();
$recentActivity = $db->fetchAll(
    "SELECT h.*, u.username, m.email 
     FROM mail_history h 
     JOIN users u ON h.user_id = u.id 
     JOIN mails m ON h.mail_id = m.id 
     ORDER BY h.taken_at DESC 
     LIMIT 10"
);

include '../includes/header.php';
?>

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
        <div class="card stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $mailStats['used'] ?></div>
                <div class="stats-label">Mail đã sử dụng</div>
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
</div>

<div class="row">
    <div class="col-md-6 mb-4">
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
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Hoạt động gần đây</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center py-3">Chưa có hoạt động nào</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($activity['username']) ?></strong>
                                    đã lấy mail <code><?= htmlspecialchars($activity['email']) ?></code>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-app"></i> <?= htmlspecialchars($activity['app_name']) ?>
                                    </small>
                                </div>
                                <small class="text-muted"><?= formatDate($activity['taken_at']) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-speedometer2"></i> Quản lý nhanh</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="/admin/members.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-people"></i><br>
                            Quản lý thành viên
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/mails.php" class="btn btn-outline-success w-100">
                            <i class="bi bi-envelope-plus"></i><br>
                            Thêm Mail mới
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/statistics.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-graph-up"></i><br>
                            Xem thống kê
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/user/" class="btn btn-outline-warning w-100">
                            <i class="bi bi-download"></i><br>
                            Lấy Mail
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Create app distribution chart
const appData = <?= json_encode(array_column($mailsByApp, 'count')) ?>;
const appLabels = <?= json_encode(array_column($mailsByApp, 'app_name')) ?>;

if (appData.length > 0) {
    createPieChart('appChart', appData, appLabels);
} else {
    document.getElementById('appChart').parentElement.innerHTML = 
        '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
}
</script>

<?php include '../includes/footer.php'; ?>