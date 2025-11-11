-- User Preferences table for personalization
CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    theme ENUM('light','dark','animated') DEFAULT 'light',
    accent_color VARCHAR(20) NULL,
    compact_mode TINYINT(1) DEFAULT 0,
    language VARCHAR(8) DEFAULT 'en',
    timezone VARCHAR(64) DEFAULT 'UTC',
    notif_email TINYINT(1) DEFAULT 1,
    notif_push TINYINT(1) DEFAULT 1,
    notif_categories JSON NULL,
    landing_section VARCHAR(40) DEFAULT 'announcements',
    show_tutorials TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
