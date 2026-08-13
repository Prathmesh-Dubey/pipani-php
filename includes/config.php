<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pipani');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_PORT', '3306');

// Site Configuration
define('SITE_NAME', 'Pipani Advertising');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('TIMEZONE', 'Asia/Kolkata');

// Security
define('SALT', 'bbd5923e7078b6c1621e8dd04f64a4c92e5580171511d9f049b46ee5e9faaee8');
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
