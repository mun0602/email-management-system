# Hệ thống quản lý Email

Một ứng dụng web PHP hoàn chỉnh để quản lý và phân phối email cho các thành viên, với giao diện tiếng Việt và thiết kế responsive.

## 🌟 Tính năng chính

### Trang Admin
- **Quản lý thành viên**: Thêm, sửa, xóa thành viên với hệ thống phân quyền
- **Quản lý email**: Thêm email theo định dạng `email|password`, hỗ trợ thêm nhiều email cùng lúc
- **Đặt giới hạn**: Thiết lập giới hạn số email mỗi thành viên có thể lấy
- **Thống kê tổng quan**: 
  - Tổng số email đã thêm và số email khả dụng
  - Số email mỗi thành viên đã lấy
  - Thống kê theo ứng dụng liên kết
  - Biểu đồ trực quan và báo cáo xuất CSV

### Trang thành viên
- **Lấy email**: 
  - Hiển thị email và password với định dạng đẹp mắt
  - Nút copy nhanh cho email và password
  - Chọn số lượng email muốn lấy (1-10 email/lần)
  - Hiển thị số email còn lại và đã lấy realtime
- **Chọn ứng dụng**:
  - Mặc định: TanTan
  - Lựa chọn: Hello Talk
  - Loại khác: Tự điền tên ứng dụng
- **Lịch sử**: 
  - Biểu đồ trực quan cho thống kê sử dụng
  - Danh sách tất cả email đã lấy với phân trang
  - Tìm kiếm và lọc theo ứng dụng và thời gian
  - Xuất CSV lịch sử sử dụng

## 🛠️ Công nghệ sử dụng

- **Backend**: PHP 7.4+ với PDO MySQL
- **Frontend**: Bootstrap 5, Chart.js, Custom CSS/JS
- **Database**: MySQL 5.7+
- **Icons**: Bootstrap Icons
- **Security**: CSRF protection, XSS prevention, SQL injection protection

## 📁 Cấu trúc thư mục

```
/
├── config/
│   ├── database.php      # Kết nối database
│   └── config.php        # Cấu hình chung
├── includes/
│   ├── header.php        # Header chung
│   ├── footer.php        # Footer chung
│   └── functions.php     # Hàm tiện ích
├── admin/
│   ├── index.php         # Dashboard admin
│   ├── users.php         # Quản lý thành viên
│   ├── emails.php        # Quản lý email
│   └── statistics.php    # Thống kê chi tiết
├── member/
│   ├── index.php         # Dashboard thành viên
│   ├── get-email.php     # Lấy email
│   └── history.php       # Lịch sử sử dụng
├── assets/
│   ├── css/style.css     # Custom CSS
│   ├── js/script.js      # JavaScript chung
│   └── images/           # Hình ảnh
├── api/
│   ├── get-user-stats.php    # API thống kê user
│   └── statistics.php        # API thống kê admin
├── auth/
│   ├── login.php         # Đăng nhập
│   ├── logout.php        # Đăng xuất
│   └── check-auth.php    # Kiểm tra xác thực
├── database/
│   └── schema.sql        # Database schema
├── index.php             # Trang chủ (redirect)
├── setup.php             # Script thiết lập
└── README.md             # Hướng dẫn này
```

## 🚀 Hướng dẫn cài đặt

### 1. Yêu cầu hệ thống
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc MariaDB 10.2+
- Web server (Apache/Nginx)
- Extension PHP: PDO, PDO_MySQL

### 2. Tải và cấu hình
```bash
# Clone repository
git clone <repository-url>
cd email-management-system

# Cấu hình quyền thư mục (nếu cần)
chmod -R 755 .
```

### 3. Thiết lập database
```bash
# Chạy script thiết lập
php setup.php
```

Hoặc thực hiện thủ công:
1. Tạo database `email_management`
2. Import file `database/schema.sql`
3. Cấu hình kết nối trong `config/database.php`

### 4. Cấu hình web server

#### Apache (.htaccess đã được tạo sẵn)
```apache
DocumentRoot /path/to/email-management-system
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/email-management-system;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Truy cập và sử dụng
1. Mở trình duyệt và truy cập website
2. Đăng nhập với tài khoản admin mặc định:
   - **Username**: `admin`
   - **Password**: `admin123`
3. Thay đổi mật khẩu admin
4. Thêm thành viên và email để bắt đầu sử dụng

## 📊 Database Schema

### Bảng `users`
```sql
id INT PRIMARY KEY AUTO_INCREMENT
username VARCHAR(50) UNIQUE NOT NULL
password VARCHAR(255) NOT NULL
role ENUM('admin', 'member') DEFAULT 'member'
email_limit INT DEFAULT 10
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### Bảng `emails`
```sql
id INT PRIMARY KEY AUTO_INCREMENT
email VARCHAR(255) UNIQUE NOT NULL
password VARCHAR(255) NOT NULL
is_used BOOLEAN DEFAULT FALSE
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### Bảng `email_usage`
```sql
id INT PRIMARY KEY AUTO_INCREMENT
user_id INT NOT NULL
email_id INT NOT NULL
app_name VARCHAR(100) NOT NULL
used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (user_id) REFERENCES users(id)
FOREIGN KEY (email_id) REFERENCES emails(id)
```

## 🔒 Bảo mật

- **Authentication**: Session-based với timeout
- **Authorization**: Role-based access control (Admin/Member)
- **CSRF Protection**: Token validation cho tất cả forms
- **SQL Injection**: PDO prepared statements
- **XSS Prevention**: Input sanitization và output escaping
- **Password Security**: bcrypt hashing

## 🎨 Giao diện

- **Framework**: Bootstrap 5
- **Color Scheme**: Material Design inspired
- **Typography**: System font stack
- **Icons**: Bootstrap Icons
- **Responsive**: Mobile-first design
- **Dark Mode**: Tự động theo system preference

## 📱 Tính năng nổi bật

### Copy to Clipboard
- Copy nhanh email/password bằng một click
- Feedback visual khi copy thành công
- Hỗ trợ copy hàng loạt

### Real-time Updates
- Cập nhật số lượng email còn lại
- Refresh statistics tự động
- Toast notifications

### Charts & Analytics
- Biểu đồ sử dụng theo ngày
- Phân bố theo ứng dụng
- Thống kê chi tiết theo user

### Export Functions
- Xuất CSV lịch sử sử dụng
- Báo cáo thống kê admin
- Dữ liệu UTF-8 support

## 🐛 Khắc phục sự cố

### Lỗi kết nối database
```php
// Kiểm tra config/database.php
$host = 'localhost';
$dbname = 'email_management';
$username = 'root';
$password = '';
```

### Lỗi permission
```bash
chmod -R 755 /path/to/email-management-system
chown -R www-data:www-data /path/to/email-management-system
```

### Lỗi 404 - URL rewrite
- Apache: Kiểm tra mod_rewrite enabled
- Nginx: Cấu hình try_files đúng

## 🔄 Cập nhật

```bash
git pull origin main
# Kiểm tra database schema có thay đổi không
# Chạy migration nếu cần
```

## 🤝 Đóng góp

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

## 📞 Hỗ trợ

- **Issues**: [GitHub Issues](https://github.com/your-username/email-management-system/issues)
- **Documentation**: Xem file README này
- **Email**: your-email@example.com

---

**Phát triển bởi Email Management Team** 🚀