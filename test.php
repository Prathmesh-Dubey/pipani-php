<?php
require 'includes/config.php';
require 'includes/database.php';
$db = db();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'logo'");
echo "Logo: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT content FROM content_blocks WHERE slug = 'about_image'");
echo "About: " . $stmt->fetchColumn() . PHP_EOL;
