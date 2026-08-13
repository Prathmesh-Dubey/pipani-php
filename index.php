<?php
// index.php - Main Frontend Entry Point

// Check if config exists
$configFile = __DIR__ . '/includes/config.php';

if (!file_exists($configFile)) {
    // Redirect to installer
    header('Location: /install/');
    exit;
}

// Load configuration
require_once $configFile;
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Check if site is in maintenance mode
if (getSetting('maintenance_mode', '0') == '1') {
    $maintenanceMessage = getSetting('maintenance_message', 'Site is under maintenance. Please check back later.');
    die('<h1>Under Maintenance</h1><p>' . htmlspecialchars($maintenanceMessage) . '</p>');
}

// Load the frontend template
require_once __DIR__ . '/templates/frontend/index.php';