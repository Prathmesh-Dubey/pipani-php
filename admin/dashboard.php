<?php
// admin/dashboard.php

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$user = Auth::getCurrentUser();

// Get statistics
$contactCount = count(getContacts());
$pageCount = count(getContentBlocks());
$mediaCount = count(getMedia());
$serviceCount = count(getServices());
$industryCount = count(getIndustries());
$portfolioCount = count(getPortfolio());
$faqCount = count(getFaqs());

// Recent contacts
$recentContacts = getContacts(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= siteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #E61A27; --primary-dark: #C4101B; --sidebar-width: 280px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        /* Sidebar */
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
        .sidebar .logo {
            font-size: 1.6rem;
            font-weight: 800;
            padding: 0 12px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 24px;
        }
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
        .sidebar .nav-item:hover,
        .sidebar .nav-item.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .sidebar .nav-item.active {
            background: rgba(230,26,39,0.15);
            color: var(--primary);
        }
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }
        .sidebar .user-card .name { font-weight: 600; font-size: 0.9rem; }
        .sidebar .user-card .role { font-size: 0.75rem; color: rgba(255,255,255,0.3); }
        /* Main Content */
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
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        }
        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .stat-card .number { font-size: 2rem; font-weight: 800; }
        .stat-card .label { color: #6b7280; font-size: 0.85rem; }
        .table-container {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .table-container h5 { font-weight: 700; margin-bottom: 16px; }
        .table th { font-weight: 600; color: #6b7280; border-top: none; }
        .table td { vertical-align: middle; }
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1a1a2e;
            cursor: pointer;
        }
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            .overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            .overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo"><?= siteName() ?><span>.</span></div>

        <div class="user-card">
            <div class="avatar"><?= substr($user['full_name'] ?? $user['username'], 0, 2) ?></div>
            <div>
                <div class="name"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></div>
                <div class="role"><?= $user['role'] ?></div>
            </div>
        </div>

        <div class="nav-section">Main</div>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="pages/content.php" class="nav-item"><i class="fas fa-file-alt"></i> Content</a>
        <a href="pages/media.php" class="nav-item"><i class="fas fa-images"></i> Media</a>
        <a href="pages/menu.php" class="nav-item"><i class="fas fa-bars"></i> Menu</a>

        <div class="nav-section">Content</div>
        <a href="pages/services.php" class="nav-item"><i class="fas fa-concierge-bell"></i> Services</a>
        <a href="pages/industries.php" class="nav-item"><i class="fas fa-industry"></i> Industries</a>
        <a href="pages/portfolio.php" class="nav-item"><i class="fas fa-briefcase"></i> Portfolio</a>
        <a href="pages/testimonials.php" class="nav-item"><i class="fas fa-comment"></i> Testimonials</a>
        <a href="pages/faqs.php" class="nav-item"><i class="fas fa-question-circle"></i> FAQs</a>

        <div class="nav-section">System</div>
        <a href="pages/contacts.php" class="nav-item"><i class="fas fa-envelope"></i> Contacts</a>
        <a href="pages/users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
        <a href="pages/seo.php" class="nav-item"><i class="fas fa-search"></i> SEO</a>
        <a href="pages/settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="logout.php" class="nav-item" style="color:rgba(255,255,255,0.3);"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <h1>Dashboard</h1>
            </div>
            <div class="user-info">
                <div class="d-flex gap-2 me-3">
                    <a href="<?= SITE_URL ?>" class="btn btn-sm btn-outline-dark" style="border-color: rgba(0,0,0,0.1);"><i class="fas fa-edit"></i> Editor Mode</a>
                    <a href="<?= SITE_URL ?>?preview=1" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-eye"></i> User View</a>
                </div>
                <span style="font-size:0.9rem;color:#6b7280;" class="d-none d-md-inline"><?= date('l, F j, Y') ?></span>
                <div class="avatar"><?= substr($user['full_name'] ?? $user['username'], 0, 2) ?></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-4 mt-2">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?= $pageCount ?></div>
                            <div class="label">Content Blocks</div>
                        </div>
                        <div class="icon" style="background:rgba(230,26,39,0.08);color:var(--primary);"><i class="fas fa-file-alt"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?= $mediaCount ?></div>
                            <div class="label">Media Files</div>
                        </div>
                        <div class="icon" style="background:rgba(16,185,129,0.08);color:#10b981;"><i class="fas fa-images"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?= $serviceCount ?></div>
                            <div class="label">Services</div>
                        </div>
                        <div class="icon" style="background:rgba(59,130,246,0.08);color:#3b82f6;"><i class="fas fa-concierge-bell"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?= $contactCount ?></div>
                            <div class="label">Messages</div>
                        </div>
                        <div class="icon" style="background:rgba(245,158,11,0.08);color:#f59e0b;"><i class="fas fa-envelope"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Contacts -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="table-container">
                    <h5><i class="fas fa-envelope me-2"></i> Recent Messages</h5>
                    <?php if ($recentContacts): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentContacts as $contact): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($contact['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($contact['email']) ?></td>
                                            <td><?= htmlspecialchars(substr($contact['message'], 0, 60)) ?>...</td>
                                            <td><?= date('M j, Y', strtotime($contact['created_at'])) ?></td>
                                            <td><span class="badge bg-<?= $contact['status'] == 'read' ? 'secondary' : 'primary' ?>"><?= $contact['status'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No messages yet.</p>
                    <?php endif; ?>
                </div>
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