<?php
// admin/pages/portfolio.php - Portfolio Management

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
    $category = sanitizeInput($_POST['category'] ?? '');
    $image = sanitizeInput($_POST['image'] ?? '');
    $link = sanitizeInput($_POST['link'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $order = intval($_POST['order'] ?? 0);

    $existingItem = null;
    if ($id > 0) {
        $stmt = db()->prepare("SELECT * FROM portfolio WHERE id = ?");
        $stmt->execute([$id]);
        $existingItem = $stmt->fetch();
    }

    $imagePath = !empty($image) ? $image : ($existingItem['image'] ?? null);

    if (isset($_FILES['image_upload']) && is_array($_FILES['image_upload']) && !empty($_FILES['image_upload']['name'])) {
        $uploadResult = uploadPortfolioImage($_FILES['image_upload']);
        if (!$uploadResult['success']) {
            $error = $uploadResult['error'];
        } else {
            $imagePath = $uploadResult['path'];
        }
    }

    if (empty($title)) {
        $error = 'Title is required';
    } elseif (empty($imagePath)) {
        $error = 'Image is required';
    } elseif (empty($error)) {
        $data = [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'image' => $imagePath,
            'link' => $link,
            'status' => $status,
            'order' => $order
        ];

        if ($id > 0) {
            if (updatePortfolio($id, $data)) {
                $message = 'Portfolio item updated successfully!';
                logActivity($_SESSION['user_id'], 'update_portfolio', 'Updated portfolio: ' . $title);
            } else {
                $error = 'Failed to update portfolio item';
            }
        } else {
            if (createPortfolio($data)) {
                $message = 'Portfolio item created successfully!';
                logActivity($_SESSION['user_id'], 'create_portfolio', 'Created portfolio: ' . $title);
            } else {
                $error = 'Failed to create portfolio item';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (deletePortfolio($id)) {
        $message = 'Portfolio item deleted successfully!';
        logActivity($_SESSION['user_id'], 'delete_portfolio', 'Deleted portfolio ID: ' . $id);
    } else {
        $error = 'Failed to delete portfolio item';
    }
}

// Get portfolio items
$portfolio = getPortfolio();

// Get single item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = db()->prepare("SELECT * FROM portfolio WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Get media files for image selection
$mediaFiles = getMedia();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - <?= siteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Same sidebar styles as services.php */
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
        .image-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f2f5;
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
        .media-select-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid rgba(0,0,0,0.04);
            border-radius: 8px;
        }
        .media-select-item {
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .media-select-item:hover {
            border-color: var(--primary);
        }
        .media-select-item.selected {
            border-color: var(--primary);
            background: rgba(230,26,39,0.05);
        }
        .media-select-item img {
            width: 100%;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
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
    <a href="portfolio.php" class="nav-item active"><i class="fas fa-briefcase"></i> Portfolio</a>
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
            <h1>Portfolio</h1>
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
        <h5><?= $editItem ? 'Edit Portfolio Item' : 'Add New Portfolio Item' ?></h5>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= $editItem ? htmlspecialchars($editItem['title']) : '' ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="Outdoor, Transit, etc." value="<?= $editItem ? htmlspecialchars($editItem['category']) : '' ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Order</label>
                    <input type="number" name="order" class="form-control" value="<?= $editItem ? $editItem['order'] : 0 ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?= $editItem ? htmlspecialchars($editItem['description']) : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image_upload" class="form-control" accept="image/*">
                <small class="text-muted">Upload an image directly, or choose a filename from the media library below.</small>
                <input type="text" name="image" id="imageInput" class="form-control mt-2" value="<?= $editItem ? htmlspecialchars($editItem['image']) : '' ?>" placeholder="Existing image filename or uploaded path">
                <?php if ($editItem && !empty($editItem['image'])): ?>
                    <?php
                        $currentImageValue = $editItem['image'];
                        $currentImageSrc = '';
                        if (preg_match('/^https?:\/\//i', $currentImageValue)) {
                            $currentImageSrc = $currentImageValue;
                        } elseif (strpos($currentImageValue, 'uploads/') === 0) {
                            $currentImageSrc = SITE_URL . $currentImageValue;
                        } elseif (strpos($currentImageValue, 'images/') === 0) {
                            $currentImageSrc = SITE_URL . 'uploads/' . $currentImageValue;
                        } else {
                            $currentImageSrc = SITE_URL . 'uploads/images/' . $currentImageValue;
                        }
                    ?>
                    <div class="mt-2">
                        <img src="<?= htmlspecialchars($currentImageSrc) ?>" class="image-preview" alt="Current portfolio image">
                    </div>
                <?php endif; ?>
                <?php if ($mediaFiles): ?>
                    <div class="media-select-grid mt-2">
                        <?php foreach ($mediaFiles as $media): ?>
                            <?php if (strpos($media['mime_type'], 'image/') === 0): ?>
                                <div class="media-select-item <?= $editItem && $editItem['image'] == $media['filename'] ? 'selected' : '' ?>" onclick="selectImage('<?= $media['filename'] ?>')">
                                    <img src="<?= SITE_URL ?>uploads/<?= $media['file_path'] ?>" alt="<?= htmlspecialchars($media['original_name']) ?>">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Link (optional)</label>
                <input type="text" name="link" class="form-control" placeholder="https://example.com" value="<?= $editItem ? htmlspecialchars($editItem['link']) : '' ?>">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="status" class="form-check-input" id="status" <?= $editItem && $editItem['status'] ? 'checked' : 'checked' ?>>
                <label class="form-check-label" for="status">Active</label>
            </div>
            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-save me-2"></i> <?= $editItem ? 'Update' : 'Save' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="portfolio.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Portfolio List -->
    <div class="content-card">
        <h5><i class="fas fa-list me-2"></i> All Portfolio Items</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($portfolio as $item): ?>
                        <tr>
                            <td>
                                <?php
                                    $portfolioImageValue = $item['image'] ?? '';
                                    $portfolioImageSrc = '';
                                    if (!empty($portfolioImageValue)) {
                                        if (preg_match('/^https?:\/\//i', $portfolioImageValue)) {
                                            $portfolioImageSrc = $portfolioImageValue;
                                        } elseif (strpos($portfolioImageValue, 'uploads/') === 0) {
                                            $portfolioImageSrc = SITE_URL . $portfolioImageValue;
                                        } elseif (strpos($portfolioImageValue, 'images/') === 0) {
                                            $portfolioImageSrc = SITE_URL . 'uploads/' . $portfolioImageValue;
                                        } else {
                                            $portfolioImageSrc = SITE_URL . 'uploads/images/' . $portfolioImageValue;
                                        }
                                    }
                                ?>
                                <?php if (!empty($portfolioImageSrc)): ?>
                                    <img src="<?= htmlspecialchars($portfolioImageSrc) ?>" class="image-preview" alt="<?= htmlspecialchars($item['title']) ?>">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                            <td><?= htmlspecialchars($item['category'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $item['status'] ? 'success' : 'danger' ?>">
                                    <?= $item['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <a href="portfolio.php?edit=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="portfolio.php?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($portfolio)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No portfolio items found.</td></tr>
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

    function selectImage(filename) {
        document.getElementById('imageInput').value = filename;
        document.querySelectorAll('.media-select-item').forEach(el => el.classList.remove('selected'));
        event.target.closest('.media-select-item').classList.add('selected');
    }
</script>
</body>
</html>