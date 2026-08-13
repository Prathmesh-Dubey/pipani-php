<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$pdo = db();
$pdo->exec("ALTER TABLE content_blocks MODIFY image LONGTEXT");
$pdo->exec("ALTER TABLE services MODIFY image LONGTEXT, MODIFY gallery LONGTEXT");
$pdo->exec("ALTER TABLE portfolio MODIFY image LONGTEXT");
$pdo->exec("ALTER TABLE testimonials MODIFY avatar LONGTEXT");
$pdo->exec("ALTER TABLE industries MODIFY image LONGTEXT");
$pdo->exec("ALTER TABLE seo_settings MODIFY og_image LONGTEXT, MODIFY twitter_image LONGTEXT");
$pdo->exec("ALTER TABLE users MODIFY avatar LONGTEXT");
echo "Column types updated to LONGTEXT.";
