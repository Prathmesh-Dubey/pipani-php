<?php
// install/index.php - Installation Wizard

// Check if config already exists and works
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
    try {
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : '3306') . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // If connection works, we don't need to install
            header('Location: ../index.php');
            exit;
        }
    } catch (PDOException $e) {
        // Connection failed, allow installer to run and show the previous error if any
    }
}

$errors = [];
$success = false;
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';

    $db_port = trim($_POST['db_port'] ?? '3306');
    $site_name = trim($_POST['site_name'] ?? 'Pipani Advertising');
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $timezone = trim($_POST['timezone'] ?? 'Asia/Kolkata');

    // Validate
    if (empty($db_name)) $errors[] = 'Database name is required';
    if (empty($db_user)) $errors[] = 'Database username is required';
    if (empty($admin_name)) $errors[] = 'Admin name is required';
    if (empty($admin_email)) $errors[] = 'Admin email is required';
    if (empty($admin_username)) $errors[] = 'Admin username is required';
    if (strlen($admin_password) < 8) $errors[] = 'Admin password must be at least 8 characters';
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid admin email';

    if (empty($errors)) {
        try {
            // Test database connection
            $dsn = "mysql:host=$db_host;port=$db_port";
            $pdo = new PDO($dsn, $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo->exec("USE `$db_name`");

            // Read and execute schema
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($schema);

            // Insert admin user with password
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, password = ? WHERE id = 1");
            $stmt->execute([$admin_name, $admin_email, $admin_username, $admin_password]);

            // Update site settings
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_name'");
            $stmt->execute([$site_name]);

            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'timezone'");
            $stmt->execute([$timezone]);

            // Generate config file
            $configContent = "<?php\n";
            $configContent .= "// Database Configuration\n";
            $configContent .= "define('DB_HOST', '$db_host');\n";
            $configContent .= "define('DB_NAME', '$db_name');\n";
            $configContent .= "define('DB_USER', '$db_user');\n";
            $configContent .= "define('DB_PASS', '$db_pass');\n";
            $configContent .= "define('DB_PORT', '$db_port');\n\n";
            $configContent .= "// Site Configuration\n";
            $configContent .= "define('SITE_NAME', '$site_name');\n";
            $configContent .= "define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . '/');\n";
            $configContent .= "define('ADMIN_URL', SITE_URL . 'admin/');\n";
            $configContent .= "define('UPLOAD_DIR', __DIR__ . '/../uploads/');\n";
            $configContent .= "define('TIMEZONE', '$timezone');\n\n";
            $configContent .= "// Security\n";
            $configContent .= "define('SALT', '" . bin2hex(random_bytes(32)) . "');\n";
            $configContent .= "define('SESSION_LIFETIME', 3600);\n\n";
            $configContent .= "// Error Reporting\n";
            $configContent .= "error_reporting(E_ALL);\n";
            $configContent .= "ini_set('display_errors', 0);\n";
            $configContent .= "ini_set('log_errors', 1);\n";
            $configContent .= "ini_set('error_log', __DIR__ . '/../logs/error.log');\n\n";
            $configContent .= "// Session\n";
            $configContent .= "ini_set('session.cookie_httponly', 1);\n";
            $configContent .= "ini_set('session.use_only_cookies', 1);\n";
            $configContent .= "ini_set('session.cookie_secure', 0);\n\n";
            $configContent .= "// Start session\n";
            $configContent .= "if (session_status() === PHP_SESSION_NONE) {\n";
            $configContent .= "    session_start();\n";
            $configContent .= "}\n";

            file_put_contents('../includes/config.php', $configContent);

            // Create upload directories
            mkdir('../uploads', 0777, true);
            mkdir('../uploads/images', 0777, true);
            mkdir('../uploads/files', 0777, true);
            mkdir('../uploads/thumbnails', 0777, true);
            mkdir('../logs', 0777, true);

            // Create .htaccess for uploads
            // Create .htaccess for uploads
            file_put_contents('../uploads/.htaccess', "Options -Indexes\n");

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Database Error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Timezone options
$timezones = [
    'Asia/Kolkata' => 'Asia/Kolkata (UTC+5:30)',
    'America/New_York' => 'America/New_York (UTC-5)',
    'Europe/London' => 'Europe/London (UTC+0)',
    'Asia/Dubai' => 'Asia/Dubai (UTC+4)',
    'Asia/Singapore' => 'Asia/Singapore (UTC+8)',
    'Australia/Sydney' => 'Australia/Sydney (UTC+10)',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipani CMS Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 20px;
        }

        .install-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px;
            max-width: 600px;
            width: 100%;
            color: #fff;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
        }

        .install-container h1 {
            font-weight: 700;
            font-size: 2rem;
            background: linear-gradient(135deg, #E61A27, #FF4D5A);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 8px;
        }

        .install-container .subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 32px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .step-indicator .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .step-indicator .step.active {
            background: #E61A27;
            border-color: #E61A27;
        }

        .step-indicator .step.done {
            background: #10b981;
            border-color: #10b981;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 12px;
            padding: 12px 16px;
        }

        .form-control:focus,
        .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #E61A27;
            box-shadow: 0 0 0 3px rgba(230, 26, 39, 0.2);
            color: #fff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-select option {
            background: #1a1a2e;
            color: #fff;
        }

        .btn-install {
            background: linear-gradient(135deg, #E61A27, #C4101B);
            border: none;
            color: #fff;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(230, 26, 39, 0.3);
            color: #fff;
        }

        .alert {
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .success-box {
            text-align: center;
            padding: 20px 0;
        }

        .success-box .icon {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 16px;
        }

        .success-box h2 {
            color: #10b981;
            margin-bottom: 8px;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #fff;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.3);
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="install-container">
        <h1><i class="fas fa-rocket me-2"></i> Pipani CMS</h1>
        <p class="subtitle">Installation Wizard</p>

        <?php if ($success): ?>
            <div class="success-box">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h2>Installation Complete!</h2>
                <p style="color:rgba(255,255,255,0.6);">Your CMS has been successfully installed.</p>
                <div style="margin-top:24px;">
                    <a href="../admin/index.php" class="btn-success-custom"><i class="fas fa-sign-in-alt me-2"></i> Go to Admin Panel</a>
                </div>
                <p style="margin-top:20px;color:rgba(255,255,255,0.3);font-size:0.85rem;">
                    <i class="fas fa-info-circle me-1"></i> Please delete the /install folder for security.
                </p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert" style="padding: 15px; margin-bottom: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <h5 style="color:rgba(255,255,255,0.8);margin-bottom:20px;"><i class="fas fa-database me-2"></i> Database Configuration</h5>
                    <div class="mb-3">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Port</label>
                        <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'pipani_cms') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Username</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Password</label>
                        <input type="password" name="db_pass" class="form-control">
                    </div>
                </div>

                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <h5 style="color:rgba(255,255,255,0.8);margin-bottom:20px;"><i class="fas fa-user-shield me-2"></i> Admin Account</h5>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" placeholder="John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" placeholder="admin@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="admin_username" class="form-control" value="<?= htmlspecialchars($_POST['admin_username'] ?? '') ?>" placeholder="admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <small style="color:rgba(255,255,255,0.3);">(min 8 characters)</small></label>
                        <input type="password" name="admin_password" class="form-control" required minlength="8">
                    </div>
                </div>

                <div>
                    <h5 style="color:rgba(255,255,255,0.8);margin-bottom:20px;"><i class="fas fa-cog me-2"></i> Site Settings</h5>
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($_POST['site_name'] ?? 'Pipani Advertising') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezones as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $value == ($_POST['timezone'] ?? 'Asia/Kolkata') ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-install" style="margin-top: 20px;"><i class="fas fa-rocket me-2"></i> Install Pipani CMS</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>