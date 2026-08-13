<?php
// admin/pages/services.php - Services Management

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
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? '');
    
    $slug = sanitizeInput($_POST['slug'] ?? '');
    if (empty($slug) && !empty($title)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
    $tagline = sanitizeInput($_POST['tagline'] ?? '');
    $how_it_works = sanitizeInput($_POST['how_it_works'] ?? '');
    $formats = json_encode(array_filter(array_map('trim', explode("\n", $_POST['formats'] ?? ''))));
    $target_audience = sanitizeInput($_POST['target_audience'] ?? '');
    $applications = json_encode(array_filter(array_map('trim', explode("\n", $_POST['applications'] ?? ''))));
    $cta_text = sanitizeInput($_POST['cta_text'] ?? '');
    
    $benefits = json_encode(array_filter(array_map('trim', explode("\n", $_POST['benefits'] ?? ''))));
    $status = isset($_POST['status']) ? 1 : 0;
    $order = intval($_POST['order'] ?? 0);

    $existingService = null;
    if ($id > 0) {
        $stmt = db()->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $existingService = $stmt->fetch();
    }

    $imagePath = $existingService['image'] ?? null;
    if (isset($_FILES['image']) && is_array($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $uploadResult = uploadServiceImage($_FILES['image']);
        if (!$uploadResult['success']) {
            $error = $uploadResult['error'];
        } else {
            $imagePath = $uploadResult['path'];
        }
    }
    
    $gallery = $existingService['gallery'] ?? '[]';
    $galleryArray = json_decode($gallery, true) ?: [];
    if (isset($_POST['clear_gallery']) && $_POST['clear_gallery'] == '1') {
        $galleryArray = [];
    }
    if (isset($_FILES['gallery_images'])) {
        $files = $_FILES['gallery_images'];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if (!empty($files['name'][$i])) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    $uploadResult = uploadServiceImage($file);
                    if ($uploadResult['success'] && $uploadResult['path']) {
                        $galleryArray[] = $uploadResult['path'];
                    }
                }
            }
        }
    }
    $galleryEncoded = json_encode($galleryArray);

    if (empty($title)) {
        $error = 'Title is required';
    } elseif (empty($error)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'tagline' => $tagline,
            'description' => $description,
            'icon' => $icon,
            'image' => $imagePath,
            'how_it_works' => $how_it_works,
            'formats' => $formats,
            'benefits' => $benefits,
            'target_audience' => $target_audience,
            'applications' => $applications,
            'gallery' => $galleryEncoded,
            'cta_text' => $cta_text,
            'status' => $status,
            'order' => $order
        ];

        if ($id > 0) {
            if (updateService($id, $data)) {
                $message = 'Service updated successfully!';
                logActivity($_SESSION['user_id'], 'update_service', 'Updated service: ' . $title);
            } else {
                $error = 'Failed to update service';
            }
        } else {
            if (createService($data)) {
                $message = 'Service created successfully!';
                logActivity($_SESSION['user_id'], 'create_service', 'Created service: ' . $title);
            } else {
                $error = 'Failed to create service';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (deleteService($id)) {
        $message = 'Service deleted successfully!';
        logActivity($_SESSION['user_id'], 'delete_service', 'Deleted service ID: ' . $id);
    } else {
        $error = 'Failed to delete service';
    }
}

// Get services
$services = getServices();

// Get single service for editing
$editService = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = db()->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $editService = $stmt->fetch();
}

