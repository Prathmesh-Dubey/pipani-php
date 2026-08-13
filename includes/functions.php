<?php
// includes/functions.php

require_once __DIR__ . '/database.php';

// ===== SETTINGS FUNCTIONS =====
function getSetting($key, $default = null)
{
    try {
        $stmt = db()->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function updateSetting($key, $value)
{
    try {
        $stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== CONTENT BLOCKS =====
function getContentBlock($slug)
{
    try {
        $stmt = db()->prepare("SELECT * FROM content_blocks WHERE slug = ? AND status = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function getContentBlocks($section = null)
{
    try {
        $sql = "SELECT * FROM content_blocks WHERE status = 1 ORDER BY `order` ASC";
        if ($section) {
            $sql = "SELECT * FROM content_blocks WHERE section = ? AND status = 1 ORDER BY `order` ASC";
            $stmt = db()->prepare($sql);
            $stmt->execute([$section]);
        } else {
            $stmt = db()->query($sql);
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function updateContentBlock($id, $data)
{
    try {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        $params[] = $id;
        $stmt = db()->prepare("UPDATE content_blocks SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?");
        return $stmt->execute($params);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== SERVICES =====
function getServices()
{
    try {
        $stmt = db()->query("SELECT * FROM services WHERE status = 1 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getService($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM services WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function getServiceBySlug($slug)
{
    try {
        $stmt = db()->prepare("SELECT * FROM services WHERE slug = ? AND status = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function generateSlug($string)
{
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function updateService($id, $data)
{
    if (empty($data['slug']) && !empty($data['title'])) {
        $data['slug'] = generateSlug($data['title']);
    }
    try {
        $stmt = db()->prepare("UPDATE services SET title = ?, slug = ?, tagline = ?, description = ?, icon = ?, image = ?, how_it_works = ?, formats = ?, benefits = ?, target_audience = ?, applications = ?, gallery = ?, cta_text = ?, `order` = ?, status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['title'] ?? null,
            $data['slug'] ?? null,
            $data['tagline'] ?? null,
            $data['description'] ?? null,
            $data['icon'] ?? null,
            $data['image'] ?? null,
            $data['how_it_works'] ?? null,
            $data['formats'] ?? null,
            $data['benefits'] ?? null,
            $data['target_audience'] ?? null,
            $data['applications'] ?? null,
            $data['gallery'] ?? null,
            $data['cta_text'] ?? null,
            $data['order'] ?? 0,
            $data['status'] ?? 1,
            $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function createService($data)
{
    if (empty($data['slug']) && !empty($data['title'])) {
        $data['slug'] = generateSlug($data['title']);
    }
    try {
        $stmt = db()->prepare("INSERT INTO services (title, slug, tagline, description, icon, image, how_it_works, formats, benefits, target_audience, applications, gallery, cta_text, `order`, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['title'] ?? null,
            $data['slug'] ?? null,
            $data['tagline'] ?? null,
            $data['description'] ?? null,
            $data['icon'] ?? null,
            $data['image'] ?? null,
            $data['how_it_works'] ?? null,
            $data['formats'] ?? null,
            $data['benefits'] ?? null,
            $data['target_audience'] ?? null,
            $data['applications'] ?? null,
            $data['gallery'] ?? null,
            $data['cta_text'] ?? null,
            $data['order'] ?? 0,
            $data['status'] ?? 1
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteService($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();

        if ($service && !empty($service['image'])) {
            $filePath = UPLOAD_DIR . $service['image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = db()->prepare("DELETE FROM services WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

function uploadServiceImage($file)
{
    if (!isset($file) || !is_array($file) || empty($file['name'])) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    $maxSize = getSetting('max_upload_size', 5242880);

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, WebP, GIF, and SVG images are allowed.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Max size: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Read the file contents and encode to base64
    $fileData = file_get_contents($file['tmp_name']);
    $base64 = base64_encode($fileData);
    
    // Determine the MIME type
    $mimeType = mime_content_type($file['tmp_name']) ?: $file['type'];
    
    $dataUri = 'data:' . $mimeType . ';base64,' . $base64;
    
    return ['success' => true, 'path' => $dataUri];
}

// ===== PORTFOLIO =====
function getPortfolio()
{
    try {
        $stmt = db()->query("SELECT * FROM portfolio WHERE status = 1 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getPortfolioItem($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM portfolio WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function updatePortfolio($id, $data)
{
    try {
        $stmt = db()->prepare("UPDATE portfolio SET title = ?, description = ?, category = ?, image = ?, link = ?, `order` = ?, status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['image'],
            $data['link'],
            $data['order'],
            $data['status'],
            $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function createPortfolio($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO portfolio (title, description, category, image, link, `order`, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['image'],
            $data['link'],
            $data['order'],
            $data['status']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function deletePortfolio($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM portfolio WHERE id = ?");
        $stmt->execute([$id]);
        $portfolio = $stmt->fetch();

        if ($portfolio && !empty($portfolio['image'])) {
            $imageValue = ltrim($portfolio['image'], '/');
            $possiblePaths = [];

            if (strpos($imageValue, 'uploads/') === 0) {
                $possiblePaths[] = UPLOAD_DIR . substr($imageValue, strlen('uploads/'));
            } elseif (strpos($imageValue, 'images/') === 0) {
                $possiblePaths[] = UPLOAD_DIR . $imageValue;
                $possiblePaths[] = UPLOAD_DIR . substr($imageValue, strlen('images/'));
            } else {
                $possiblePaths[] = UPLOAD_DIR . 'images/' . $imageValue;
                $possiblePaths[] = UPLOAD_DIR . $imageValue;
            }

            foreach ($possiblePaths as $filePath) {
                if (!empty($filePath) && file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        $stmt = db()->prepare("DELETE FROM portfolio WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

function uploadPortfolioImage($file)
{
    if (!isset($file) || !is_array($file) || empty($file['name'])) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    $maxSize = getSetting('max_upload_size', 5242880);

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, WebP, GIF, and SVG images are allowed.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Max size: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }

    $uploadDir = UPLOAD_DIR . 'images/portfolio/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    chmod($uploadDir, 0777);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('portfolio_', true) . ($ext ? '.' . $ext : '');
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }

    return ['success' => true, 'path' => 'portfolio/' . $filename];
}

// ===== TESTIMONIALS =====
function getTestimonials()
{
    try {
        $stmt = db()->query("SELECT * FROM testimonials WHERE status = 1 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getTestimonial($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM testimonials WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function updateTestimonial($id, $data)
{
    try {
        $stmt = db()->prepare("UPDATE testimonials SET name = ?, company = ?, position = ?, content = ?, avatar = ?, rating = ?, `order` = ?, status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['name'],
            $data['company'],
            $data['position'],
            $data['content'],
            $data['avatar'],
            $data['rating'],
            $data['order'],
            $data['status'],
            $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function createTestimonial($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO testimonials (name, company, position, content, avatar, rating, `order`, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['company'],
            $data['position'],
            $data['content'],
            $data['avatar'],
            $data['rating'],
            $data['order'],
            $data['status']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteTestimonial($id)
{
    try {
        $stmt = db()->prepare("DELETE FROM testimonials WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== INDUSTRIES =====
function getIndustries($activeOnly = false)
{
    try {
        $sql = "SELECT * FROM industries";
        if ($activeOnly) {
            $sql .= " WHERE status = 1";
        }
        $sql .= " ORDER BY `order` ASC, id ASC";
        $stmt = db()->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getIndustry($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM industries WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function updateIndustry($id, $data)
{
    try {
        $stmt = db()->prepare("UPDATE industries SET name = ?, icon = ?, image = ?, `order` = ?, status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['name'],
            $data['icon'] ?? null,
            $data['image'] ?? null,
            $data['order'] ?? 0,
            $data['status'] ?? 1,
            $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function createIndustry($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO industries (name, icon, image, `order`, status) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['icon'] ?? null,
            $data['image'] ?? null,
            $data['order'] ?? 0,
            $data['status'] ?? 1
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteIndustry($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM industries WHERE id = ?");
        $stmt->execute([$id]);
        $industry = $stmt->fetch();

        if ($industry && !empty($industry['image'])) {
            $imageValue = ltrim($industry['image'], '/');
            $possiblePaths = [
                UPLOAD_DIR . $imageValue,
                UPLOAD_DIR . 'images/' . $imageValue,
                UPLOAD_DIR . 'images/industries/' . basename($imageValue)
            ];

            foreach ($possiblePaths as $filePath) {
                if (!empty($filePath) && file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $stmt = db()->prepare("DELETE FROM industries WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

function uploadIndustryImage($file)
{
    if (!isset($file) || !is_array($file) || empty($file['name'])) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    $maxSize = getSetting('max_upload_size', 5242880);

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, WebP, GIF, and SVG images are allowed.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Max size: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }

    $uploadDir = UPLOAD_DIR . 'images/industries/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    @chmod($uploadDir, 0777);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('industry_', true) . ($ext ? '.' . $ext : '');
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }

    return ['success' => true, 'path' => 'images/industries/' . $filename];
}


// ===== FAQS =====
function getFaqs()
{
    try {
        $stmt = db()->query("SELECT * FROM faqs WHERE status = 1 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getFaq($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM faqs WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function updateFaq($id, $data)
{
    try {
        $stmt = db()->prepare("UPDATE faqs SET question = ?, answer = ?, `order` = ?, status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['question'],
            $data['answer'],
            $data['order'],
            $data['status'],
            $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function createFaq($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO faqs (question, answer, `order`, status) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['question'],
            $data['answer'],
            $data['order'],
            $data['status']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteFaq($id)
{
    try {
        $stmt = db()->prepare("DELETE FROM faqs WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== STATISTICS =====
function getStatistics()
{
    try {
        $stmt = db()->query("SELECT * FROM statistics WHERE status = 1 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// ===== MENU =====
function getMenu($location = 'main')
{
    try {
        $stmt = db()->prepare("SELECT m.* FROM menus m WHERE m.location = ? AND m.status = 1 LIMIT 1");
        $stmt->execute([$location]);
        $menu = $stmt->fetch();
        if (!$menu) return [];

        $stmt = db()->prepare("SELECT * FROM menu_items WHERE menu_id = ? AND status = 1 AND parent_id = 0 ORDER BY `order` ASC");
        $stmt->execute([$menu['id']]);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $stmt = db()->prepare("SELECT * FROM menu_items WHERE menu_id = ? AND parent_id = ? AND status = 1 ORDER BY `order` ASC");
            $stmt->execute([$menu['id'], $item['id']]);
            $item['children'] = $stmt->fetchAll();
        }

        return $items;
    } catch (PDOException $e) {
        return [];
    }
}

function getMenuItems($menuId)
{
    try {
        $stmt = db()->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY `order` ASC");
        $stmt->execute([$menuId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function updateMenuItem($id, $data)
{
    try {
        $stmt = db()->prepare("UPDATE menu_items SET label = ?, url = ?, target = ?, icon = ?, `order` = ?, status = ? WHERE id = ?");
        return $stmt->execute([
            $data['label'],
            $data['url'],
            $data['target'],
            $data['icon'],
            $data['order'],
            $data['status'],
            $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function createMenuItem($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO menu_items (menu_id, parent_id, label, url, target, icon, `order`, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['menu_id'],
            $data['parent_id'],
            $data['label'],
            $data['url'],
            $data['target'],
            $data['icon'],
            $data['order'],
            $data['status']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteMenuItem($id)
{
    try {
        $stmt = db()->prepare("DELETE FROM menu_items WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== CONTACT =====
function saveContact($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO contacts (name, email, phone, subject, message, ip_address, user_agent) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['subject'] ?? null,
            $data['message'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function getContacts($limit = null)
{
    try {
        $sql = "SELECT * FROM contacts ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $stmt = db()->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getContact($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function updateContactStatus($id, $status)
{
    try {
        $stmt = db()->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteContact($id)
{
    try {
        $stmt = db()->prepare("DELETE FROM contacts WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== MEDIA =====
function uploadFile($file, $targetDir = 'images')
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif', 'application/pdf'];
    $maxSize = getSetting('max_upload_size', 5242880);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Max size: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }

    $uploadDir = UPLOAD_DIR . $targetDir . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Save to database
        $stmt = db()->prepare("INSERT INTO media (filename, original_name, file_path, file_size, mime_type) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$filename, $file['name'], $targetDir . '/' . $filename, $file['size'], $file['type']]);

        // Generate thumbnail for images
        if (strpos($file['type'], 'image/') === 0) {
            generateThumbnail($filepath, $filename);
        }

        return ['success' => true, 'filename' => $filename, 'path' => $targetDir . '/' . $filename, 'id' => db()->lastInsertId()];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

function resolveImgUrl($path, $fallback = '')
{
    if (empty($path)) return $fallback;
    if (strpos($path, 'data:image/') === 0) return $path;
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    if (strpos($path, 'uploads/') === 0) return SITE_URL . $path;
    if (strpos($path, 'images/') === 0) return SITE_URL . 'uploads/' . $path;
    return SITE_URL . 'uploads/images/' . $path;
}

function generateThumbnail($filepath, $filename)
{
    $thumbDir = UPLOAD_DIR . 'thumbnails/';
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0777, true);
    }

    $thumbPath = $thumbDir . $filename;
    copy($filepath, $thumbPath);
}

function getMedia()
{
    try {
        $stmt = db()->query("SELECT * FROM media ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getMediaItem($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function deleteMedia($id)
{
    try {
        $stmt = db()->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();
        if ($media) {
            $filePath = UPLOAD_DIR . $media['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $thumbPath = UPLOAD_DIR . 'thumbnails/' . $media['filename'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        $stmt = db()->prepare("DELETE FROM media WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== SEO =====
function getSeo($page = 'home')
{
    try {
        $stmt = db()->prepare("SELECT * FROM seo_settings WHERE page = ?");
        $stmt->execute([$page]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function updateSeo($page, $data)
{
    try {
        $stmt = db()->prepare("INSERT INTO seo_settings (page, title, description, keywords, canonical, og_title, og_description, og_image, twitter_title, twitter_description, twitter_image, schema_json, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE 
                               title = ?, description = ?, keywords = ?, canonical = ?, og_title = ?, og_description = ?, og_image = ?, twitter_title = ?, twitter_description = ?, twitter_image = ?, schema_json = ?, updated_at = NOW()");
        return $stmt->execute([
            $page,
            $data['title'],
            $data['description'],
            $data['keywords'],
            $data['canonical'],
            $data['og_title'],
            $data['og_description'],
            $data['og_image'],
            $data['twitter_title'],
            $data['twitter_description'],
            $data['twitter_image'],
            $data['schema_json'],
            $data['title'],
            $data['description'],
            $data['keywords'],
            $data['canonical'],
            $data['og_title'],
            $data['og_description'],
            $data['og_image'],
            $data['twitter_title'],
            $data['twitter_description'],
            $data['twitter_image'],
            $data['schema_json']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== SITE INFO =====
function siteName()
{
    return getSetting('site_name', 'Pipani Advertising');
}

function siteTagline()
{
    return getSetting('site_tagline', 'Advertising · PR · Massive Impact');
}

function siteDescription()
{
    return getSetting('site_description', '');
}

function siteEmail()
{
    return getSetting('site_email', 'info@pipaniadvertising.com');
}

function sitePhone()
{
    return getSetting('site_phone', '+91 9766840787');
}

function siteAddress()
{
    return getSetting('site_address', 'Dhankawadi, Pune, Maharashtra 411043');
}

function getSocialLinks()
{
    try {
        $stmt = db()->query("SELECT * FROM social_links WHERE status = 1 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// ===== SECURITY =====
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function sanitizeArray($array)
{
    return array_map('sanitizeInput', $array);
}

// ===== LOGGING =====
function logActivity($userId, $action, $details = null)
{
    try {
        $stmt = db()->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
                               VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== ERROR HANDLING =====
function showError($message)
{
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

function showSuccess($message)
{
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

// ===== USER MANAGEMENT =====
function getUsers()
{
    try {
        $stmt = db()->query("SELECT id, username, email, full_name, role, status, last_login, created_at FROM users ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getUser($id)
{
    try {
        $stmt = db()->prepare("SELECT id, username, email, full_name, role, status, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function createUser($data)
{
    try {
        $stmt = db()->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password'],
            $data['full_name'],
            $data['role'],
            $data['status']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function updateUser($id, $data)
{
    try {
        $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ?";
        $params = [$data['username'], $data['email'], $data['full_name'], $data['role'], $data['status']];

        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = $data['password'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = db()->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteUser($id)
{
    try {
        $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===== FOOTER =====
function getFooterColumns()
{
    try {
        $stmt = db()->query("SELECT * FROM footer_columns WHERE status = 1 ORDER BY `order` ASC");
        $cols = $stmt->fetchAll();
        $stmt = db()->query("SELECT * FROM footer_links WHERE status = 1 ORDER BY `order` ASC");
        $links = $stmt->fetchAll();
        foreach ($cols as &$col) {
            $col['links'] = array_filter($links, function($l) use ($col) { return $l['column_id'] == $col['id']; });
        }
        return $cols;
    } catch (PDOException $e) {
        return [];
    }
}

function getFooterBottomLinks()
{
    try {
        $stmt = db()->query("SELECT * FROM footer_links WHERE status = 1 AND column_id = 0 ORDER BY `order` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

