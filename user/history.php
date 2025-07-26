<?php
require_once '../includes/functions.php';
requireLogin();

$db = new Database();
$user = getCurrentUser();

// Get user's mail history with filters
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$app_filter = $_GET['app'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = ["h.user_id = ?"];
$params = [$user['id']];

if (!empty($search)) {
    $where_conditions[] = "e.email LIKE ?";
    $params[] = "%$search%";
}

if (!empty($app_filter)) {
    $where_conditions[] = "a.name = ?";
    $params[] = $app_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(h.taken_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(h.taken_at) <= ?";
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$total_history = $db->fetch(
    "SELECT COUNT(*) as count 
     FROM email_history h 
     JOIN emails e ON h.email_id = e.id 
     LEFT JOIN apps a ON h.app_id = a.id
     $where_clause",
    $params
)['count'];

// Get user statistics
$totalUsed = getUserMailCount($user['id']);
$userAppsCount = count($userApps);

// Get user's current limits summary
$userLimits = $db->fetchAll(
    "SELECT ul.*, a.name as app_name 
     FROM user_limits ul 
     JOIN apps a ON ul.app_id = a.id 
     WHERE ul.user_id = ?",
    [$user['id']]
);

$totalRemaining = 0;
foreach ($userLimits as $limit) {
    // Reset daily usage if new day
    if ($limit['last_reset'] !== date('Y-m-d')) {
        $db->query(
            "UPDATE user_limits SET used_today = 0, last_reset = DATE('now') WHERE user_id = ? AND app_id = ?",
            [$user['id'], $limit['app_id']]
        );
        $limit['used_today'] = 0;
    }
    $totalRemaining += max(0, $limit['daily_limit'] - $limit['used_today']);
}

// Get history data
$history = $db->fetchAll(
    "SELECT h.*, e.email, e.password, a.name as app_name 
     FROM email_history h 
     JOIN emails e ON h.email_id = e.id 
     LEFT JOIN apps a ON h.app_id = a.id
     $where_clause 
     ORDER BY h.taken_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

$total_pages = ceil($total_history / $limit);

// Get apps used by user for filter
$userApps = $db->fetchAll(
    "SELECT DISTINCT a.name as app_name 
     FROM email_history h
     JOIN apps a ON h.app_id = a.id 
     WHERE h.user_id = ? 
     ORDER BY a.name",
    [$user['id']]
);

// Get statistics for charts
$appStats = $db->fetchAll(
    "SELECT a.name as app_name, COUNT(*) as count 
     FROM email_history h
     JOIN apps a ON h.app_id = a.id
     WHERE h.user_id = ? 
     GROUP BY a.name 
     ORDER BY count DESC",
    [$user['id']]
);

$monthlyStats = $db->fetchAll(
    "SELECT strftime('%Y-%m', taken_at) as month, COUNT(*) as count 
     FROM email_history 
     WHERE user_id = ? 
     GROUP BY month 
     ORDER BY month DESC 
     LIMIT 6",
    [$user['id']]
);

$pageTitle = getPageTitle('user_history');
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history"></i> Lịch sử cá nhân</h2>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number"><?= $totalUsed ?></div>
                <div class="stats-label">Tổng mail đã lấy</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $userAppsCount ?></div>
                <div class="stats-label">Ứng dụng đã dùng</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <div class="card-body text-center">
                <div class="stats-number"><?= $totalRemaining ?></div>
                <div class="stats-label">Mail còn lại hôm nay</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Phân bổ theo ứng dụng</h5>
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
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Hoạt động theo tháng</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       placeholder="Tìm kiếm email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="app">
                    <option value="">Tất cả ứng dụng</option>
                    <?php foreach ($userApps as $app): ?>
                    <option value="<?= htmlspecialchars($app['app_name']) ?>" 
                            <?= $app_filter === $app['app_name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($app['app_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" 
                       value="<?= htmlspecialchars($date_from) ?>" placeholder="Từ ngày">
            </div>
            
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" 
                       value="<?= htmlspecialchars($date_to) ?>" placeholder="Đến ngày">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Tìm kiếm
                </button>
            </div>
            
            <div class="col-md-1">
                <a href="history.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- History Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Lịch sử lấy mail (<?= number_format($total_history) ?> mail)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Mật khẩu</th>
                        <th>Ứng dụng</th>
                        <th>Ngày lấy</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Không tìm thấy lịch sử nào
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($history as $item): ?>
                        <tr class="searchable-row" data-date="<?= date('Y-m-d', strtotime($item['taken_at'])) ?>">
                            <td>
                                <input type="checkbox" class="mail-checkbox me-2" value="<?= $item['email_id'] ?>">
                                <code id="email-<?= $item['id'] ?>"><?= htmlspecialchars($item['email']) ?></code>
                            </td>
                            <td>
                                <code id="password-<?= $item['id'] ?>"><?= htmlspecialchars($item['password']) ?></code>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= htmlspecialchars($item['app_name']) ?></span>
                            </td>
                            <td><?= formatDate($item['taken_at']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-copy me-1" 
                                        data-target="#email-<?= $item['id'] ?>" title="Copy email">
                                    <i class="bi bi-clipboard"></i> Email
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-copy" 
                                        data-target="#password-<?= $item['id'] ?>" title="Copy password">
                                    <i class="bi bi-clipboard"></i> Pass
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php 
                $query_params = http_build_query(array_filter([
                    'search' => $search,
                    'app' => $app_filter,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ]));
                ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $query_params ? '&' . $query_params : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <!-- Share selected button -->
        <div class="text-center mt-3">
            <input type="checkbox" id="selectAll" class="me-2">
            <label for="selectAll">Chọn tất cả</label>
            <button type="button" class="btn btn-info ms-3" id="shareSelected" disabled data-bs-toggle="modal" data-bs-target="#shareModal">
                <i class="bi bi-share"></i> Chia sẻ đã chọn (<span class="badge bg-light text-dark">0</span>)
            </button>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chia sẻ Mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="shareForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="share_password" class="form-label">Mật khẩu bảo vệ (tùy chọn)</label>
                        <input type="password" class="form-control" id="share_password" name="password">
                        <div class="form-text">Để trống nếu không muốn đặt mật khẩu</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo link chia sẻ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Share Link Modal -->
<div class="modal fade" id="shareLinkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link chia sẻ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="shareLink" class="form-label">Link chia sẻ</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareLink" readonly>
                        <button type="button" class="btn btn-outline-primary" id="copyShareLink">
                            <i class="bi bi-clipboard"></i> Sao chép link
                        </button>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Link này có thể được chia sẻ công khai. Người khác có thể truy cập mà không cần đăng nhập.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Create charts
const appData = <?= json_encode(array_column($appStats, 'count')) ?>;
const appLabels = <?= json_encode(array_column($appStats, 'app_name')) ?>;

if (appData.length > 0) {
    createPieChart('appChart', appData, appLabels);
} else {
    document.getElementById('appChart').parentElement.innerHTML = 
        '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
}

const monthlyData = <?= json_encode(array_reverse(array_column($monthlyStats, 'count'))) ?>;
const monthlyLabels = <?= json_encode(array_reverse(array_column($monthlyStats, 'month'))) ?>;

if (monthlyData.length > 0) {
    createBarChart('monthlyChart', monthlyData, monthlyLabels);
} else {
    document.getElementById('monthlyChart').parentElement.innerHTML = 
        '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
}

// Add checkboxes to table rows
$(document).ready(function() {
    // Select all functionality
    $('#selectAll').on('change', function() {
        $('.mail-checkbox').prop('checked', $(this).prop('checked'));
        updateShareButtonState();
    });
    
    // Individual checkbox change
    $('.mail-checkbox').on('change', function() {
        updateShareButtonState();
        updateSelectAllState();
    });
    
    // Update share button state
    function updateShareButtonState() {
        const checkedBoxes = $('.mail-checkbox:checked').length;
        $('#shareSelected').prop('disabled', checkedBoxes === 0);
        $('#shareSelected .badge').text(checkedBoxes);
    }
    
    // Update select all checkbox state
    function updateSelectAllState() {
        const totalBoxes = $('.mail-checkbox').length;
        const checkedBoxes = $('.mail-checkbox:checked').length;
        $('#selectAll').prop('checked', totalBoxes === checkedBoxes && totalBoxes > 0);
    }
    
    // Share form submission
    $('#shareForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedEmails = [];
        $('.mail-checkbox:checked').each(function() {
            selectedEmails.push($(this).val());
        });
        
        if (selectedEmails.length === 0) {
            alert('Vui lòng chọn ít nhất một email để chia sẻ!');
            return;
        }
        
        const formData = {
            email_ids: selectedEmails,
            password: $('#share_password').val(),
            action: 'create_share'
        };
        
        $.post('/api/sharing.php', JSON.stringify(formData), function(response) {
            if (response.success) {
                $('#shareLink').val(response.share_url);
                $('#shareLinkModal').modal('show');
                $('#shareModal').modal('hide');
                
                // Reset form
                $('#share_password').val('');
                $('.mail-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
                updateShareButtonState();
            } else {
                alert('Có lỗi xảy ra: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Có lỗi xảy ra khi tạo link chia sẻ');
        });
    });
    
    // Copy share link
    $('#copyShareLink').on('click', function() {
        const shareLink = $('#shareLink')[0];
        shareLink.select();
        document.execCommand('copy');
        
        $(this).html('<i class="bi bi-check"></i> Đã sao chép');
        setTimeout(() => {
            $(this).html('<i class="bi bi-clipboard"></i> Sao chép link');
        }, 2000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>