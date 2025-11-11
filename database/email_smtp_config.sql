-- Simple SMTP Configuration Table
-- For Gmail sending with App Password (no Google Cloud needed)

CREATE TABLE IF NOT EXISTS `email_smtp_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `smtp_username` varchar(255) NOT NULL COMMENT 'Gmail address',
    `smtp_password` varchar(255) NOT NULL COMMENT 'Gmail App Password',
    `is_active` tinyint(1) DEFAULT 1,
    `configured_by` int(11) DEFAULT NULL COMMENT 'Admin user_id who configured',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `configured_by` (`configured_by`),
    CONSTRAINT `fk_smtp_config_user` FOREIGN KEY (`configured_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Instructions:
-- To enable Gmail sending, insert your Gmail credentials:
-- 
-- INSERT INTO email_smtp_config (smtp_username, smtp_password, configured_by)
-- VALUES ('your-email@gmail.com', 'your-app-password', 1);
--
-- Get App Password from: https://myaccount.google.com/apppasswords
-- (Requires 2-Step Verification enabled)
