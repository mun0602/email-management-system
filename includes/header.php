<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Hệ thống quản lý Mail' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js (fallback for basic charts) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-envelope-check"></i>
                Hệ thống quản lý Mail
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Quản trị
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/">
                                <i class="bi bi-speedometer2"></i> Bảng điều khiển
                            </a></li>
                            <li><a class="dropdown-item" href="/admin/members.php">
                                <i class="bi bi-people"></i> Quản lý thành viên
                            </a></li>
                            <li><a class="dropdown-item" href="/admin/mails.php">
                                <i class="bi bi-envelope-plus"></i> Quản lý Mail
                            </a></li>
                            <li><a class="dropdown-item" href="/admin/statistics.php">
                                <i class="bi bi-graph-up"></i> Thống kê
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/user/">
                            <i class="bi bi-download"></i> Lấy Mail
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/user/history.php">
                            <i class="bi bi-clock-history"></i> Lịch sử
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= $_SESSION['username'] ?>
                            <?= isAdmin() ? '<span class="badge bg-warning">Admin</span>' : '<span class="badge bg-info">User</span>' ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="<?= isLoggedIn() ? 'py-4' : '' ?>">
        <div class="container">