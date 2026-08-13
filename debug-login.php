<?php
// debug-login.php - Debug login issues

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h1>🔐 Login Debug Tool</h1>";

$username = 'admin';
$password = 'admin123';

echo "<h2>Testing: $username / $password</h2>";

try {
    $db = db();
    
    // 1. Check if user exists
    echo "<h3>1. Checking user in database...</h3>";
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found!<br>";
        echo "<pre>";
        print_r([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'] ? 'Active' : 'Inactive',
            'password_hash' => substr($user['password'], 0, 30) . '...'
        ]);
        echo "</pre>";
    } else {
        echo "❌ User not found!<br>";
        exit;
    }
    
    // 2. Test password verification
    echo "<h3>2. Testing password verification...</h3>";
    $passwordMatch = ($password === $user['password']);
    
    if ($passwordMatch) {
        echo "✅ Password matches!<br>";
    } else {
        echo "❌ Password does NOT match!<br>";
        echo "Password entered: " . $password . "<br>";
        echo "Stored password: " . $user['password'] . "<br>";
        
        // Auto-fix
        echo "<h3>3. Auto-fixing password...</h3>";
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$password, $user['id']])) {
            echo "✅ Password updated successfully!<br>";
            echo "Try logging in with: <strong>$username</strong> / <strong>$password</strong><br>";
        } else {
            echo "❌ Failed to update password<br>";
        }
    }
    
    // 3. Try actual login
    echo "<h3>4. Testing Auth::login()...</h3>";
    $result = Auth::login($username, $password);
    
    if ($result['success']) {
        echo "✅ Login successful!<br>";
        echo "User: " . $result['user']['username'] . "<br>";
        echo "Role: " . $result['user']['role'] . "<br>";
        echo "<br><a href='admin/dashboard.php' style='display:inline-block;padding:10px 20px;background:#E61A27;color:#fff;text-decoration:none;border-radius:5px;'>Go to Dashboard</a>";
    } else {
        echo "❌ Login failed: " . $result['error'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Manual SQL Fix:</h3>";
echo "<code>UPDATE users SET password = 'admin123' WHERE username = 'admin';</code>";
?>