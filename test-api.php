<?php
// test-api.php - Simple login test API

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$username = $_GET['username'] ?? $_POST['username'] ?? 'admin';
$password = $_GET['password'] ?? $_POST['password'] ?? 'admin123';

$response = [
    'success' => false,
    'message' => '',
    'debug' => []
];

try {
    $db = db();
    
    // Check user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $response['message'] = 'User not found';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    $response['debug']['user_found'] = true;
    $response['debug']['username'] = $user['username'];
    $response['debug']['role'] = $user['role'];
    
    // Check password
    $passwordMatch = ($password === $user['password']);
    $response['debug']['password_matches'] = $passwordMatch;
    
    if (!$passwordMatch) {
        $response['message'] = 'Invalid password';
        $response['debug']['stored_password'] = $user['password'];
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Try login
    $result = Auth::login($username, $password);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['data'] = [
            'id' => $result['user']['id'],
            'username' => $result['user']['username'],
            'email' => $result['user']['email'],
            'role' => $result['user']['role'],
            'full_name' => $result['user']['full_name']
        ];
        $response['debug']['session_id'] = session_id();
    } else {
        $response['message'] = $result['error'];
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>