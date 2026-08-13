<?php
// admin/pages/users.php - User Management

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireRole(['super_admin', 'admin']);

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'editor');
    $status = isset($_POST['status']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($full_name)) {
        $error = 'Username, email, and full name are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        $data = [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ];

        if (!empty($password)) {
            if (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters';
            } else {
                $data['password'] = $password;
            }
        }

        if (empty($error)) {
            if ($id > 0) {
                if (updateUser($id, $data)) {
                    $message = 'User updated successfully!';
                    logActivity($_SESSION['user_id'], 'update_user', 'Updated user: ' . $username);
                } else {
                    $error = 'Failed to update user';
                }
            } else {
                if (empty($password)) {
                    $error = 'Password is required for new users';
                } else {
                    if (createUser($data)) {
                        $message = 'User created successfully!';
                        logActivity($_SESSION['user_id'], 'create_user', 'Created user: ' . $username);
                    } else {
                        $error = 'Failed to create user (username or email may already exist)';
                    }
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account';
    } else {
        if (deleteUser($id)) {
            $message = 'User deleted successfully!';
            logActivity($_SESSION['user_id'], 'delete_user', 'Deleted user ID: ' . $id);
        } else {
            $error = 'Failed to delete user';
        }
    }
}

// Get users
$users = getUsers();

// Get single user for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - <?= siteName() ?></title>
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
    <a href="industries.php" class="nav-item"><i class="fas fa-industry"></i> Industries</a>
    <a href="portfolio.php" class="nav-item"><i class="fas fa-briefcase"></i> Portfolio</a>
    <a href="testimonials.php" class="nav-item"><i class="fas fa-comment"></i> Testimonials</a>
    <a href="faqs.php" class="nav-item"><i class="fas fa-question-circle"></i> FAQs</a>

    <div class="nav-section">System</div>
    <a href="contacts.php" class="nav-item"><i class="fas fa-envelope"></i> Contacts</a>
    <a href="users.php" class="nav-item active"><i class="fas fa-users"></i> Users</a>
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
            <h1>User Management</h1>
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
        <h5><?= $editUser ? 'Edit User' : 'Add New User' ?></h5>
        <form method="POST">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= $editUser ? htmlspecialchars($editUser['full_name']) : '' ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= $editUser ? htmlspecialchars($editUser['username']) : '' ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $editUser ? htmlspecialchars($editUser['email']) : '' ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="super_admin" <?= $editUser && $editUser['role'] == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        <option value="admin" <?= $editUser && $editUser['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="editor" <?= $editUser && $editUser['role'] == 'editor' ? 'selected' : '' ?>>Editor</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <?= $editUser ? '<small class="text-muted">(Leave blank to keep current)</small>' : '' ?></label>
                    <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?> minlength="8">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="status" class="form-check-input" id="status" <?= $editUser && $editUser['status'] ? 'checked' : 'checked' ?>>
                        <label class="form-check-label" for="status">Active</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-save me-2"></i> <?= $editUser ? 'Update' : 'Save' ?>
            </button>
            <?php if ($editUser): ?>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users List -->
    <div class="content-card">
        <h5><i class="fas fa-list me-2"></i> All Users</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['role'] == 'super_admin' ? 'danger' : ($u['role'] == 'admin' ? 'warning' : 'secondary') ?>">
                                    <?= $u['role'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $u['status'] ? 'success' : 'danger' ?>">
                                    <?= $u['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : 'Never' ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                                <?php else: ?>
                                    <span class="text-muted">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
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