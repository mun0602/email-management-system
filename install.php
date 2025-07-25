<?php
// Database installation script for Mail Management System

try {
    require_once __DIR__ . '/config/database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(10) NOT NULL DEFAULT 'user',
            mail_limit INTEGER NOT NULL DEFAULT 10,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create mails table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL
        )
    ");
    
    // Create mail_history table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mail_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            mail_id INTEGER NOT NULL,
            app_name VARCHAR(100) NOT NULL,
            taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (mail_id) REFERENCES mails (id)
        )
    ");
    
    // Create shared_mails table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shared_mails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            share_token VARCHAR(255) NOT NULL UNIQUE,
            mail_ids TEXT NOT NULL,
            password VARCHAR(255) DEFAULT NULL,
            created_by INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            accessed_count INTEGER NOT NULL DEFAULT 0,
            last_accessed TIMESTAMP NULL,
            FOREIGN KEY (created_by) REFERENCES users (id)
        )
    ");
    
    // Insert default admin user (username: admin, password: admin123)
    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminExists == 0) {
        $pdo->exec("
            INSERT INTO users (username, password, role, mail_limit) 
            VALUES ('admin', '" . md5('admin123') . "', 'admin', 999999)
        ");
    }
    
    // Insert sample user (username: user1, password: user123)
    $userExists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'user1'")->fetchColumn();
    if ($userExists == 0) {
        $pdo->exec("
            INSERT INTO users (username, password, role, mail_limit) 
            VALUES ('user1', '" . md5('user123') . "', 'user', 50)
        ");
    }
    
    // Insert sample mails for testing
    $mailCount = $pdo->query("SELECT COUNT(*) FROM mails")->fetchColumn();
    if ($mailCount == 0) {
        $sampleMails = [
            ['test1@gmail.com', 'password123'],
            ['test2@gmail.com', 'password456'],
            ['test3@gmail.com', 'password789'],
            ['test4@yahoo.com', 'mypass123'],
            ['test5@hotmail.com', 'secret456']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO mails (email, password) VALUES (?, ?)");
        foreach ($sampleMails as $mail) {
            $stmt->execute($mail);
        }
    }
    
    $message = "
    <div class='alert alert-success'>
        <h4><i class='bi bi-check-circle'></i> Cài đặt thành công!</h4>
        <p>Cơ sở dữ liệu đã được tạo và khởi tạo thành công.</p>
        <hr>
        <h6>Thông tin đăng nhập:</h6>
        <ul>
            <li><strong>Admin:</strong> username: <code>admin</code>, password: <code>admin123</code></li>
            <li><strong>User:</strong> username: <code>user1</code>, password: <code>user123</code></li>
        </ul>
        <a href='/login.php' class='btn btn-primary'>
            <i class='bi bi-box-arrow-in-right'></i> Đăng nhập ngay
        </a>
    </div>";
    
} catch (Exception $e) {
    $message = "
    <div class='alert alert-danger'>
        <h4><i class='bi bi-exclamation-triangle'></i> Lỗi cài đặt!</h4>
        <p>Có lỗi xảy ra trong quá trình cài đặt: " . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - Mail Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="bi bi-gear"></i> Cài đặt hệ thống quản lý Mail</h3>
                    </div>
                    <div class="card-body">
                        <?= $message ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>