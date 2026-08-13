<?php
// admin/pages/content.php - Content Management (Plain Text Only)

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
    $content = sanitizeInput($_POST['content'] ?? ''); // Plain text only - no HTML
    $slug = sanitizeInput($_POST['slug'] ?? '');
    $section = sanitizeInput($_POST['section'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $imagePath = null;

    if (empty($title) || empty($slug)) {
        $error = 'Title and slug are required';
    } else {
        if ($id > 0) {
            $existingStmt = db()->prepare("SELECT image FROM content_blocks WHERE id = ?");
            $existingStmt->execute([$id]);
            $existingContent = $existingStmt->fetch();
            $imagePath = $existingContent['image'] ?? null;
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'The image upload failed. Please try again.';
            } else {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['image_file']['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    $error = 'Only JPG, PNG, WebP, and GIF images are allowed.';
                } else {
                    $uploadDir = UPLOAD_DIR . 'images/content/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['image_file']['name']));
                    $targetPath = $uploadDir . $fileName;

                    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                        $error = 'The image could not be saved. Please try again.';
                    } else {
                        $imagePath = 'images/content/' . $fileName;
                    }
                }
            }
        }

        if (empty($error)) {
            $data = [
                'title' => $title,
                'content' => $content, // Plain text only
                'slug' => $slug,
                'section' => $section,
                'image' => $imagePath,
                'status' => $status
            ];

            if ($id > 0) {
                if (updateContentBlock($id, $data)) {
                    $message = 'Content updated successfully!';
                    logActivity($_SESSION['user_id'], 'update_content', 'Updated content block: ' . $slug);
                } else {
                    $error = 'Failed to update content';
                }
            } else {
                // Insert new content block
                try {
                    $stmt = db()->prepare("INSERT INTO content_blocks (section, slug, title, content, image, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$section, $slug, $title, $content, $imagePath, $status]);
                    $message = 'Content created successfully!';
                    logActivity($_SESSION['user_id'], 'create_content', 'Created content block: ' . $slug);
                } catch (PDOException $e) {
                    $error = 'Failed to create content: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = db()->prepare("DELETE FROM content_blocks WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Content deleted successfully!';
        logActivity($_SESSION['user_id'], 'delete_content', 'Deleted content block ID: ' . $id);
    } catch (PDOException $e) {
        $error = 'Failed to delete content';
    }
}

// Get content blocks
$contentBlocks = getContentBlocks();
$sections = db()->query("SELECT DISTINCT section FROM content_blocks ORDER BY section")->fetchAll();

// Get single content for editing
$editContent = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = db()->prepare("SELECT * FROM content_blocks WHERE id = ?");
    $stmt->execute([$id]);
    $editContent = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - <?= siteName() ?></title>
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
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(230,26,39,0.1);
        }
        .table th { font-weight: 600; color: #6b7280; border-top: none; }
        .badge-section { 
            background: rgba(230,26,39,0.08); 
            color: var(--primary); 
            padding: 4px 12px; 
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .content-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.9rem;
            color: #6b7280;
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }
        .content-image-preview {
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.08);
            background: #f8f9fa;
        }
        .content-list-image {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.08);
            background: #f8f9fa;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .action-buttons .btn {
            margin: 0;
        }
        .content-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(transparent, #f8f9fa);
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
        .info-box {
            background: #e8f0fe;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .info-box i {
            color: #1a73e8;
            font-size: 1.2rem;
            margin-top: 2px;
        }
        .info-box .text {
            font-size: 0.85rem;
            color: #1967d2;
        }
        .info-box .text strong {
            display: block;
            font-size: 0.9rem;
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
    <a href="content.php" class="nav-item active"><i class="fas fa-file-alt"></i> Content</a>
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
    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php" class="nav-item" style="color:rgba(255,255,255,0.3);"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="overlay" id="overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Content Management</h1>
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

    <!-- Info Box -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <div class="text">
            <strong>Plain Text Only</strong>
            You can only edit the text content. HTML tags are not allowed. The styling will remain consistent across the website.
        </div>
    </div>

    <!-- Add/Edit Form -->
    <div class="content-card">
        <h5><?= $editContent ? 'Edit Content' : 'Add New Content' ?></h5>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editContent): ?>
                <input type="hidden" name="id" value="<?= $editContent['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Section <span class="text-muted">(category)</span></label>
                    <input type="text" name="section" class="form-control" value="<?= $editContent ? htmlspecialchars($editContent['section']) : '' ?>" required>
                    <small class="text-muted">e.g., hero, about, services, etc.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Slug <span class="text-muted">(unique identifier)</span></label>
                    <input type="text" name="slug" class="form-control" value="<?= $editContent ? htmlspecialchars($editContent['slug']) : '' ?>" required>
                    <small class="text-muted">e.g., hero_title, about_content</small>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= $editContent ? htmlspecialchars($editContent['title']) : '' ?>" required>
                <small class="text-muted">Display title for this content block</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Content <span class="text-muted">(plain text only)</span></label>
                <textarea name="content" id="contentEditor" class="form-control" rows="6" placeholder="Enter your content here. HTML tags will be removed."><?= $editContent ? htmlspecialchars($editContent['content']) : '' ?></textarea>
                <small class="text-muted">
                    <i class="fas fa-exclamation-triangle text-warning"></i> 
                    Only plain text is allowed. No HTML, CSS, or JavaScript.
                </small>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-muted">Upload a JPG, PNG, WebP, or GIF image. Leave empty to keep the current image.</small>
                <?php if ($editContent && !empty($editContent['image'])): ?>
                    <div class="mt-2">
                        <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($editContent['image']) ?>" alt="Current image" class="content-image-preview">
                        <div class="small text-muted mt-1">Current image</div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="status" class="form-check-input" id="status" <?= $editContent && $editContent['status'] ? 'checked' : 'checked' ?>>
                <label class="form-check-label" for="status">Active</label>
            </div>
            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-save me-2"></i> <?= $editContent ? 'Update' : 'Save' ?>
            </button>
            <?php if ($editContent): ?>
                <a href="content.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Content List -->
    <div class="content-card">
        <h5><i class="fas fa-list me-2"></i> All Content Blocks</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Section</th>
                        <th>Slug</th>
                        <th>Title</th>
                        <th>Image</th>
                        <th>Content Preview</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contentBlocks as $block): ?>
                        <tr>
                            <td><?= $block['id'] ?></td>
                            <td><span class="badge-section"><?= htmlspecialchars($block['section']) ?></span></td>
                            <td><code><?= htmlspecialchars($block['slug']) ?></code></td>
                            <td><?= htmlspecialchars($block['title']) ?></td>
                            <td>
                                <?php if (!empty($block['image'])): ?>
                                    <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($block['image']) ?>" alt="<?= htmlspecialchars($block['title']) ?>" class="content-list-image">
                                <?php else: ?>
                                    <span class="text-muted small">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="content-preview">
                                    <?= htmlspecialchars(substr($block['content'], 0, 80)) ?>...
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $block['status'] ? 'success' : 'danger' ?>">
                                    <?= $block['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="content.php?edit=<?= $block['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="content.php?delete=<?= $block['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this content?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contentBlocks)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No content blocks found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Sidebar toggle
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

    // Prevent HTML tags in content field
    document.getElementById('contentEditor').addEventListener('input', function() {
        // Remove any HTML tags
        this.value = this.value.replace(/<[^>]*>/g, '');
    });

    // Also prevent on paste
    document.getElementById('contentEditor').addEventListener('paste', function(e) {
        setTimeout(() => {
            this.value = this.value.replace(/<[^>]*>/g, '');
        }, 10);
    });
</script>
</body>
</html>