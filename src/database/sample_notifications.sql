-- Insert sample notifications for testing
-- Replace USER_ID with an actual user ID from your users table

-- Sample notification 1: Like notification
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, is_read, created_at) 
VALUES (2, 'like', 'New Like', '<strong>John Doe</strong> liked your post', 1, 'post', 0, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Sample notification 2: Comment notification
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, is_read, created_at) 
VALUES (2, 'comment', 'New Comment', '<strong>Sarah Wilson</strong> commented on your post', 1, 'post', 0, DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- Sample notification 3: Approval notification
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, is_read, created_at) 
VALUES (2, 'approval', 'Post Approved', 'Your post has been approved!', 2, 'post', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Sample notification 4: Event notification
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, is_read, created_at) 
VALUES (2, 'event', 'New Event', 'A new event has been posted: <strong>Tech Workshop 2026</strong>', 1, 'event', 0, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Sample notification 5: Notice notification
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, is_read, created_at) 
VALUES (2, 'notice', 'New Notice', 'Important: Examination schedule has been updated', 1, 'notice', 0, DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- To check the notifications:
-- SELECT * FROM notifications WHERE user_id = 2 ORDER BY created_at DESC;
