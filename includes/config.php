<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pipaniadvertising_db');
define('DB_USER', 'pipaniadvertising_db');
define('DB_PASS', 'BCLCEa8kZaTMjrqBbjuq');
define('DB_PORT', '3306');

// Site Configuration
define('SITE_NAME', 'Pipani Advertising');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('TIMEZONE', 'Asia/Kolkata');

// Security
define('SALT', '916ccd78c53035f622ac5ac6af9ac04eb361fb869b83fe599fe08888b59756d6');
define('SESSION_LIFETIME', 3600);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
