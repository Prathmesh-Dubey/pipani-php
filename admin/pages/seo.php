<?php
// admin/pages/seo.php - SEO Management

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireLogin();

$message = '';
$error = '';

$page = isset($_GET['page']) ? sanitizeInput($_GET['page']) : 'home';

// Get SEO data
$seoData = getSeo($page);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'keywords' => sanitizeInput($_POST['keywords'] ?? ''),
        'canonical' => sanitizeInput($_POST['canonical'] ?? ''),
        'og_title' => sanitizeInput($_POST['og_title'] ?? ''),
        'og_description' => sanitizeInput($_POST['og_description'] ?? ''),
        'og_image' => sanitizeInput($_POST['og_image'] ?? ''),
        'twitter_title' => sanitizeInput($_POST['twitter_title'] ?? ''),
        'twitter_description' => sanitizeInput($_POST['twitter_description'] ?? ''),
        'twitter_image' => sanitizeInput($_POST['twitter_image'] ?? ''),
        'schema_json' => $_POST['schema_json'] ?? ''
    ];

    if (updateSeo($page, $data)) {
        $message = 'SEO settings updated successfully!';
        logActivity($_SESSION['user_id'], 'update_seo', 'Updated SEO for page: ' . $page);
        $seoData = getSeo($page);
    } else {
        $error = 'Failed to update SEO settings';
    }
}

// Get all pages for dropdown
$pages = ['home', 'about', 'services', 'portfolio', 'contact'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - <?= siteName() ?></title>
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
        .seo-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }
        .seo-preview .title { color: #1a0dab; font-size: 1.2rem; font-weight: 500; }
        .seo-preview .url { color: #006621; font-size: 0.85rem; }
        .seo-preview .desc { color: #545454; font-size: 0.85rem; }
        textarea.form-control { font-family: 'Courier New', monospace; }
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
    <a href="seo.php" class="nav-item active"><i class="fas fa-search"></i> SEO</a>
    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php" class="nav-item" style="color:rgba(255,255,255,0.3);"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="overlay" id="overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>SEO Manager</h1>
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

    <!-- Page Selector -->
    <div class="content-card">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Select Page</label>
                <select name="page" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($pages as $p): ?>
                        <option value="<?= $p ?>" <?= $page == $p ? 'selected' : '' ?>>
                            <?= ucfirst($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary-custom">Load Page</button>
            </div>
        </form>
    </div>

    <!-- SEO Form -->
    <div class="content-card">
        <h5><i class="fas fa-edit me-2"></i> SEO Settings for: <strong><?= ucfirst($page) ?></strong></h5>
        <form method="POST">
            <!-- Basic SEO -->
            <div class="mb-3">
                <label class="form-label">Meta Title</label>
                <input type="text" name="title" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['title']) : '' ?>" placeholder="Page title (60-70 characters)">
                <small class="text-muted">Recommended: 60-70 characters</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Meta Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Page description (150-160 characters)"><?= $seoData ? htmlspecialchars($seoData['description']) : '' ?></textarea>
                <small class="text-muted">Recommended: 150-160 characters</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Keywords</label>
                <input type="text" name="keywords" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['keywords']) : '' ?>" placeholder="keyword1, keyword2, keyword3">
                <small class="text-muted">Comma separated keywords</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Canonical URL</label>
                <input type="text" name="canonical" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['canonical']) : '' ?>" placeholder="https://example.com/page">
            </div>

            <hr>

            <!-- Open Graph -->
            <h6 class="mt-4">Open Graph (Social Media)</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">OG Title</label>
                    <input type="text" name="og_title" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['og_title']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">OG Image URL</label>
                    <input type="text" name="og_image" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['og_image']) : '' ?>" placeholder="https://example.com/image.jpg">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">OG Description</label>
                <textarea name="og_description" class="form-control" rows="2"><?= $seoData ? htmlspecialchars($seoData['og_description']) : '' ?></textarea>
            </div>

            <hr>

            <!-- Twitter Cards -->
            <h6 class="mt-4">Twitter Cards</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Twitter Title</label>
                    <input type="text" name="twitter_title" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['twitter_title']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Twitter Image URL</label>
                    <input type="text" name="twitter_image" class="form-control" value="<?= $seoData ? htmlspecialchars($seoData['twitter_image']) : '' ?>" placeholder="https://example.com/image.jpg">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Twitter Description</label>
                <textarea name="twitter_description" class="form-control" rows="2"><?= $seoData ? htmlspecialchars($seoData['twitter_description']) : '' ?></textarea>
            </div>

            <hr>

            <!-- Schema JSON -->
            <div class="mb-3">
                <label class="form-label">Schema.org JSON-LD</label>
                <textarea name="schema_json" class="form-control" rows="6" placeholder='{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Your Site",
  "url": "https://example.com"
}'><?= $seoData ? htmlspecialchars($seoData['schema_json']) : '' ?></textarea>
                <small class="text-muted">Valid JSON-LD schema markup</small>
            </div>

            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-save me-2"></i> Save SEO Settings
            </button>
        </form>

        <!-- Preview -->
        <div class="seo-preview">
            <h6>Google Search Preview</h6>
            <?php
            $title = $seoData ? $seoData['title'] : '';
            $desc = $seoData ? $seoData['description'] : '';
            $url = $seoData ? $seoData['canonical'] : SITE_URL . $page;
            ?>
            <div class="title"><?= $title ?: 'Page Title' ?></div>
            <div class="url"><?= $url ?: 'https://example.com/page' ?></div>
            <div class="desc"><?= $desc ?: 'Page description will appear here...' ?></div>
        </div>
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