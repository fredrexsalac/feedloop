-- Create feedback_limits table if it doesn't exist
CREATE TABLE IF NOT EXISTS `feedback_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT 'ID of the admin who set these limits',
  `daily_limit` int(11) NOT NULL DEFAULT 5 COMMENT 'Max feedback submissions per day',
  `weekly_limit` int(11) NOT NULL DEFAULT 20 COMMENT 'Max feedback submissions per week',
  `min_chars` int(11) NOT NULL DEFAULT 50 COMMENT 'Minimum characters required in feedback',
  `max_chars` int(11) NOT NULL DEFAULT 2000 COMMENT 'Maximum characters allowed in feedback',
  `enable_moderation` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether feedback requires moderation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_id` (`admin_id`),
  CONSTRAINT `fk_feedback_limits_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
