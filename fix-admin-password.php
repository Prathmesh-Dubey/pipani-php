<?php
// fix-admin-password.php - Reset admin password properly

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$username = 'admin';
$newPassword = 'admin123';

try {
    $db = db();
    
    // Update the password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    $result = $stmt->execute([$newPassword, $username]);
    
    if ($result) {
        echo "✅ Password updated successfully!<br><br>";
        echo "Login with:<br>";
        echo "<strong>Username:</strong> admin<br>";
        echo "<strong>Password:</strong> admin123<br><br>";
        echo "<a href='admin/index.php'>Go to Admin Login</a>";
    } else {
        echo "❌ Failed to update password";
    }
    
    // Show current users
    echo "<br><br><strong>Current users in database:</strong><br>";
    $stmt = $db->query("SELECT id, username, email, role, status FROM users");
    $users = $stmt->fetchAll();
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>