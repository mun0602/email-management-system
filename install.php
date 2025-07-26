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
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            role VARCHAR(10) NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create apps table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS apps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create emails table  
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS emails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(100) NOT NULL,
            password VARCHAR(100) NOT NULL,
            app_id INTEGER,
            is_used BOOLEAN DEFAULT FALSE,
            used_by INTEGER NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (app_id) REFERENCES apps(id),
            FOREIGN KEY (used_by) REFERENCES users(id)
        )
    ");
    
    // Create user_limits table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            app_id INTEGER,
            daily_limit INTEGER DEFAULT 10,
            used_today INTEGER DEFAULT 0,
            last_reset DATE DEFAULT (DATE('now')),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (app_id) REFERENCES apps(id),
            UNIQUE(user_id, app_id)
        )
    ");
    
    // Create email_history table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            email_id INTEGER,
            app_id INTEGER,
            taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (email_id) REFERENCES emails(id),
            FOREIGN KEY (app_id) REFERENCES apps(id)
        )
    ");
    
    // Create shared_emails table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shared_emails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            share_code VARCHAR(32) UNIQUE NOT NULL,
            email_ids TEXT NOT NULL,
            password VARCHAR(255) NULL,
            created_by INTEGER,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");
    
    // Insert default apps
    $pdo->exec("INSERT OR IGNORE INTO apps (name) VALUES ('TanTan')");
    $pdo->exec("INSERT OR IGNORE INTO apps (name) VALUES ('HelloTalk')");
    
    // Insert default admin user (username: admin, password: admin123)
    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminExists == 0) {
        $pdo->exec("
            INSERT INTO users (username, password, full_name, role) 
            VALUES ('admin', '" . md5('admin123') . "', 'Administrator', 'admin')
        ");
    }
    
    // Insert sample user (username: user1, password: user123)
    $userExists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'user1'")->fetchColumn();
    if ($userExists == 0) {
        $pdo->exec("
            INSERT INTO users (username, password, full_name, role) 
            VALUES ('user1', '" . md5('user123') . "', 'Test User', 'user')
        ");
    }
    
    // Insert sample mails for testing
    $mailCount = $pdo->query("SELECT COUNT(*) FROM emails")->fetchColumn();
    if ($mailCount == 0) {
        $sampleMails = [
            ['test1@gmail.com', 'password123'],
            ['test2@gmail.com', 'password456'],
            ['test3@gmail.com', 'password789'],
            ['test4@yahoo.com', 'mypass123'],
            ['test5@hotmail.com', 'secret456']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO emails (email, password) VALUES (?, ?)");
        foreach ($sampleMails as $mail) {
            $stmt->execute($mail);
        }
    }
    
    // Set up user limits for all users and apps
    $users = $pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll();
    $apps = $pdo->query("SELECT id FROM apps")->fetchAll();
    
    foreach ($users as $user) {
        foreach ($apps as $app) {
            $pdo->prepare("
                INSERT OR IGNORE INTO user_limits (user_id, app_id, daily_limit, used_today)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $user['id'],
                $app['id'],
                25, // Default daily limit
                0
            ]);
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