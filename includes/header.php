<?php
if (!isset($pageTitle)) $pageTitle = APP_NAME;
if (!isset($currentPage)) $currentPage = '';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.umd.js" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= isAdmin() ? '/admin' : '/member' ?>/index.php">
                <i class="bi bi-envelope-fill"></i>
                <?= APP_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" 
                               href="/admin/index.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" 
                               href="/admin/users.php">
                                <i class="bi bi-people"></i> Thành viên
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'emails' ? 'active' : '' ?>" 
                               href="/admin/emails.php">
                                <i class="bi bi-envelope"></i> Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'statistics' ? 'active' : '' ?>" 
                               href="/admin/statistics.php">
                                <i class="bi bi-graph-up"></i> Thống kê
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" 
                               href="/member/index.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'get-email' ? 'active' : '' ?>" 
                               href="/member/get-email.php">
                                <i class="bi bi-download"></i> Lấy Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'history' ? 'active' : '' ?>" 
                               href="/member/history.php">
                                <i class="bi bi-clock-history"></i> Lịch sử
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($user['username']) ?>
                            <span class="badge bg-secondary ms-1">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="container mt-4">
        <!-- Toast container -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            <div id="toastMessage" class="toast" role="alert">
                <div class="toast-header">
                    <i class="bi bi-info-circle text-primary me-2"></i>
                    <strong class="me-auto">Thông báo</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body"></div>
            </div>
        </div>