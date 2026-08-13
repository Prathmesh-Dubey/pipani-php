<?php
// admin/pages/settings.php - Site Settings

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireLogin();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => sanitizeInput($_POST['site_name'] ?? ''),
        'site_tagline' => sanitizeInput($_POST['site_tagline'] ?? ''),
        'site_description' => sanitizeInput($_POST['site_description'] ?? ''),
        'site_email' => sanitizeInput($_POST['site_email'] ?? ''),
        'site_phone' => sanitizeInput($_POST['site_phone'] ?? ''),
        'site_address' => sanitizeInput($_POST['site_address'] ?? ''),
        'footer_text' => sanitizeInput($_POST['footer_text'] ?? ''),
        'logo' => sanitizeInput($_POST['logo'] ?? ''),
        'favicon' => sanitizeInput($_POST['favicon'] ?? ''),
        'max_upload_size' => sanitizeInput($_POST['max_upload_size'] ?? '5242880'),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'maintenance_message' => sanitizeInput($_POST['maintenance_message'] ?? 'Site is under maintenance. Please check back later.'),
        'google_analytics' => sanitizeInput($_POST['google_analytics'] ?? ''),
        'facebook_pixel' => sanitizeInput($_POST['facebook_pixel'] ?? ''),
        'smtp_host' => sanitizeInput($_POST['smtp_host'] ?? ''),
        'smtp_port' => sanitizeInput($_POST['smtp_port'] ?? '587'),
        'smtp_username' => sanitizeInput($_POST['smtp_username'] ?? ''),
        'smtp_password' => sanitizeInput($_POST['smtp_password'] ?? ''),
        'smtp_encryption' => sanitizeInput($_POST['smtp_encryption'] ?? 'tls'),
        'timezone' => sanitizeInput($_POST['timezone'] ?? 'Asia/Kolkata')
    ];

    $success = true;
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value)) {
            $success = false;
        }
    }

    if ($success) {
        $message = 'Settings updated successfully!';
        logActivity($_SESSION['user_id'], 'update_settings', 'Updated site settings');
        // Refresh timezone
        date_default_timezone_set(getSetting('timezone', 'Asia/Kolkata'));
    } else {
        $error = 'Failed to update some settings';
    }
}

// Get current settings
$siteName = getSetting('site_name', 'Pipani Advertising');
$siteTagline = getSetting('site_tagline', 'Advertising · PR · Massive Impact');
$siteDescription = getSetting('site_description', '');
$siteEmail = getSetting('site_email', 'info@pipaniadvertising.com');
$sitePhone = getSetting('site_phone', '+91 9766840787');
$siteAddress = getSetting('site_address', 'Dhankawadi, Pune, Maharashtra 411043');
$footerText = getSetting('footer_text', '2026 Pipani Advertising. All Rights Reserved.');
$logo = getSetting('logo', '');
$favicon = getSetting('favicon', '');
$maxUploadSize = getSetting('max_upload_size', '5242880');
$maintenanceMode = getSetting('maintenance_mode', '0');
$maintenanceMessage = getSetting('maintenance_message', 'Site is under maintenance. Please check back later.');
$googleAnalytics = getSetting('google_analytics', '');
$facebookPixel = getSetting('facebook_pixel', '');
$smtpHost = getSetting('smtp_host', '');
$smtpPort = getSetting('smtp_port', '587');
$smtpUsername = getSetting('smtp_username', '');
$smtpPassword = getSetting('smtp_password', '');
$smtpEncryption = getSetting('smtp_encryption', 'tls');
$timezone = getSetting('timezone', 'Asia/Kolkata');

$timezones = [
    'Asia/Kolkata' => 'Asia/Kolkata (UTC+5:30)',
    'America/New_York' => 'America/New_York (UTC-5)',
    'Europe/London' => 'Europe/London (UTC+0)',
    'Asia/Dubai' => 'Asia/Dubai (UTC+4)',
    'Asia/Singapore' => 'Asia/Singapore (UTC+8)',
    'Australia/Sydney' => 'Australia/Sydney (UTC+10)',
];

