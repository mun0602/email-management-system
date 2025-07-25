<?php
/**
 * Setup Script for Email Management System
 * Script thiết lập hệ thống quản lý email
 */

// Đọc file schema SQL
$schemaFile = __DIR__ . '/database/schema.sql';

if (!file_exists($schemaFile)) {
    die("Không tìm thấy file schema.sql\n");
}

$sql = file_get_contents($schemaFile);

echo "=== THIẾT LẬP HỆ THỐNG QUẢN LÝ EMAIL ===\n\n";

// Cấu hình database
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
];

echo "Đang kết nối đến MySQL server...\n";

try {
    $pdo = new PDO(
        "mysql:host={$config['host']}", 
        $config['username'], 
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Kết nối thành công!\n\n";
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Đang thực thi các câu lệnh SQL...\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "✗ Lỗi: " . $e->getMessage() . "\n";
                echo "SQL: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "\n=== THIẾT LẬP HOÀN TẤT ===\n";
    echo "✓ Database 'email_management' đã được tạo\n";
    echo "✓ Các bảng đã được tạo thành công\n";
    echo "✓ Tài khoản admin mặc định: admin/admin123\n";
    echo "✓ Đã thêm một số email mẫu\n\n";
    
    echo "Bước tiếp theo:\n";
    echo "1. Cấu hình web server (Apache/Nginx) trỏ đến thư mục này\n";
    echo "2. Đảm bảo PHP có extension PDO MySQL\n";
    echo "3. Truy cập website và đăng nhập bằng admin/admin123\n";
    echo "4. Thay đổi mật khẩu admin và thêm thành viên\n\n";
    
    echo "Cấu hình database trong config/database.php:\n";
    echo "- Host: {$config['host']}\n";
    echo "- Database: email_management\n";
    echo "- Username: {$config['username']}\n";
    echo "- Password: (trống)\n\n";
    
} catch (PDOException $e) {
    echo "✗ Lỗi kết nối: " . $e->getMessage() . "\n";
    echo "\nVui lòng kiểm tra:\n";
    echo "1. MySQL server đã chạy chưa\n";
    echo "2. Thông tin kết nối có đúng không\n";
    echo "3. User có quyền tạo database không\n";
}
?>