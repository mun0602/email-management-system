-- Email Management System Database Schema
-- MySQL/SQLite compatible schema as per requirements

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Apps table
CREATE TABLE IF NOT EXISTS apps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Emails table
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
);

-- User limits table
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
);

-- Email history table
CREATE TABLE IF NOT EXISTS email_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    email_id INTEGER,
    app_id INTEGER,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (email_id) REFERENCES emails(id),
    FOREIGN KEY (app_id) REFERENCES apps(id)
);

-- Shared emails table
CREATE TABLE IF NOT EXISTS shared_emails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    share_code VARCHAR(32) UNIQUE NOT NULL,
    email_ids TEXT NOT NULL,
    password VARCHAR(255) NULL,
    created_by INTEGER,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert default apps
INSERT OR IGNORE INTO apps (name) VALUES ('TanTan');
INSERT OR IGNORE INTO apps (name) VALUES ('HelloTalk');

-- Insert default admin user
INSERT OR IGNORE INTO users (username, password, full_name, role) 
VALUES ('admin', 'e64b78fc3bc91bcbc7dc232ba8ec59e0', 'Administrator', 'admin');

-- Insert default test user
INSERT OR IGNORE INTO users (username, password, full_name, role)
VALUES ('user1', '6512bd43d9caa6e02c990b0a82652dca', 'Test User', 'user');

-- Sample emails for testing
INSERT OR IGNORE INTO emails (email, password) VALUES 
('test1@gmail.com', 'password123'),
('test2@gmail.com', 'password456'),
('test3@gmail.com', 'password789'),
('test4@yahoo.com', 'mypass123'),
('test5@hotmail.com', 'secret456');

-- Set default user limits for existing user
INSERT OR IGNORE INTO user_limits (user_id, app_id, daily_limit, used_today) 
SELECT u.id, a.id, 25, 0 
FROM users u, apps a 
WHERE u.username = 'user1';