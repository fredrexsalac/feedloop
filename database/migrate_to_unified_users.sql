-- This script merges frontend_users into the users table for a unified user system

-- Step 1: Modify users table role enum to include 'user'
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user';

-- Step 2: Add full_name column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(100) NULL AFTER `email`;

-- Step 3: Add profile_pic column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `profile_pic` VARCHAR(255) NULL AFTER `full_name`;

-- Step 4: Add email_verified column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0 AFTER `profile_pic`;

-- Step 5: Add email_verified column to frontend_users if it doesn't exist (for migration)
ALTER TABLE `frontend_users` 
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0;

-- Step 6: Migrate existing frontend_users to users table
INSERT INTO `users` (`username`, `email`, `full_name`, `password`, `profile_pic`, `email_verified`, `role`, `status`, `created_at`)
SELECT 
    fu.username,
    fu.email,
    COALESCE(fu.full_name, fu.username) as full_name,
    fu.password,
    COALESCE(fu.profile_pic, NULL) as profile_pic,
    COALESCE(fu.email_verified, 0) as email_verified,
    'user' as role,
    fu.status,
    fu.created_at
FROM `frontend_users` fu
WHERE NOT EXISTS (
    SELECT 1 FROM `users` u WHERE u.username = fu.username OR u.email = fu.email
);

-- Step 7: Update foreign key constraints to reference unified users table
-- IMPORTANT: Only run these after verifying the migration worked!

-- 7a. Drop old foreign key constraints referencing frontend_users
-- ALTER TABLE `feedback_submissions` DROP FOREIGN KEY `fk_feedback_frontend_user`;
-- ALTER TABLE `notifications` DROP FOREIGN KEY `fk_notifications_user`;
-- ALTER TABLE `user_dismissed_announcements` DROP FOREIGN KEY IF EXISTS `user_dismissed_announcements_ibfk_1`;
-- ALTER TABLE `user_notifications` DROP FOREIGN KEY IF EXISTS `user_notifications_ibfk_1`;

-- 7b. Update foreign keys to reference users table instead
-- Note: This assumes you've mapped frontend_users.id to users.user_id correctly
-- ALTER TABLE `feedback_submissions` 
--   ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`frontend_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- ALTER TABLE `notifications` 
--   ADD CONSTRAINT `fk_notifications_user_new` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- ALTER TABLE `user_dismissed_announcements` 
--   ADD CONSTRAINT `fk_dismissed_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- ALTER TABLE `user_notifications` 
--   ADD CONSTRAINT `fk_user_notifications` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Step 8: (Optional) Backup frontend_users before dropping
-- CREATE TABLE frontend_users_backup AS SELECT * FROM frontend_users;

-- Step 9: (Optional) Drop frontend_users table after verifying migration
-- IMPORTANT: Must disable foreign key checks first due to constraints
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS `frontend_users`;
-- SET FOREIGN_KEY_CHECKS = 1;

-- Verification queries:
-- SELECT COUNT(*) as total_users, role FROM users GROUP BY role;
-- SELECT * FROM users WHERE role = 'user' LIMIT 10;
