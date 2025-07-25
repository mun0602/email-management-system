<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = 'Lịch sử sử dụng - ' . APP_NAME;
$currentPage = 'history';

$user = getCurrentUser();

// Filters
$appFilter = sanitizeInput($_GET['app'] ?? '');
$dateFilter = sanitizeInput($_GET['date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query conditions
$whereConditions = ["eu.user_id = ?"];
$params = [$user['id']];

if (!empty($appFilter)) {
    $whereConditions[] = "eu.app_name = ?";
    $params[] = $appFilter;
}

if (!empty($dateFilter)) {
    $whereConditions[] = "DATE(eu.used_at) = ?";
    $params[] = $dateFilter;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$totalCount = $database->fetchOne(
    "SELECT COUNT(*) as count 
     FROM email_usage eu 
     WHERE $whereClause",
    $params
)['count'];

$totalPages = ceil($totalCount / $limit);

// Get history data
$history = $database->fetchAll(
    "SELECT eu.*, e.email, e.password 
     FROM email_usage eu
     JOIN emails e ON eu.email_id = e.id 
     WHERE $whereClause
     ORDER BY eu.used_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

// Get user apps for filter
$userApps = $database->fetchAll(
    "SELECT DISTINCT app_name 
     FROM email_usage 
     WHERE user_id = ? 
     ORDER BY app_name",
    [$user['id']]
);

// Get usage statistics for charts
$dailyUsage = $database->fetchAll(
    "SELECT DATE(used_at) as date, COUNT(*) as count 
     FROM email_usage 
     WHERE user_id = ? AND used_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(used_at) 
     ORDER BY date ASC",
    [$user['id']]
);

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
                <i class="bi bi-clock-history text-primary"></i>
                Lịch sử sử dụng
            </h1>
            <div class="text-muted">
                Tổng: <strong><?= number_format($totalCount) ?></strong> lượt sử dụng
            </div>
        </div>
    </div>
</div>

<!-- Statistics Charts -->
<?php if (!empty($dailyUsage) || !empty($appUsage)): ?>
    <div class="row mb-4">
        <!-- Daily Usage Chart -->
        <?php if (!empty($dailyUsage)): ?>
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-graph-up"></i>
                            Thống kê sử dụng 30 ngày gần đây
                        </h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 250px;">
                            <canvas id="dailyUsageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- App Usage Distribution -->
        <?php if (!empty($appUsage)): ?>
            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-pie-chart"></i>
                            Phân bố theo ứng dụng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 200px;">
                            <canvas id="appUsageChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <?php foreach ($appUsage as $app): ?>
                                <div class="d-flex justify-content-between">
                                    <span><?= htmlspecialchars($app['app_name']) ?></span>
                                    <strong><?= number_format($app['count']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-funnel"></i>
            Bộ lọc
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4">
                <label for="app" class="form-label">Ứng dụng</label>
                <select class="form-select" id="app" name="app">
                    <option value="">Tất cả ứng dụng</option>
                    <?php foreach ($userApps as $app): ?>
                        <option value="<?= htmlspecialchars($app['app_name']) ?>" 
                                <?= $appFilter === $app['app_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($app['app_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="date" class="form-label">Ngày</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
            </div>
            <div class="col-md-4">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Lọc
                    </button>
                    <a href="/member/history.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i>
                        Xóa bộ lọc
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- History Table -->
<div class="card shadow">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="m-0 font-weight-bold text-primary">
                    Lịch sử chi tiết
                    <?php if ($appFilter || $dateFilter): ?>
                        <small class="text-muted">
                            (Đã lọc: 
                            <?php if ($appFilter): ?>
                                <?= htmlspecialchars($appFilter) ?>
                            <?php endif; ?>
                            <?php if ($dateFilter): ?>
                                <?= formatDate($dateFilter, 'd/m/Y') ?>
                            <?php endif; ?>)
                        </small>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportHistory()">
                    <i class="bi bi-download"></i>
                    Xuất CSV
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Ứng dụng</th>
                        <th>Thời gian sử dụng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-clock-history fs-1 text-muted"></i>
                                <p class="text-muted mt-2">
                                    <?php if ($appFilter || $dateFilter): ?>
                                        Không tìm thấy lịch sử sử dụng với bộ lọc này
                                    <?php else: ?>
                                        Bạn chưa sử dụng email nào
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $index => $item): ?>
                            <tr>
                                <td><?= $offset + $index + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?= htmlspecialchars($item['email']) ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                                data-copy="<?= htmlspecialchars($item['email']) ?>" 
                                                title="Sao chép email">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <code class="me-2"><?= htmlspecialchars($item['password']) ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                                data-copy="<?= htmlspecialchars($item['password']) ?>" 
                                                title="Sao chép password">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($item['app_name']) ?></span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= formatDate($item['used_at'], 'd/m/Y') ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?= formatDate($item['used_at'], 'H:i:s') ?>
                                            (<?= timeAgo($item['used_at']) ?>)
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary copy-btn" 
                                                data-copy="<?= htmlspecialchars($item['email']) ?>|<?= htmlspecialchars($item['password']) ?>" 
                                                title="Sao chép email|password">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $appFilter ? '&app=' . urlencode($appFilter) : '' ?><?= $dateFilter ? '&date=' . $dateFilter : '' ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $appFilter ? '&app=' . urlencode($appFilter) : '' ?><?= $dateFilter ? '&date=' . $dateFilter : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $appFilter ? '&app=' . urlencode($appFilter) : '' ?><?= $dateFilter ? '&date=' . $dateFilter : '' ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Hiển thị <?= $offset + 1 ?> - <?= min($offset + $limit, $totalCount) ?> 
                        trong tổng số <?= number_format($totalCount) ?> mục
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Usage Chart
    <?php if (!empty($dailyUsage)): ?>
    const dailyCtx = document.getElementById('dailyUsageChart').getContext('2d');
    const dailyData = <?= json_encode($dailyUsage) ?>;
    
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
    <?php endif; ?>
    
    // App Usage Chart
    <?php if (!empty($appUsage)): ?>
    const appCtx = document.getElementById('appUsageChart').getContext('2d');
    const appData = <?= json_encode($appUsage) ?>;
    
    const appLabels = appData.map(item => item.app_name);
    const appValues = appData.map(item => item.count);
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1'];
    
    app.createChart(appCtx, 'doughnut', {
        labels: appLabels,
        datasets: [{
            data: appValues,
            backgroundColor: colors.slice(0, appValues.length),
            borderColor: '#fff',
            borderWidth: 2
        }]
    });
    <?php endif; ?>
});

function exportHistory() {
    // Build CSV content
    let csv = 'STT,Email,Password,Ứng dụng,Thời gian sử dụng\n';
    
    <?php foreach ($history as $index => $item): ?>
        csv += '<?= $offset + $index + 1 ?>,<?= addslashes($item['email']) ?>,<?= addslashes($item['password']) ?>,<?= addslashes($item['app_name']) ?>,<?= formatDate($item['used_at'], 'd/m/Y H:i:s') ?>\n';
    <?php endforeach; ?>
    
    // Download CSV
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'lich_su_su_dung_<?= date('Y-m-d') ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Đã tải xuống lịch sử sử dụng', 'success');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>