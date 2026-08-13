<?php
// admin/pages/menu.php - Menu Management

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireLogin();

$message = '';
$error = '';

// Get the main menu
$menu = db()->query("SELECT * FROM menus WHERE location = 'main' LIMIT 1")->fetch();
if (!$menu) {
    db()->query("INSERT INTO menus (name, location) VALUES ('Main Menu', 'main')");
    $menu = db()->query("SELECT * FROM menus WHERE location = 'main' LIMIT 1")->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $label = sanitizeInput($_POST['label'] ?? '');
                $url = sanitizeInput($_POST['url'] ?? '#');
                $parent_id = intval($_POST['parent_id'] ?? 0);
                $target = sanitizeInput($_POST['target'] ?? '_self');

                if (empty($label)) {
                    $error = 'Label is required';
                } else {
                    $stmt = db()->prepare("INSERT INTO menu_items (menu_id, parent_id, label, url, target, status) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$menu['id'], $parent_id, $label, $url, $target]);
                    $message = 'Menu item added successfully!';
                    logActivity($_SESSION['user_id'], 'add_menu_item', 'Added menu item: ' . $label);
                }
                break;

            case 'update':
                $id = intval($_POST['id']);
                $label = sanitizeInput($_POST['label'] ?? '');
                $url = sanitizeInput($_POST['url'] ?? '#');
                $target = sanitizeInput($_POST['target'] ?? '_self');
                $status = isset($_POST['status']) ? 1 : 0;

                if (empty($label)) {
                    $error = 'Label is required';
                } else {
                    $stmt = db()->prepare("UPDATE menu_items SET label = ?, url = ?, target = ?, status = ? WHERE id = ?");
                    $stmt->execute([$label, $url, $target, $status, $id]);
                    $message = 'Menu item updated successfully!';
                    logActivity($_SESSION['user_id'], 'update_menu_item', 'Updated menu item: ' . $label);
                }
                break;

            case 'delete':
                $id = intval($_POST['id']);
                $stmt = db()->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Menu item deleted successfully!';
                logActivity($_SESSION['user_id'], 'delete_menu_item', 'Deleted menu item ID: ' . $id);
                break;

            case 'reorder':
                $order = json_decode($_POST['order'], true);
                foreach ($order as $position => $id) {
                    $stmt = db()->prepare("UPDATE menu_items SET `order` = ? WHERE id = ?");
                    $stmt->execute([$position, $id]);
                }
                $message = 'Menu order updated successfully!';
                break;
        }
    }
}

// Get menu items
$menuItems = getMenuItems($menu['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Manager - <?= siteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
        .menu-item {
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: grab;
            transition: all 0.3s;
        }
        .menu-item:hover {
            background: #e9ecef;
        }
        .menu-item .drag-icon {
            color: #6b7280;
            margin-right: 12px;
            cursor: grab;
        }
        .menu-item .label {
            font-weight: 500;
            flex: 1;
        }
        .menu-item .url {
            color: #6b7280;
            font-size: 0.85rem;
            margin-right: 16px;
        }
        .menu-item .badge-status {
            margin-right: 12px;
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
        .sortable-ghost {
            opacity: 0.4;
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
    <a href="menu.php" class="nav-item active"><i class="fas fa-bars"></i> Menu</a>

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
            <h1>Menu Manager</h1>
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

    <!-- Add Menu Item -->
    <div class="content-card">
        <h5><i class="fas fa-plus me-2"></i> Add Menu Item</h5>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-control" placeholder="Home" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">URL</label>
                    <input type="text" name="url" class="form-control" placeholder="#home">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Parent</label>
                    <select name="parent_id" class="form-select">
                        <option value="0">Root</option>
                        <?php foreach ($menuItems as $item): ?>
                            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Target</label>
                    <select name="target" class="form-select">
                        <option value="_self">Same Window</option>
                        <option value="_blank">New Window</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-plus me-2"></i> Add Item</button>
        </form>
    </div>

    <!-- Menu List -->
    <div class="content-card">
        <h5><i class="fas fa-list me-2"></i> Menu Items <span class="text-muted" style="font-size:0.85rem;font-weight:400;">(Drag to reorder)</span></h5>
        <form method="POST" id="reorderForm">
            <input type="hidden" name="action" value="reorder">
            <input type="hidden" name="order" id="menuOrder">
        </form>
        <div id="menuList">
            <?php foreach ($menuItems as $item): ?>
                <div class="menu-item" data-id="<?= $item['id'] ?>">
                    <div>
                        <i class="fas fa-grip-lines drag-icon"></i>
                        <span class="label"><?= htmlspecialchars($item['label']) ?></span>
                    </div>
                    <div>
                        <span class="url"><?= htmlspecialchars($item['url']) ?></span>
                        <span class="badge badge-status bg-<?= $item['status'] ? 'success' : 'danger' ?>">
                            <?= $item['status'] ? 'Active' : 'Inactive' ?>
                        </span>
                        <button class="btn btn-sm btn-outline-primary" onclick="editMenu(<?= $item['id'] ?>, '<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>', '<?= $item['target'] ?>', <?= $item['status'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this menu item?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($menuItems)): ?>
                <p class="text-muted">No menu items yet. Add one above.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Menu Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" id="editLabel" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL</label>
                            <input type="text" name="url" id="editUrl" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target</label>
                            <select name="target" id="editTarget" class="form-select">
                                <option value="_self">Same Window</option>
                                <option value="_blank">New Window</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="status" id="editStatus" class="form-check-input" value="1">
                            <label class="form-check-label" for="editStatus">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

    const menuList = document.getElementById('menuList');
    if (menuList) {
        new Sortable(menuList, {
            animation: 150,
            handle: '.drag-icon',
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                const items = menuList.querySelectorAll('.menu-item');
                const order = [];
                items.forEach(item => {
                    order.push(item.dataset.id);
                });
                document.getElementById('menuOrder').value = JSON.stringify(order);
                document.getElementById('reorderForm').submit();
            }
        });
    }

    function editMenu(id, label, url, target, status) {
        document.getElementById('editId').value = id;
        document.getElementById('editLabel').value = label;
        document.getElementById('editUrl').value = url;
        document.getElementById('editTarget').value = target;
        document.getElementById('editStatus').checked = status == 1;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
</script>
</body>
</html>