// Get media files for logo selection
$mediaFiles = getMedia();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= siteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #E61A27; --sidebar-width: 280px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #0a0a0a;
            color: #fff;
            padding: 24px 16px;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        .sidebar .logo { font-size: 1.6rem; font-weight: 800; padding: 0 12px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 24px; }
        .sidebar .logo span { color: var(--primary); }
        .sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 2px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .sidebar .nav-item:hover, .sidebar .nav-item.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .sidebar .nav-item.active { background: rgba(230,26,39,0.15); color: var(--primary); }
        .sidebar .nav-item i { width: 20px; font-size: 1.1rem; }
        .sidebar .nav-section {
            color: rgba(255,255,255,0.2);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 16px 8px;
            font-weight: 600;
        }
        .sidebar .user-card {
            padding: 16px;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            margin-bottom: 16px;
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar .user-card .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 600;
        }
        .sidebar .user-card .name { font-weight: 600; font-size: 0.9rem; }
        .sidebar .user-card .role { font-size: 0.75rem; color: rgba(255,255,255,0.3); }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 24px 32px;
            min-height: 100vh;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0 24px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .header h1 { font-size: 1.8rem; font-weight: 800; }
        .header .user-info { display: flex; align-items: center; gap: 16px; }
        .header .user-info .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 600;
        }
        .content-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }
        .content-card h5 { font-weight: 700; margin-bottom: 16px; }
        .btn-primary-custom {
            background: linear-gradient(135deg, #E61A27, #C4101B);
            border: none;
            color: #fff;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(230,26,39,0.3); color: #fff; }
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1a1a2e;
            cursor: pointer;
        }
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .overlay.active { display: block; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .sidebar-toggle { display: block; }
        }
        .setting-group { border-left: 3px solid var(--primary); padding-left: 16px; margin-top: 24px; }
        .setting-group h6 { color: var(--primary); font-weight: 600; }
        .logo-preview {
            max-width: 100px;
            max-height: 60px;
            object-fit: contain;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo"><?= siteName() ?><span>.</span></div>
    <?php $user = Auth::getCurrentUser(); ?>
    <div class="user-card">
        <div class="avatar"><?= substr($user['full_name'] ?? $user['username'], 0, 2) ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></div>
            <div class="role"><?= $user['role'] ?></div>
        </div>
    </div>

    <div class="nav-section">Main</div>
    <a href="../dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="content.php" class="nav-item"><i class="fas fa-file-alt"></i> Content</a>
    <a href="media.php" class="nav-item"><i class="fas fa-images"></i> Media</a>
    <a href="menu.php" class="nav-item"><i class="fas fa-bars"></i> Menu</a>

    <div class="nav-section">Content</div>
    <a href="services.php" class="nav-item"><i class="fas fa-concierge-bell"></i> Services</a>
    <a href="industries.php" class="nav-item"><i class="fas fa-industry"></i> Industries</a>
    <a href="portfolio.php" class="nav-item"><i class="fas fa-briefcase"></i> Portfolio</a>
    <a href="testimonials.php" class="nav-item"><i class="fas fa-comment"></i> Testimonials</a>
    <a href="faqs.php" class="nav-item"><i class="fas fa-question-circle"></i> FAQs</a>

    <div class="nav-section">System</div>
    <a href="contacts.php" class="nav-item"><i class="fas fa-envelope"></i> Contacts</a>
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
    <a href="seo.php" class="nav-item"><i class="fas fa-search"></i> SEO</a>
    <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php" class="nav-item" style="color:rgba(255,255,255,0.3);"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="overlay" id="overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Site Settings</h1>
        </div>
        <div class="user-info">
            <span style="font-size:0.9rem;color:#6b7280;"><?= date('l, F j, Y') ?></span>
            <div class="avatar"><?= substr($user['full_name'] ?? $user['username'], 0, 2) ?></div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="content-card">
        <h5><i class="fas fa-cog me-2"></i> General Settings</h5>
        <form method="POST">
            <!-- General -->
            <div class="setting-group">
                <h6>General</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($siteName) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Tagline</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($siteTagline) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Site Description</label>
                    <textarea name="site_description" class="form-control" rows="2"><?= htmlspecialchars($siteDescription) ?></textarea>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="setting-group">
                <h6>Contact Information</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($siteEmail) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="site_phone" class="form-control" value="<?= htmlspecialchars($sitePhone) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezones as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $timezone == $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="site_address" class="form-control" value="<?= htmlspecialchars($siteAddress) ?>">
                </div>
            </div>

            <!-- Branding -->
            <div class="setting-group">
                <h6>Branding</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo URL</label>
                        <input type="text" name="logo" class="form-control" value="<?= htmlspecialchars($logo) ?>" placeholder="logo.png">
                        <?php if ($logo): ?>
                            <div class="mt-2">
                                <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($logo) ?>" class="logo-preview" alt="Logo">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Favicon URL</label>
                        <input type="text" name="favicon" class="form-control" value="<?= htmlspecialchars($favicon) ?>" placeholder="favicon.ico">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="setting-group">
                <h6>Footer</h6>
                <div class="mb-3">
                    <label class="form-label">Footer Text</label>
                    <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars($footerText) ?>">
                </div>
            </div>

            <!-- Media -->
            <div class="setting-group">
                <h6>Media Settings</h6>
                <div class="mb-3">
                    <label class="form-label">Max Upload Size (bytes)</label>
                    <input type="number" name="max_upload_size" class="form-control" value="<?= htmlspecialchars($maxUploadSize) ?>">
                    <small class="text-muted">Default: 5242880 (5MB)</small>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="setting-group">
                <h6>Maintenance Mode</h6>
                <div class="form-check mb-2">
                    <input type="checkbox" name="maintenance_mode" class="form-check-input" id="maintenance_mode" <?= $maintenanceMode == '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="maintenance_mode">Enable Maintenance Mode</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Maintenance Message</label>
                    <input type="text" name="maintenance_message" class="form-control" value="<?= htmlspecialchars($maintenanceMessage) ?>">
                </div>
            </div>

            <!-- Analytics -->
            <div class="setting-group">
                <h6>Tracking & Analytics</h6>
                <div class="mb-3">
                    <label class="form-label">Google Analytics ID</label>
                    <input type="text" name="google_analytics" class="form-control" value="<?= htmlspecialchars($googleAnalytics) ?>" placeholder="G-XXXXXXXXXX">
                </div>
                <div class="mb-3">
                    <label class="form-label">Facebook Pixel ID</label>
                    <input type="text" name="facebook_pixel" class="form-control" value="<?= htmlspecialchars($facebookPixel) ?>" placeholder="XXXXXXXXXXXXX">
                </div>
            </div>

            <!-- SMTP -->
            <div class="setting-group">
                <h6>SMTP Settings (Email)</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($smtpHost) ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Port</label>
                        <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($smtpPort) ?>" placeholder="587">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Encryption</label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="tls" <?= $smtpEncryption == 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $smtpEncryption == 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= $smtpEncryption == 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($smtpUsername) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($smtpPassword) ?>" placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom mt-3">
                <i class="fas fa-save me-2"></i> Save All Settings
            </button>
        </form>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
</script>
</body>
</html>