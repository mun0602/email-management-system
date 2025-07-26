<?php
// Database migration script for Email Management System upgrade
require_once __DIR__ . '/config/database.php';

echo "Starting database migration...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "1. Creating apps table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS apps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "2. Creating user_limits table...\n";
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
    
    echo "3. Creating new emails table...\n";
    // First backup existing mails data
    $existingMails = $pdo->query("SELECT * FROM mails")->fetchAll();
    
    // Create new emails table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS emails_new (
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
    
    echo "4. Creating new email_history table...\n";
    // Backup existing history
    $existingHistory = $pdo->query("SELECT * FROM mail_history")->fetchAll();
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_history_new (
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
    
    echo "5. Creating shared_emails table...\n";
    // Backup existing shared mails
    $existingShared = [];
    if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='shared_mails'")->fetch()) {
        $existingShared = $pdo->query("SELECT * FROM shared_mails")->fetchAll();
    }
    
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
    
    echo "6. Inserting default apps...\n";
    $pdo->exec("INSERT OR IGNORE INTO apps (name) VALUES ('TanTan')");
    $pdo->exec("INSERT OR IGNORE INTO apps (name) VALUES ('HelloTalk')");
    
    // Get app IDs
    $tantan_id = $pdo->query("SELECT id FROM apps WHERE name = 'TanTan'")->fetchColumn();
    $hellotalk_id = $pdo->query("SELECT id FROM apps WHERE name = 'HelloTalk'")->fetchColumn();
    
    echo "7. Migrating existing mail data...\n";
    foreach ($existingMails as $mail) {
        $isUsed = $mail['status'] === 'used' ? 1 : 0;
        $usedBy = null;
        $usedAt = null;
        
        if ($isUsed) {
            $usedAt = $mail['used_at'] ?? $mail['created_at'];
        }
        
        $pdo->prepare("
            INSERT INTO emails_new (id, email, password, app_id, is_used, used_by, used_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $mail['id'],
            $mail['email'],
            $mail['password'],
            null, // Will be set based on history
            $isUsed,
            $usedBy,
            $usedAt,
            $mail['created_at']
        ]);
    }
    
    echo "8. Migrating existing history data...\n";
    foreach ($existingHistory as $history) {
        // Determine app_id based on app_name
        $app_id = null;
        if ($history['app_name'] === 'TanTan') {
            $app_id = $tantan_id;
        } elseif ($history['app_name'] === 'HelloTalk') {
            $app_id = $hellotalk_id;
        } else {
            // Create custom app if it doesn't exist
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO apps (name) VALUES (?)");
            $stmt->execute([$history['app_name']]);
            $app_id = $pdo->query("SELECT id FROM apps WHERE name = " . $pdo->quote($history['app_name']))->fetchColumn();
        }
        
        $pdo->prepare("
            INSERT INTO email_history_new (user_id, email_id, app_id, taken_at)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $history['user_id'],
            $history['mail_id'],
            $app_id,
            $history['taken_at']
        ]);
        
        // Update email with app_id and used_by
        $pdo->prepare("
            UPDATE emails_new 
            SET app_id = ?, used_by = ? 
            WHERE id = ? AND is_used = 1
        ")->execute([
            $app_id,
            $history['user_id'],
            $history['mail_id']
        ]);
    }
    
    echo "9. Migrating shared emails data...\n";
    foreach ($existingShared as $shared) {
        $pdo->prepare("
            INSERT INTO shared_emails (share_code, email_ids, password, created_by, created_at)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $shared['share_token'],
            $shared['mail_ids'],
            $shared['password'],
            $shared['created_by'],
            $shared['created_at']
        ]);
    }
    
    echo "10. Setting up user limits...\n";
    $users = $pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll();
    $apps = $pdo->query("SELECT id FROM apps")->fetchAll();
    
    foreach ($users as $user) {
        foreach ($apps as $app) {
            // Count current usage
            $used = $pdo->prepare("
                SELECT COUNT(*) FROM email_history_new 
                WHERE user_id = ? AND app_id = ? AND DATE(taken_at) = DATE('now')
            ");
            $used->execute([$user['id'], $app['id']]);
            $usedCount = $used->fetchColumn();
            
            $pdo->prepare("
                INSERT OR IGNORE INTO user_limits (user_id, app_id, daily_limit, used_today)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $user['id'],
                $app['id'],
                25, // Default limit
                $usedCount
            ]);
        }
    }
    
    echo "11. Replacing old tables...\n";
    $pdo->exec("DROP TABLE IF EXISTS mails");
    $pdo->exec("ALTER TABLE emails_new RENAME TO emails");
    
    $pdo->exec("DROP TABLE IF EXISTS mail_history");
    $pdo->exec("ALTER TABLE email_history_new RENAME TO email_history");
    
    $pdo->exec("DROP TABLE IF EXISTS shared_mails");
    
    // Update users table to add full_name if not exists
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    $hasFullName = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'full_name') {
            $hasFullName = true;
            break;
        }
    }
    
    if (!$hasFullName) {
        echo "12. Adding full_name column to users...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100)");
    }
    
    // Update role column to proper ENUM-like check
    echo "13. Updating users role constraints...\n";
    $pdo->exec("UPDATE users SET role = 'admin' WHERE role = 'admin'");
    $pdo->exec("UPDATE users SET role = 'user' WHERE role = 'user' OR role IS NULL");
    
    // Commit transaction
    $pdo->commit();
    
    echo "✅ Database migration completed successfully!\n";
    echo "📊 Migration summary:\n";
    echo "   - " . count($existingMails) . " emails migrated\n";
    echo "   - " . count($existingHistory) . " history records migrated\n";
    echo "   - " . count($existingShared) . " shared emails migrated\n";
    echo "   - " . count($users) . " users configured with limits\n";
    echo "   - " . count($apps) . " apps available\n";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}