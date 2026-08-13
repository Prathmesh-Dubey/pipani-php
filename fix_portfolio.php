<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$pdo = db();
$stmt = $pdo->query("SELECT COUNT(*) FROM portfolio");
$count = $stmt->fetchColumn();

if ($count == 0) {
    $stmt = $pdo->prepare("INSERT INTO portfolio (title, category, image) VALUES (?, ?, ?)");
    $stmt->execute(['Hoardings Campaign', '₹ 100,000.00', 'img/portfolio_hoarding.png']);
    $stmt->execute(['Bus Branding', '₹ 100,000.00', 'img/portfolio_bus.png']);
    $stmt->execute(['Metro Branding', '₹ 100,000.00', 'img/portfolio_metro.png']);
    echo "Inserted default portfolio items.";
} else {
    echo "Portfolio table already has data.";
}
