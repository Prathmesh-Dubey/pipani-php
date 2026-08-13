<?php
// admin/index.php - Admin Login Page

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if already logged in — send directly to website in Editor Mode
if (Auth::isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = Auth::login($username, $password, $remember);
        if ($result['success']) {
            // Redirect to website in Editor Mode instead of Dashboard
            header('Location: ' . SITE_URL);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Check remember me cookie — send directly to website in Editor Mode
Auth::checkRememberMe();
if (Auth::isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= siteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .login-container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 48px;
            max-width: 420px;
            width: 100%;
            color: #fff;
            box-shadow: 0 40px 80px rgba(0,0,0,0.5);
        }
        .login-container .logo {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .login-container .logo span { color: #E61A27; }
        .login-container .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.4);
            margin-bottom: 32px;
            font-size: 0.9rem;
        }
        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: #E61A27;
            box-shadow: 0 0 0 3px rgba(230,26,39,0.2);
            color: #fff;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .form-label { color: rgba(255,255,255,0.6); font-weight: 500; }
        .btn-login {
            background: linear-gradient(135deg, #E61A27, #C4101B);
            border: none;
            color: #fff;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(230,26,39,0.3);
            color: #fff;
        }
        .alert { border-radius: 12px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
        .form-check-label { color: rgba(255,255,255,0.5); font-size: 0.9rem; }
        .form-check-input { background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
        .form-check-input:checked { background-color: #E61A27; border-color: #E61A27; }
        .forgot-link { color: rgba(255,255,255,0.4); font-size: 0.85rem; text-decoration: none; transition: all 0.3s; }
        .forgot-link:hover { color: #E61A27; }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.2);
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo"><?= siteName() ?><span>.</span></div>
        <div class="subtitle">Admin Panel Login</div>

        <?php if ($error): ?>
            <div class="alert"><i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i> Sign In</button>
        </form>
        <div class="footer-text">
            <i class="fas fa-shield-alt me-1"></i> Secure Login
        </div>
    </div>
</body>
</html>