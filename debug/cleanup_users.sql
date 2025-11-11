-- Cleanup Script for FeedLoop Users
-- Run this in phpMyAdmin SQL tab

-- 1. View all frontend users (to see what's there)
SELECT * FROM frontend_users;

-- 2. Delete all frontend users (if you want to start fresh)
-- DELETE FROM frontend_users;

-- 3. Delete specific user by username
-- DELETE FROM frontend_users WHERE username = 'your_username';

-- 4. Set user to inactive instead of deleting
-- UPDATE frontend_users SET status = 'inactive' WHERE username = 'your_username';

-- 5. Reactivate an inactive user
-- UPDATE frontend_users SET status = 'active' WHERE username = 'your_username';

-- 6. View only active users
-- SELECT * FROM frontend_users WHERE status = 'active';

-- 7. View only inactive users
-- SELECT * FROM frontend_users WHERE status = 'inactive';
