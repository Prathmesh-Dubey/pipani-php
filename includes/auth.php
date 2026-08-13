<?php
// includes/auth.php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    public static function login($username, $password, $remember = false) {
        try {
            // Debug: Check if database connection works
            $db = db();
            
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            // Debug: Check if user exists
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Debug: Check password
            if ($password !== $user['password']) {
                return ['success' => false, 'error' => 'Invalid password'];
            }

            if ($user['status'] != 1) {
                return ['success' => false, 'error' => 'Account is disabled'];
            }

            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['logged_in'] = true;

            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
                setcookie('remember_token', $token, time() + 86400 * 30, '/');
            }

            if (function_exists('logActivity')) {
                logActivity($user['id'], 'login', 'User logged in');
            }

            return ['success' => true, 'user' => $user];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public static function checkRememberMe() {
        if (isset($_COOKIE['remember_token']) && !self::isLoggedIn()) {
            try {
                $db = db();
                $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 1 LIMIT 1");
                $stmt->execute([$_COOKIE['remember_token']]);
                $user = $stmt->fetch();

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['logged_in'] = true;
                    return true;
                }
            } catch (PDOException $e) {
                return false;
            }
        }
        return false;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['role']) && in_array($_SESSION['role'], ['super_admin', 'admin']);
    }

    public static function logout() {
        if (self::isLoggedIn() && function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }

        $_SESSION = [];
        session_destroy();

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        return true;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . ADMIN_URL . 'index.php');
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireLogin();
        if (!in_array($_SESSION['role'], (array)$roles)) {
            header('Location: ' . ADMIN_URL . 'dashboard.php');
            exit;
        }
    }

    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        try {
            $db = db();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function changePassword($userId, $oldPassword, $newPassword) {
        try {
            $db = db();
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || $oldPassword !== $user['password']) {
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }

            if (strlen($newPassword) < 8) {
                return ['success' => false, 'error' => 'New password must be at least 8 characters'];
            }

            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newPassword, $userId]);

            if (function_exists('logActivity')) {
                logActivity($userId, 'password_change', 'User changed password');
            }

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    public static function resetPassword($email) {
        try {
            $db = db();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'error' => 'Email not found'];
            }

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Create password_resets table if not exists
            $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY email (email)
            )");

            $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
            $stmt->execute([$email, $token, $expires, $token, $expires]);

            // In production, send email here
            $resetLink = SITE_URL . 'reset-password.php?token=' . $token;

            return ['success' => true, 'message' => 'Password reset link sent to your email'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    public static function verifyResetToken($token) {
        try {
            $db = db();
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function updatePasswordWithToken($token, $newPassword) {
        try {
            $reset = self::verifyResetToken($token);
            if (!$reset) {
                return ['success' => false, 'error' => 'Invalid or expired token'];
            }

            if (strlen($newPassword) < 8) {
                return ['success' => false, 'error' => 'Password must be at least 8 characters'];
            }

            $db = db();
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$newPassword, $reset['email']]);

            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    // Debug function to create a test user if none exists
    public static function createTestUser() {
        try {
            $db = db();
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, status) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute(['admin', 'admin@example.com', 'admin123', 'Super Admin', 'super_admin', 1]);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
}