<?php
// admin/pages/industries.php - Industries Management

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireLogin();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = sanitizeInput($_POST['name'] ?? '');
    $customIcon = sanitizeInput($_POST['custom_icon'] ?? '');
    $icon = !empty($customIcon) ? $customIcon : sanitizeInput($_POST['icon'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $order = intval($_POST['order'] ?? 0);

    $existingIndustry = null;
    if ($id > 0) {
        $existingIndustry = getIndustry($id);
    }

    $imagePath = $existingIndustry['image'] ?? null;
    if (isset($_FILES['image']) && is_array($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $uploadResult = uploadIndustryImage($_FILES['image']);
        if (!$uploadResult['success']) {
            $error = $uploadResult['error'];
        } else {
            $imagePath = $uploadResult['path'];
        }
    }

    if (empty($name)) {
        $error = 'Industry name is required';
    } elseif (empty($error)) {
        $data = [
            'name' => $name,
            'icon' => $icon,
            'image' => $imagePath,
            'status' => $status,
            'order' => $order
        ];

        if ($id > 0) {
            if (updateIndustry($id, $data)) {
                $message = 'Industry updated successfully!';
                logActivity($_SESSION['user_id'], 'update_industry', 'Updated industry: ' . $name);
                $existingIndustry = null;
                // Redirect to clear edit query param
                header('Location: industries.php?msg=updated');
                exit;
            } else {
                $error = 'Failed to update industry';
            }
        } else {
            if (createIndustry($data)) {
                $message = 'Industry created successfully!';
                logActivity($_SESSION['user_id'], 'create_industry', 'Created industry: ' . $name);
                header('Location: industries.php?msg=created');
                exit;
            } else {
                $error = 'Failed to create industry';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (deleteIndustry($id)) {
        logActivity($_SESSION['user_id'], 'delete_industry', 'Deleted industry ID: ' . $id);
        header('Location: industries.php?msg=deleted');
        exit;
    } else {
        $error = 'Failed to delete industry';
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $message = 'Industry created successfully!';
    elseif ($_GET['msg'] === 'updated') $message = 'Industry updated successfully!';
    elseif ($_GET['msg'] === 'deleted') $message = 'Industry deleted successfully!';
}

// Get all industries for admin list (active and inactive)
$industries = getIndustries(false);

// Get single industry for editing
$editIndustry = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $editIndustry = getIndustry($id);
}

// Common FontAwesome icon list for industries
$icons = [
    'fas fa-building', 'fas fa-heart-pulse', 'fas fa-graduation-cap',
    'fas fa-cart-shopping', 'fas fa-car', 'fas fa-utensils',
    'fas fa-landmark', 'fas fa-vote-yea', 'fas fa-box-open',
    'fas fa-coins', 'fas fa-microchip', 'fas fa-industry',
    'fas fa-film', 'fas fa-hand-holding-heart', 'fas fa-leaf',
    'fas fa-broadcast-tower', 'fas fa-tractor', 'fas fa-gavel',
    'fas fa-plane', 'fas fa-truck', 'fas fa-shopping-bag',
    'fas fa-briefcase', 'fas fa-stethoscope', 'fas fa-hotel',
    'fas fa-shield-alt', 'fas fa-tshirt', 'fas fa-gem',
    'fas fa-laptop-code', 'fas fa-bolt', 'fas fa-chart-pie'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industries - <?= siteName() ?></title>
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
        .table th { font-weight: 600; color: #6b7280; border-top: none; }
        .icon-preview {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(230,26,39,0.06);
            border-radius: 8px;
            color: var(--primary);
            font-size: 1.2rem;
        }
        .industry-image-preview {
            width: 50px; height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .cms-icon-picker {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            background: #fff;
            margin-top: 4px;
        }
        .cms-icon-search-wrap {
            position: relative;
            margin-bottom: 10px;
        }
        .cms-icon-search-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .cms-icon-search-wrap input {
            width: 100%;
            padding: 8px 12px 8px 34px !important;
            font-size: 0.85rem !important;
            border-radius: 6px !important;
            border: 1px solid #e2e8f0;
        }
        .cms-icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            max-height: 180px;
            overflow-y: auto;
            padding: 4px 2px;
        }
        .cms-icon-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 4px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
            user-select: none;
        }
        .cms-icon-cell i {
            font-size: 1.15rem;
            margin-bottom: 4px;
            color: #334155;
            transition: color 0.15s;
        }
        .cms-icon-cell span {
            font-size: 0.65rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .cms-icon-cell:hover {
            border-color: var(--primary);
            background: rgba(230, 26, 39, 0.05);
        }
        .cms-icon-cell:hover i {
            color: var(--primary);
        }
        .cms-icon-cell.selected {
            border-color: var(--primary);
            background: #fef2f2;
            box-shadow: 0 0 0 2px rgba(230, 26, 39, 0.2);
        }
        .cms-icon-cell.selected i {
            color: var(--primary);
        }
        .cms-icon-cell.selected span {
            color: var(--primary);
            font-weight: 600;
        }
        .cms-icon-preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
        }
        .cms-icon-preview-badge i {
            font-size: 1.25rem;
            color: var(--primary);
        }
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
    <a href="industries.php" class="nav-item active"><i class="fas fa-industry"></i> Industries</a>
    <a href="portfolio.php" class="nav-item"><i class="fas fa-briefcase"></i> Portfolio</a>
    <a href="testimonials.php" class="nav-item"><i class="fas fa-comment"></i> Testimonials</a>
    <a href="faqs.php" class="nav-item"><i class="fas fa-question-circle"></i> FAQs</a>

    <div class="nav-section">System</div>
    <a href="contacts.php" class="nav-item"><i class="fas fa-envelope"></i> Contacts</a>
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
    <a href="seo.php" class="nav-item"><i class="fas fa-search"></i> SEO</a>
    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php" class="nav-item" style="color:rgba(255,255,255,0.3);"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="overlay" id="overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Industries</h1>
        </div>
        <div class="user-info">
            <span style="font-size:0.9rem;color:#6b7280;"><?= date('l, F j, Y') ?></span>
            <div class="avatar"><?= substr($user['full_name'] ?? $user['username'], 0, 2) ?></div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="content-card">
        <h5><?= $editIndustry ? 'Edit Industry' : 'Add New Industry' ?></h5>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editIndustry): ?>
                <input type="hidden" name="id" value="<?= $editIndustry['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Industry Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Real Estate &amp; Construction" value="<?= $editIndustry ? htmlspecialchars($editIndustry['name']) : '' ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="order" class="form-control" value="<?= $editIndustry ? $editIndustry['order'] : 0 ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Select Industry Icon</label>
                    <input type="hidden" name="icon" id="admin_industry_icon" value="<?= $editIndustry ? htmlspecialchars($editIndustry['icon'] ?? 'fas fa-building') : 'fas fa-building' ?>">
                    <div class="cms-icon-preview-badge" id="admin_industry_icon_preview">
                        <i class="<?= $editIndustry && !empty($editIndustry['icon']) ? htmlspecialchars($editIndustry['icon']) : 'fas fa-building' ?>"></i>
                        <span>Selected: <strong><?= $editIndustry && !empty($editIndustry['icon']) ? htmlspecialchars($editIndustry['icon']) : 'Real Estate / Building' ?></strong></span>
                    </div>
                    <div class="cms-icon-picker">
                        <div class="cms-icon-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search icons (e.g. bank, hospital, retail, car, food)..." oninput="filterAdminIconPicker(this)">
                        </div>
                        <div class="cms-icon-grid" id="admin_industry_grid"></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Optional Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if ($editIndustry && !empty($editIndustry['image'])): ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img src="<?= SITE_URL . 'uploads/' . htmlspecialchars($editIndustry['image']) ?>" alt="Industry Image" class="industry-image-preview">
                            <small class="text-muted">Current image</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="status" class="form-check-input" id="status" <?= !$editIndustry || $editIndustry['status'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="status">Active (Visible on frontend)</label>
            </div>
            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-save me-2"></i> <?= $editIndustry ? 'Update Industry' : 'Save Industry' ?>
            </button>
            <?php if ($editIndustry): ?>
                <a href="industries.php" class="btn btn-secondary ms-2">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Industries List -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-industry me-2"></i> All Industries (<?= count($industries) ?>)</h5>
            <?php if ($editIndustry): ?>
                <a href="industries.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i> Add New Industry</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 70px;">Icon</th>
                        <th>Industry Name</th>
                        <th style="width: 120px;">Display Order</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($industries as $ind): ?>
                        <tr>
                            <td>
                                <?php if (!empty($ind['image'])): ?>
                                    <img src="<?= SITE_URL . 'uploads/' . htmlspecialchars($ind['image']) ?>" alt="<?= htmlspecialchars($ind['name']) ?>" class="industry-image-preview">
                                <?php elseif (!empty($ind['icon'])): ?>
                                    <div class="icon-preview"><i class="<?= htmlspecialchars($ind['icon']) ?>"></i></div>
                                <?php else: ?>
                                    <div class="icon-preview" style="background:rgba(0,0,0,0.04);color:#6b7280;font-size:0.8rem;"><i class="fas fa-building"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($ind['name']) ?></strong>
                                <?php if (!empty($ind['icon'])): ?>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;"><code><?= htmlspecialchars($ind['icon']) ?></code></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $ind['order'] ?></span></td>
                            <td>
                                <span class="badge bg-<?= $ind['status'] ? 'success' : 'danger' ?>">
                                    <?= $ind['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <a href="industries.php?edit=<?= $ind['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i> Edit</a>
                                <a href="industries.php?delete=<?= $ind['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete \'<?= addslashes(htmlspecialchars($ind['name'])) ?>\'?')"><i class="fas fa-trash me-1"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($industries)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No industries found. Use the form above to add one.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const iconSelect = document.getElementById('iconSelect');
    const customIconInput = document.getElementById('customIconInput');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    const CMS_ICONS = [
        { class: 'fas fa-building', name: 'Real Estate / Building', tags: 'building property real estate architecture tower office' },
        { class: 'fas fa-heart-pulse', name: 'Healthcare / Medical', tags: 'healthcare medical hospital doctor clinic health heart pulse' },
        { class: 'fas fa-graduation-cap', name: 'Education', tags: 'education school college university student study degree' },
        { class: 'fas fa-cart-shopping', name: 'Retail / Shopping', tags: 'retail shopping store mall market buy ecommerce cart' },
        { class: 'fas fa-car', name: 'Automotive', tags: 'automotive car vehicle drive motor automobile' },
        { class: 'fas fa-utensils', name: 'Food & Dining', tags: 'food restaurant dining cafe eatery drink chef' },
        { class: 'fas fa-coins', name: 'Finance & Banking', tags: 'finance banking money investment wealth bank coins' },
        { class: 'fas fa-landmark', name: 'Government & Public', tags: 'government landmark civic public law authority' },
        { class: 'fas fa-laptop-code', name: 'IT & Tech', tags: 'it tech technology software computer code laptop digital' },
        { class: 'fas fa-film', name: 'Entertainment & Media', tags: 'entertainment film cinema movie video media' },
        { class: 'fas fa-industry', name: 'Manufacturing', tags: 'manufacturing factory industry production plant industrial' },
        { class: 'fas fa-tshirt', name: 'Fashion & Apparel', tags: 'fashion apparel clothing style garment textile' },
        { class: 'fas fa-plane', name: 'Travel & Tourism', tags: 'travel tourism flight plane holiday vacation tour' },
        { class: 'fas fa-hotel', name: 'Hospitality', tags: 'hospitality hotel resort lodge stay accommodation' },
        { class: 'fas fa-truck', name: 'Logistics & Supply', tags: 'logistics supply shipping transport delivery freight' },
        { class: 'fas fa-bullhorn', name: 'Marketing & Ads', tags: 'marketing advertising promotion announcement megaphone bullhorn' },
        { class: 'fas fa-rectangle-ad', name: 'Billboard / Ads', tags: 'billboard advertisement banner hoarding display ad' },
        { class: 'fas fa-tv', name: 'Broadcasting & TV', tags: 'broadcasting tv television stream display screen' },
        { class: 'fas fa-newspaper', name: 'Print Media & News', tags: 'print media news newspaper press journal' },
        { class: 'fas fa-gift', name: 'Gifting & Events', tags: 'gifting gifts present reward corporate events' },
        { class: 'fas fa-gem', name: 'Jewelry & Luxury', tags: 'jewelry luxury premium diamond gem elegance' },
        { class: 'fas fa-leaf', name: 'Agriculture & Nature', tags: 'agriculture farming organic nature green leaf environment' },
        { class: 'fas fa-bolt', name: 'Energy & Power', tags: 'energy power electricity solar utility lightning bolt' },
        { class: 'fas fa-hand-holding-heart', name: 'NGO & Social Cause', tags: 'ngo charity social welfare help care donate non-profit' },
        { class: 'fas fa-shield-halved', name: 'Security & Legal', tags: 'security protection legal safety defense insurance' },
        { class: 'fas fa-briefcase', name: 'Corporate & Consulting', tags: 'corporate business consulting services management work' },
        { class: 'fas fa-chart-line', name: 'Growth & Analytics', tags: 'growth analytics statistics performance success sales chart' },
        { class: 'fas fa-eye', name: 'Visibility & Readership', tags: 'visibility view look impression see eye awareness' },
        { class: 'fas fa-rocket', name: 'Speed & Scale', tags: 'speed rocket launch scale boost fast' },
        { class: 'fas fa-clock', name: '24/7 & Timely', tags: 'clock time hour 24/7 round the clock duration' },
        { class: 'fas fa-star', name: 'Quality & Rating', tags: 'quality star rating review excellence premium' },
        { class: 'fas fa-lightbulb', name: 'Innovation & Creative', tags: 'innovation creative idea lightbulb design solution' },
        { class: 'fas fa-mobile-screen', name: 'Mobile & Apps', tags: 'mobile phone smartphone screen app telecom' },
        { class: 'fas fa-wifi', name: 'Telecom & Network', tags: 'wifi internet telecom connectivity network signal' },
        { class: 'fas fa-award', name: 'Awards & Recognition', tags: 'award trophy honor badge distinction winner' },
        { class: 'fas fa-handshake', name: 'Partnership', tags: 'partnership deal agreement trust collaboration client' }
    ];

    function initAdminIconPicker() {
        const grid = document.getElementById('admin_industry_grid');
        const input = document.getElementById('admin_industry_icon');
        const preview = document.getElementById('admin_industry_icon_preview');
        if (!grid || !input || !preview) return;

        const currentVal = input.value || 'fas fa-building';
        grid.innerHTML = '';

        CMS_ICONS.forEach(item => {
            const cell = document.createElement('div');
            cell.className = 'cms-icon-cell' + (item.class === currentVal ? ' selected' : '');
            cell.dataset.class = item.class;
            cell.dataset.name = item.name;
            cell.dataset.tags = item.tags.toLowerCase();
            cell.innerHTML = `<i class="${item.class}"></i><span>${item.name.split('/')[0].trim()}</span>`;
            cell.onclick = function () {
                grid.querySelectorAll('.cms-icon-cell').forEach(c => c.classList.remove('selected'));
                cell.classList.add('selected');
                input.value = item.class;
                preview.innerHTML = `<i class="${item.class}"></i><span>Selected: <strong>${item.name}</strong></span>`;
            };
            grid.appendChild(cell);
        });

        const matched = CMS_ICONS.find(i => i.class === currentVal) || { class: currentVal, name: currentVal };
        preview.innerHTML = `<i class="${matched.class}"></i><span>Selected: <strong>${matched.name}</strong></span>`;
    }

    function filterAdminIconPicker(searchInput) {
        const query = (searchInput.value || '').toLowerCase().trim();
        const grid = document.getElementById('admin_industry_grid');
        if (!grid) return;
        grid.querySelectorAll('.cms-icon-cell').forEach(cell => {
            const text = (cell.dataset.name + ' ' + cell.dataset.tags + ' ' + cell.dataset.class).toLowerCase();
            cell.style.display = (!query || text.includes(query)) ? 'flex' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', initAdminIconPicker);
</script>
</body>
</html>
