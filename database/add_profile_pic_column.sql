-- Add profile_pic column to frontend_users table
-- Run this in phpMyAdmin or MySQL command line

ALTER TABLE `frontend_users` 
ADD COLUMN `profile_pic` VARCHAR(255) NULL DEFAULT NULL 
AFTER `status`;

-- Verify the column was added
-- SELECT * FROM frontend_users LIMIT 1;
