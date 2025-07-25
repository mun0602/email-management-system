-- Email Management System Database Schema
-- Tạo database và các bảng cần thiết

CREATE DATABASE IF NOT EXISTS email_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE email_management;

-- Bảng users (người dùng)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    email_limit INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng emails (email pool)
CREATE TABLE emails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng email_usage (lịch sử sử dụng)
CREATE TABLE email_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email_id INT NOT NULL,
    app_name VARCHAR(100) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
);

-- Tạo indexes để tối ưu hiệu suất
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_emails_used ON emails(is_used);
CREATE INDEX idx_usage_user_id ON email_usage(user_id);
CREATE INDEX idx_usage_app_name ON email_usage(app_name);
CREATE INDEX idx_usage_used_at ON email_usage(used_at);

-- Thêm admin mặc định (password: admin123)
INSERT INTO users (username, password, role, email_limit) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 999999);

-- Thêm một số email mẫu
INSERT INTO emails (email, password) VALUES 
('test1@example.com', 'password123'),
('test2@example.com', 'password456'),
('test3@example.com', 'password789');