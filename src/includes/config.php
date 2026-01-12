<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uiu_social_connect');

// Application Configuration
define('SITE_URL', 'http://localhost/uiu-social-connect');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// File Upload Limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);

// Session Configuration
// Only set session ini values when a session has not already started to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
