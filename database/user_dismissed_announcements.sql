-- Table for tracking dismissed announcements by users
CREATE TABLE IF NOT EXISTS user_dismissed_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    form_id INT NOT NULL,
    dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES frontend_users(id) ON DELETE CASCADE,
    FOREIGN KEY (form_id) REFERENCES custom_forms(form_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_form (user_id, form_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for user notifications
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    form_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES frontend_users(id) ON DELETE CASCADE,
    FOREIGN KEY (form_id) REFERENCES custom_forms(form_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