// Common icon list
$icons = [
    'fas fa-billboard', 'fas fa-bus', 'fas fa-radio', 'fas fa-film',
    'fas fa-store', 'fas fa-newspaper', 'fas fa-paint-brush',
    'fas fa-gift', 'fas fa-cricket-bat-ball', 'fas fa-chart-line',
    'fas fa-users', 'fas fa-mobile-alt', 'fas fa-laptop',
    'fas fa-building', 'fas fa-car', 'fas fa-utensils'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - <?= siteName() ?></title>
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
        .service-image-preview {
            width: 54px; height: 54px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.08);
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
    <a href="services.php" class="nav-item active"><i class="fas fa-concierge-bell"></i> Services</a>
    <a href="industries.php" class="nav-item"><i class="fas fa-industry"></i> Industries</a>
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
            <h1>Services</h1>
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

    <!-- Add/Edit Form -->
    <div class="content-card">
        <h5><?= $editService ? 'Edit Service' : 'Add New Service' ?></h5>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editService): ?>
                <input type="hidden" name="id" value="<?= $editService['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= $editService ? htmlspecialchars($editService['title']) : '' ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Icon</label>
                    <select name="icon" class="form-select">
                        <option value="" <?= $editService && empty($editService['icon']) ? 'selected' : '' ?>>No Icon</option>
                        <?php foreach ($icons as $icon): ?>
                            <option value="<?= $icon ?>" <?= $editService && $editService['icon'] == $icon ? 'selected' : '' ?>><?= $icon ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Order</label>
                    <input type="number" name="order" class="form-control" value="<?= $editService ? $editService['order'] : 0 ?>">
                </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Slug (URL Type)</label>
                    <input type="text" name="slug" class="form-control" value="<?= $editService ? htmlspecialchars($editService['slug'] ?? '') : '' ?>" placeholder="Leave blank to auto-generate">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="tagline" class="form-control" value="<?= $editService ? htmlspecialchars($editService['tagline'] ?? '') : '' ?>" placeholder="e.g. The most effective media">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Overview / Description</label>
                <textarea name="description" class="form-control" rows="3"><?= $editService ? htmlspecialchars($editService['description']) : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">How It Works</label>
                <textarea name="how_it_works" class="form-control" rows="3"><?= $editService ? htmlspecialchars($editService['how_it_works'] ?? '') : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Formats / Types (one per line)</label>
                <textarea name="formats" class="form-control" rows="3"><?= $editService && !empty($editService['formats']) ? implode("\n", json_decode($editService['formats'], true)) : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="text-muted">Upload a JPG, PNG, WebP, GIF, or SVG file. Leave blank to keep the current image.</small>
                <?php if ($editService && !empty($editService['image'])): ?>
                    <div class="mt-2">
                        <img src="<?= SITE_URL . 'uploads/' . htmlspecialchars($editService['image']) ?>" alt="Current service image" class="service-image-preview">
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Benefits (one per line)</label>
                <textarea name="benefits" class="form-control" rows="3" placeholder="71% Read Rate&#10;High Visibility"><?= $editService && !empty($editService['benefits']) ? implode("\n", json_decode($editService['benefits'], true)) : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Target Audience / Reach</label>
                <textarea name="target_audience" class="form-control" rows="3"><?= $editService ? htmlspecialchars($editService['target_audience'] ?? '') : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Applications / Use Cases (one per line)</label>
                <textarea name="applications" class="form-control" rows="3"><?= $editService && !empty($editService['applications']) ? implode("\n", json_decode($editService['applications'], true)) : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Call to Action Text</label>
                <input type="text" name="cta_text" class="form-control" value="<?= $editService ? htmlspecialchars($editService['cta_text'] ?? '') : '' ?>" placeholder="e.g. Enquire about Transit Media">
            </div>
            <div class="mb-3">
                <label class="form-label">Gallery Images</label>
                <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">You can select multiple images to upload.</small>
                <?php 
                if ($editService && !empty($editService['gallery'])) {
                    $galleryImages = json_decode($editService['gallery'], true);
                    if (!empty($galleryImages)) {
                        echo '<div class="mt-2 d-flex flex-wrap gap-2">';
                        foreach ($galleryImages as $img) {
                            echo '<img src="' . SITE_URL . 'uploads/' . htmlspecialchars($img) . '" class="service-image-preview">';
                        }
                        echo '</div>';
                        echo '<div class="mt-2 form-check"><input type="checkbox" name="clear_gallery" value="1" class="form-check-input" id="clear_gallery"><label class="form-check-label text-danger" for="clear_gallery">Clear all gallery images</label></div>';
                    }
                }
                ?>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="status" class="form-check-input" id="status" <?= $editService && $editService['status'] ? 'checked' : 'checked' ?>>
                <label class="form-check-label" for="status">Active</label>
            </div>
            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-save me-2"></i> <?= $editService ? 'Update' : 'Save' ?>
            </button>
            <?php if ($editService): ?>
                <a href="services.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Services List -->
    <div class="content-card">
        <h5><i class="fas fa-list me-2"></i> All Services</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Title</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <?php if (!empty($service['image'])): ?>
                                    <img src="<?= SITE_URL . 'uploads/' . htmlspecialchars($service['image']) ?>" alt="<?= htmlspecialchars($service['title']) ?>" class="service-image-preview">
                                <?php elseif (!empty($service['icon'])): ?>
                                    <div class="icon-preview"><i class="<?= htmlspecialchars($service['icon']) ?>"></i></div>
                                <?php else: ?>
                                    <div class="icon-preview" style="background:rgba(0,0,0,0.04);color:#6b7280;font-size:0.8rem;">No icon</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($service['title']) ?></strong></td>
                            <td><?= $service['order'] ?></td>
                            <td>
                                <span class="badge bg-<?= $service['status'] ? 'success' : 'danger' ?>">
                                    <?= $service['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <a href="services.php?edit=<?= $service['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="services.php?delete=<?= $service['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this service?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No services found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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