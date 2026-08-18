<?php
// templates/frontend/index.php - Modern Frontend Template (Active)

// Helper for image URLs
if (!function_exists('resolveImgUrl')) {
    function resolveImgUrl($path, $fallback = '')
    {
        if (empty($path)) $path = $fallback;
        if (empty($path)) return '';

        if (strpos($path, 'data:image/') === 0) return $path;
        if (preg_match('/^https?:\/\//i', $path)) return $path;

        $relPath = '';
        if (strpos($path, 'uploads/') === 0) {
            $relPath = substr($path, 8);
        } elseif (strpos($path, 'images/') === 0) {
            $relPath = $path;
        } else {
            $relPath = 'images/' . $path;
        }

        if (defined('UPLOAD_DIR') && file_exists(UPLOAD_DIR . $relPath) && is_file(UPLOAD_DIR . $relPath)) {
            return SITE_URL . 'uploads/' . $relPath;
        }

        if ($path !== $fallback && !empty($fallback)) {
            return resolveImgUrl($fallback);
        }

        return '';
    }
}

// Get all content & settings
$siteName = siteName();
$siteTagline = siteTagline();
$siteDescription = siteDescription();
$logo = getSetting('logo', '');
$favicon = getSetting('favicon', '');
$footerText = getSetting('footer_text', '');
$contactEmail = siteEmail();
$contactPhone = sitePhone();
$contactAddress = siteAddress();

// Content blocks
$heroTitle = getContentBlock('hero_title');
$heroSubtitle = getContentBlock('hero_subtitle');
$heroBadge = getContentBlock('hero_badge');
$aboutTitle = getContentBlock('about_title');
$aboutSubtitle = getContentBlock('about_subtitle');
$aboutContent = getContentBlock('about_content');
$aboutImage = getContentBlock('about_image');
$aboutImgUrl = ($aboutImage && !empty($aboutImage['content'])) ? resolveImgUrl($aboutImage['content']) : resolveImgUrl('about_team.png');

$heroTrustItems = getContentBlock('hero_trust_items');
$trustItemsData = ($heroTrustItems && !empty($heroTrustItems['content'])) ? json_decode($heroTrustItems['content'], true) : [
    ['icon' => 'fas fa-check-circle', 'text' => '7+ Years of Excellence'],
    ['icon' => 'fas fa-check-circle', 'text' => 'Full-Service Agency'],
    ['icon' => 'fas fa-check-circle', 'text' => 'Pan-India Presence']
];

// Dynamic data from CMS
$services = getServices();
$industries = getIndustries(true);
$portfolio = getPortfolio();
$testimonials = getTestimonials();
$faqs = getFaqs();
$statistics = getStatistics();
$socialLinks = getSocialLinks();
$menuItems = getMenu('main');

// Footer Data
$footerCols = function_exists('getFooterColumns') ? getFooterColumns() : [];
$footerBottomLinks = function_exists('getFooterBottomLinks') ? getFooterBottomLinks() : [];

// SEO
$seo = getSeo('home');

// Dynamic JSON data with fallback
$clientsData = json_decode(getSetting('clients_data', '[{"name":"Tata Group"},{"name":"Reliance"},{"name":"Airtel"},{"name":"Jio"},{"name":"Zomato"},{"name":"Amul"},{"name":"Maruti Suzuki"},{"name":"Paytm"},{"name":"HDFC Bank"},{"name":"Infosys"}]'), true);
$processData = json_decode(getSetting('process_data', '[{"num":"1","title":"Discovery","desc":"Understanding your brand, goals, and target audience"},{"num":"2","title":"Strategy","desc":"Developing a tailored media plan for maximum impact"},{"num":"3","title":"Execution","desc":"Flawless implementation across chosen media channels"},{"num":"4","title":"Analysis","desc":"Measuring results and optimizing for continued success"}]'), true);
$whyData = json_decode(getSetting('why_data', '[{"icon":"fas fa-eye","title":"71% Read Rate","desc":"71% of people read hoardings carefully while driving"},{"icon":"fas fa-chart-line","title":"80% Response","desc":"80% of consumers react to bus ads by purchasing"},{"icon":"fas fa-rocket","title":"50%+ Growth","desc":"50%+ increase in purchase via OTT ads in Q2 2020"},{"icon":"fas fa-clock","title":"24/7 Visibility","desc":"Round-the-clock visibility through cab and transit advertising"}]'), true);

// Check if user is logged in as admin
$isLoggedInAdmin = Auth::isAdmin();
if (isset($_GET['preview']) && $_GET['preview'] == 1) {
    $isLoggedInAdmin = false;
}

// Handle Contact Form Submission (Public & Visitors)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_contact') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? 'Website Inquiry');

    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['contact_error'] = 'Please fill in all required fields (Name, Email, Message).';
    } else {
        $contactData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ];

        if (saveContact($contactData)) {
            $_SESSION['contact_success'] = 'Thank you! Your message has been received. We will get back to you shortly.';
        } else {
            $_SESSION['contact_error'] = 'Sorry, there was an error sending your message. Please try again or call us directly.';
        }
    }
    header('Location: ' . SITE_URL . '#contact');
    exit;
}

// Handle Admin Quick Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$isLoggedInAdmin) {
        die('Unauthorized access');
    }

    $action = $_POST['action'];

    // Services
    if ($action === 'quick_add_service') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-bullhorn');
        $order = intval($_POST['order'] ?? 0);
        $status = 1;
        $benefitsRaw = $_POST['benefits'] ?? '';
        $benefitsArray = array_filter(array_map('trim', explode("\n", $benefitsRaw)));
        $benefits = !empty($benefitsArray) ? json_encode(array_values($benefitsArray)) : null;

        $imagePath = null;
        if (isset($_FILES['service_image']) && !empty($_FILES['service_image']['name'])) {
            $uploadResult = uploadServiceImage($_FILES['service_image']);
            if ($uploadResult['success'])
                $imagePath = $uploadResult['path'];
        }

        if (!empty($title)) {
            createService(['title' => $title, 'description' => $description, 'icon' => $icon, 'image' => $imagePath, 'benefits' => $benefits, 'order' => $order, 'status' => $status]);
            if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
                logActivity($_SESSION['user_id'], 'create_service', 'Added service from website: ' . $title);
            }
        }
        header('Location: ' . SITE_URL . '#services');
        exit;
    }

    if ($action === 'quick_edit_service') {
        $serviceId = intval($_POST['service_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-bullhorn');
        $order = intval($_POST['order'] ?? 0);
        $status = 1;
        $benefitsRaw = $_POST['benefits'] ?? '';
        $benefitsArray = array_filter(array_map('trim', explode("\n", $benefitsRaw)));
        $benefits = !empty($benefitsArray) ? json_encode(array_values($benefitsArray)) : null;

        $existing = getService($serviceId);
        $imagePath = $existing['image'] ?? null;
        if (isset($_FILES['service_image']) && !empty($_FILES['service_image']['name'])) {
            $uploadResult = uploadServiceImage($_FILES['service_image']);
            if ($uploadResult['success'])
                $imagePath = $uploadResult['path'];
        }

        if ($serviceId > 0 && !empty($title)) {
            updateService($serviceId, ['title' => $title, 'description' => $description, 'icon' => $icon, 'image' => $imagePath, 'benefits' => $benefits, 'order' => $order, 'status' => $status]);
            if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
                logActivity($_SESSION['user_id'], 'update_service', 'Updated service: ' . $title);
            }
        }
        header('Location: ' . SITE_URL . '#services');
        exit;
    }

    if ($action === 'quick_delete_service') {
        $serviceId = intval($_POST['service_id'] ?? 0);
        if ($serviceId > 0) {
            deleteService($serviceId);
            if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
                logActivity($_SESSION['user_id'], 'delete_service', 'Deleted service ID: ' . $serviceId);
            }
        }
        header('Location: ' . SITE_URL . '#services');
        exit;
    }

    // Portfolio
    if ($action === 'quick_add_portfolio') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? 'Campaign');
        $order = intval($_POST['order'] ?? 0);
        $imagePath = null;
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadResult = uploadPortfolioImage($_FILES['image']);
            if ($uploadResult['success'])
                $imagePath = $uploadResult['path'];
        }
        createPortfolio(['title' => $title, 'description' => $description, 'category' => $category, 'image' => $imagePath, 'link' => '', 'order' => $order, 'status' => 1]);
        header('Location: ' . SITE_URL . '#portfolio');
        exit;
    }

    if ($action === 'quick_edit_portfolio') {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? 'Campaign');
        $order = intval($_POST['order'] ?? 0);
        $existing = getPortfolioItem($id);
        $imagePath = $existing['image'] ?? null;
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadResult = uploadPortfolioImage($_FILES['image']);
            if ($uploadResult['success'])
                $imagePath = $uploadResult['path'];
        }
        updatePortfolio($id, ['title' => $title, 'description' => $description, 'category' => $category, 'image' => $imagePath, 'link' => '', 'order' => $order, 'status' => 1]);
        header('Location: ' . SITE_URL . '#portfolio');
        exit;
    }

    if ($action === 'quick_delete_portfolio') {
        $id = intval($_POST['id'] ?? 0);
        deletePortfolio($id);
        header('Location: ' . SITE_URL . '#portfolio');
        exit;
    }

    // Industries
    if ($action === 'quick_add_industry') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-building');
        $order = intval($_POST['order'] ?? 0);
        $imagePath = null;
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadResult = uploadIndustryImage($_FILES['image']);
            if ($uploadResult['success'])
                $imagePath = $uploadResult['path'];
        }
        if (!empty($name)) {
            createIndustry(['name' => $name, 'icon' => $icon, 'image' => $imagePath, 'order' => $order, 'status' => 1]);
        }
        header('Location: ' . SITE_URL . '#industries');
        exit;
    }

    if ($action === 'quick_edit_industry') {
        $id = intval($_POST['id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-building');
        $order = intval($_POST['order'] ?? 0);
        $existing = getIndustry($id);
        $imagePath = $existing['image'] ?? null;
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadResult = uploadIndustryImage($_FILES['image']);
            if ($uploadResult['success'])
                $imagePath = $uploadResult['path'];
        }
        if ($id > 0 && !empty($name)) {
            updateIndustry($id, ['name' => $name, 'icon' => $icon, 'image' => $imagePath, 'order' => $order, 'status' => 1]);
        }
        header('Location: ' . SITE_URL . '#industries');
        exit;
    }

    if ($action === 'quick_delete_industry') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0)
            deleteIndustry($id);
        header('Location: ' . SITE_URL . '#industries');
        exit;
    }

    // FAQs
    if ($action === 'quick_add_faq') {
        createFaq(['question' => $_POST['question'], 'answer' => $_POST['answer'], 'order' => intval($_POST['order'] ?? 0), 'status' => 1]);
        header('Location: ' . SITE_URL . '#faq');
        exit;
    }
    if ($action === 'quick_edit_faq') {
        updateFaq(intval($_POST['id']), ['question' => $_POST['question'], 'answer' => $_POST['answer'], 'order' => intval($_POST['order'] ?? 0), 'status' => 1]);
        header('Location: ' . SITE_URL . '#faq');
        exit;
    }
    if ($action === 'quick_delete_faq') {
        deleteFaq(intval($_POST['id']));
        header('Location: ' . SITE_URL . '#faq');
        exit;
    }

    // Statistics
    if ($action === 'quick_add_statistics') {
        $label = $_POST['label'];
        $value = $_POST['value'];
        $suffix = $_POST['suffix'];
        $stmt = db()->prepare("INSERT INTO statistics (label, value, suffix, `order`, status) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$label, $value, $suffix, intval($_POST['order'] ?? 0)]);
        header('Location: ' . SITE_URL . '#statistics');
        exit;
    }
    if ($action === 'quick_edit_statistics') {
        $id = intval($_POST['id']);
        $label = $_POST['label'];
        $value = $_POST['value'];
        $suffix = $_POST['suffix'];
        $stmt = db()->prepare("UPDATE statistics SET label=?, value=?, suffix=?, `order`=? WHERE id=?");
        $stmt->execute([$label, $value, $suffix, intval($_POST['order'] ?? 0), $id]);
        header('Location: ' . SITE_URL . '#statistics');
        exit;
    }
    if ($action === 'quick_delete_statistics') {
        $id = intval($_POST['id']);
        $stmt = db()->prepare("DELETE FROM statistics WHERE id=?");
        $stmt->execute([$id]);
        header('Location: ' . SITE_URL . '#statistics');
        exit;
    }

    // Testimonials
    if ($action === 'quick_add_testimonial') {
        createTestimonial(['name' => $_POST['name'], 'company' => $_POST['company'], 'position' => $_POST['position'], 'content' => $_POST['content'], 'avatar' => '', 'rating' => intval($_POST['rating']), 'order' => intval($_POST['order'] ?? 0), 'status' => 1]);
        header('Location: ' . SITE_URL . '#testimonials');
        exit;
    }
    if ($action === 'quick_edit_testimonial') {
        updateTestimonial(intval($_POST['id']), ['name' => $_POST['name'], 'company' => $_POST['company'], 'position' => $_POST['position'], 'content' => $_POST['content'], 'avatar' => '', 'rating' => intval($_POST['rating']), 'order' => intval($_POST['order'] ?? 0), 'status' => 1]);
        header('Location: ' . SITE_URL . '#testimonials');
        exit;
    }
    if ($action === 'quick_delete_testimonial') {
        deleteTestimonial(intval($_POST['id']));
        header('Location: ' . SITE_URL . '#testimonials');
        exit;
    }

    // Footer Columns & Links
    if ($action === 'add_footer_column') {
        db()->prepare("INSERT INTO footer_columns (title, `order`) VALUES (?, ?)")->execute([$_POST['title'], intval($_POST['order'] ?? 0)]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'edit_footer_column') {
        db()->prepare("UPDATE footer_columns SET title=?, `order`=? WHERE id=?")->execute([$_POST['title'], intval($_POST['order'] ?? 0), intval($_POST['id'])]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'delete_footer_column') {
        db()->prepare("DELETE FROM footer_columns WHERE id=?")->execute([intval($_POST['id'])]);
        db()->prepare("DELETE FROM footer_links WHERE column_id=?")->execute([intval($_POST['id'])]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'add_footer_link') {
        db()->prepare("INSERT INTO footer_links (column_id, label, url, `order`) VALUES (?, ?, ?, ?)")->execute([intval($_POST['column_id']), $_POST['label'], $_POST['url'], intval($_POST['order'] ?? 0)]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'edit_footer_link') {
        db()->prepare("UPDATE footer_links SET label=?, url=?, `order`=? WHERE id=?")->execute([$_POST['label'], $_POST['url'], intval($_POST['order'] ?? 0), intval($_POST['id'])]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'delete_footer_link') {
        db()->prepare("DELETE FROM footer_links WHERE id=?")->execute([intval($_POST['id'])]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }

    // Social Links
    if ($action === 'add_social_link') {
        db()->prepare("INSERT INTO social_links (platform, url, icon, `order`) VALUES (?, ?, ?, ?)")->execute([$_POST['platform'], $_POST['url'], $_POST['icon'], intval($_POST['order'] ?? 0)]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'edit_social_link') {
        db()->prepare("UPDATE social_links SET platform=?, url=?, icon=?, `order`=? WHERE id=?")->execute([$_POST['platform'], $_POST['url'], $_POST['icon'], intval($_POST['order'] ?? 0), intval($_POST['id'])]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }
    if ($action === 'delete_social_link') {
        db()->prepare("DELETE FROM social_links WHERE id=?")->execute([intval($_POST['id'])]);
        header('Location: ' . SITE_URL . '#footer');
        exit;
    }

    // Hero Multi-block
    // Navbar Logo
    if ($action === 'quick_edit_logo') {
        if (isset($_FILES['logo_image']) && !empty($_FILES['logo_image']['name'])) {
            $uploadResult = uploadFile($_FILES['logo_image'], 'images');
            if ($uploadResult['success']) {
                updateSetting('logo', $uploadResult['path']);
            }
        }
        if (isset($_POST['logo_display_type'])) {
            updateSetting('logo_display_type', $_POST['logo_display_type']);
        }
        header('Location: ' . SITE_URL . '#');
        exit;
    }

    if ($action === 'quick_edit_hero') {
        $badge = $_POST['hero_badge'] ?? '';
        $title = $_POST['hero_title'] ?? '';
        $subtitle = $_POST['hero_subtitle'] ?? '';

        $trustItems = [];
        for ($i = 0; $i < 3; $i++) {
            $trustItems[] = [
                'icon' => $_POST['hero_trust_icon'][$i] ?? 'fas fa-check-circle',
                'text' => $_POST['hero_trust_text'][$i] ?? ''
            ];
        }
        $trustItemsJson = json_encode($trustItems);

        foreach (['hero_badge' => $badge, 'hero_title' => $title, 'hero_subtitle' => $subtitle, 'hero_trust_items' => $trustItemsJson] as $slug => $val) {
            $block = getContentBlock($slug);
            if (!$block) {
                db()->prepare("INSERT INTO content_blocks (slug, content, status, section) VALUES (?, ?, 1, 'general')")->execute([$slug, $val]);
            } else {
                db()->prepare("UPDATE content_blocks SET content=? WHERE slug=?")->execute([$val, $slug]);
            }
        }
        header('Location: ' . SITE_URL . '#hero');
        exit;
    }

    // About Multi-block
    if ($action === 'quick_edit_about') {
        $subtitle = $_POST['about_subtitle'] ?? '';
        $content = $_POST['about_content'] ?? '';

        // Handle Image Upload / Removal
        if (!empty($_POST['remove_about_image'])) {
            $block = getContentBlock('about_image');
            if ($block) {
                db()->prepare("UPDATE content_blocks SET content='' WHERE slug='about_image'")->execute();
            }
        } elseif (isset($_FILES['about_image']) && is_array($_FILES['about_image']) && !empty($_FILES['about_image']['name'])) {
            $uploadResult = uploadFile($_FILES['about_image'], 'images');
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path'];
                $block = getContentBlock('about_image');
                if (!$block) {
                    db()->prepare("INSERT INTO content_blocks (slug, content, status, section) VALUES ('about_image', ?, 1, 'general')")->execute([$imagePath]);
                } else {
                    db()->prepare("UPDATE content_blocks SET content=? WHERE slug='about_image'")->execute([$imagePath]);
                }
            }
        }

        foreach (['about_subtitle' => $subtitle, 'about_content' => $content] as $slug => $val) {
            $block = getContentBlock($slug);
            if (!$block) {
                db()->prepare("INSERT INTO content_blocks (slug, content, status, section) VALUES (?, ?, 1, 'general')")->execute([$slug, $val]);
            } else {
                db()->prepare("UPDATE content_blocks SET content=? WHERE slug=?")->execute([$val, $slug]);
            }
        }
        header('Location: ' . SITE_URL . '#about');
        exit;
    }

    // Contact Settings
    if ($action === 'quick_edit_contact_settings') {
        updateSetting('site_email', $_POST['site_email']);
        updateSetting('site_phone', $_POST['site_phone']);
        updateSetting('site_address', $_POST['site_address']);
        header('Location: ' . SITE_URL . '#contact');
        exit;
    }

    // Quick Edit Clients (Visual Icon Picker & Image Uploader)
    if ($action === 'quick_edit_clients') {
        $names = $_POST['client_names'] ?? [];
        $logoTypes = $_POST['client_logo_types'] ?? [];
        $icons = $_POST['client_icons'] ?? [];
        $existingLogos = $_POST['client_existing_logos'] ?? [];

        $clientsArray = [];
        for ($i = 0; $i < count($names); $i++) {
            $name = trim($names[$i]);
            if (empty($name))
                continue;

            $type = $logoTypes[$i] ?? 'icon';
            $icon = !empty($icons[$i]) ? trim($icons[$i]) : 'fas fa-building';
            $logo = $existingLogos[$i] ?? '';

            // Check if a file was uploaded for this row
            if (isset($_FILES['client_logo_files']['name'][$i]) && !empty($_FILES['client_logo_files']['name'][$i]) && $_FILES['client_logo_files']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['client_logo_files']['name'][$i],
                    'type' => $_FILES['client_logo_files']['type'][$i],
                    'tmp_name' => $_FILES['client_logo_files']['tmp_name'][$i],
                    'error' => $_FILES['client_logo_files']['error'][$i],
                    'size' => $_FILES['client_logo_files']['size'][$i],
                ];
                $upload = uploadFile($file, 'images');
                if ($upload['success']) {
                    $logo = $upload['path'];
                }
            }

            $clientItem = ['name' => $name];
            if ($type === 'image' && !empty($logo)) {
                $clientItem['logo'] = $logo;
            } else {
                $clientItem['icon'] = $icon;
            }
            $clientsArray[] = $clientItem;
        }

        updateSetting('clients_data', json_encode($clientsArray));
        header('Location: ' . SITE_URL . '#clients');
        exit;
    }

    // JSON Settings (Process, Why)
    if ($action === 'quick_edit_json_setting') {
        $key = $_POST['setting_key'];
        $json_data = $_POST['json_data'];
        updateSetting($key, $json_data);
        header('Location: ' . SITE_URL . '#' . ($_POST['section'] ?? ''));
        exit;
    }
}

// Flash messages
$contactSuccess = $_SESSION['contact_success'] ?? '';
$contactError = $_SESSION['contact_error'] ?? '';
unset($_SESSION['contact_success'], $_SESSION['contact_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $seo ? htmlspecialchars($seo['title']) : htmlspecialchars($siteName . ' — ' . $siteTagline) ?></title>
    <meta name="description"
        content="<?= $seo ? htmlspecialchars($seo['description']) : htmlspecialchars($siteDescription) ?>" />
    <?php if ($seo && !empty($seo['keywords'])): ?>
        <meta name="keywords" content="<?= htmlspecialchars($seo['keywords']) ?>" />
    <?php endif; ?>
    <?php if ($seo && !empty($seo['canonical'])): ?>
        <link rel="canonical" href="<?= htmlspecialchars($seo['canonical']) ?>" />
    <?php endif; ?>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= resolveImgUrl(htmlspecialchars($favicon)) ?>" />
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <style>
        /* ===== CSS VARIABLES - CORPORATE RED & WHITE THEME ===== */
        :root {
            /* Brand Colors */
            --primary: #E61A27;
            --primary-dark: #B3121D;
            --primary-light: #FF4D5A;
            --primary-gradient: linear-gradient(180deg, #E61A27 0%, #B3121D 100%);

            /* Clean Backgrounds */
            --bg-main: #FFFFFF;
            --bg-alt: #F3F4F6;
            /* Light grey for section contrast */
            --bg-card: #FFFFFF;

            /* Professional Typography */
            --text-heading: #111827;
            --text-body: #4B5563;
            --text-muted: #6B7280;
            --text-light: #FFFFFF;

            /* UI Elements */
            --border-light: #E5E7EB;
            --border-medium: #D1D5DB;

            /* Elegant Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            --shadow-red: 0 10px 20px -5px rgba(230, 26, 39, 0.3);

            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 20px;

            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --max-width: 1280px;
            --section-padding: 56px 0;

            --font-body: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: var(--font-body);
            color: var(--text-body);
            background: var(--bg-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 32px;
        }

        /* ===== UTILITY ===== */
        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .section-badge::before {
            content: '';
            display: block;
            width: 24px;
            height: 2px;
            background: var(--primary);
        }

        .section-title {
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: clamp(2.2rem, 3.5vw, 3rem);
            line-height: 1.2;
            margin-bottom: 12px;
            color: var(--text-heading);
        }

        .section-title .highlight {
            color: var(--primary);
        }

        .section-subtitle {
            color: var(--text-body);
            font-size: 1.1rem;
            max-width: 600px;
            line-height: 1.7;
        }

        .text-center {
            text-align: center;
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--primary);
            color: var(--text-light);
            font-weight: 600;
            padding: 14px 32px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            box-shadow: var(--shadow-red);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            color: var(--text-heading);
            font-weight: 600;
            padding: 12px 30px;
            border-radius: var(--radius-sm);
            border: 2px solid var(--border-medium);
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(230, 26, 39, 0.05);
        }

        /* ===== SCROLL PROGRESS ===== */
        #scrollProgress {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: var(--primary-dark);
            z-index: 9999;
            width: 0%;
            transition: width 0.1s;
        }


        /* ===== NAVIGATION (RED THEME) ===== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 10px 0;
            transition: var(--transition);
            background: var(--primary-gradient);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        }

        .navbar.scrolled {
            padding: 8px 0;
            background: var(--primary-dark);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--text-light) !important;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .logo img {
            max-height: 48px;
            width: auto;
            display: block;
            object-fit: contain;
            margin-right: 16px;
            margin-top: -4px;
            margin-bottom: -4px;
        }

        .logo span {
            color: #FFB3B8;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.88);
            font-weight: 500;
            font-size: 0.92rem;
            letter-spacing: 0.2px;
            text-decoration: none;
            padding: 6px 0;
            position: relative;
            transition: var(--transition);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ffffff;
            border-radius: 2px;
            transition: var(--transition);
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #ffffff;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 1001;
            padding: 6px;
        }

        .menu-toggle span {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--text-light);
            border-radius: 2px;
            transition: var(--transition);
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
            transform: scale(0);
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* ===== SERVICES (BELT) ===== */
        .services {
            padding: 12px 0;
            margin-top: 52px;
            background: var(--bg-main);
            border-bottom: 1px solid var(--border-light);
            box-sizing: border-box;
            width: 100%;
        }

        .services-slider-container {
            position: relative;
            padding: 0 42px;
        }

        .services-slider {
            display: flex;
            align-items: center;
            gap: 14px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            scroll-snap-type: x mandatory;
            white-space: nowrap;
            -ms-overflow-style: none;
            scrollbar-width: none;
            padding: 6px 0;
        }

        .services-slider::-webkit-scrollbar {
            display: none;
        }

        .service-belt-item {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: auto;
            padding: 5px 13px;
            background: var(--bg-alt);
            border-radius: 50px;
            border: 1px solid transparent;
            color: var(--text-body);
            transition: var(--transition);
            text-decoration: none;
            white-space: nowrap;
        }

        .service-belt-item .icon {
            font-size: 11.5px;
            color: var(--primary);
        }

        .service-belt-item h3 {
            margin: 0;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .service-belt-item:hover,
        .service-belt-item.active {
            background: var(--primary);
            color: var(--text-light);
            box-shadow: var(--shadow-red);
        }

        .service-belt-item:hover .icon,
        .service-belt-item.active .icon {
            color: var(--text-light);
        }

        .services-belt-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--bg-main);
            border: 1px solid var(--border-light);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 20;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            font-size: 0.75rem;
            color: var(--text-body);
        }

        .services-belt-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .services-belt-btn.prev {
            left: 0;
        }

        .services-belt-btn.next {
            right: 0;
        }

        /* ===== HERO ===== */
        .hero {
            background: var(--bg-main);
            position: relative;
            overflow: hidden;
            padding: 48px 0 24px;
        }

        .hero .container {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .hero-content {
            max-width: 850px;
            margin: 0 auto 28px;
        }

        .hero-content h1 {
            color: var(--text-heading);
            font-size: clamp(2.8rem, 5vw, 4.2rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 14px;
            letter-spacing: -1px;
        }

        .hero-content p {
            color: var(--text-body);
            font-size: 1.15rem;
            margin: 0 auto 24px;
            line-height: 1.6;
            max-width: 650px;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .hero-trust {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .hero-trust .trust-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-trust .trust-item i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* ===== CAMPAIGN SHOWCASE ===== */
        .portfolio-slider-container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
        }

        .portfolio-slider {
            display: flex;
            gap: 20px;
            padding: 8px 0 16px;
            width: max-content;
            animation: portfolio-marquee 40s linear infinite;
        }

        .portfolio-slider:hover {
            animation-play-state: paused;
        }

        @keyframes portfolio-marquee {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-50% - 10px));
            }
        }

        .portfolio-card {
            flex: 0 0 320px;
            scroll-snap-align: start;
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .portfolio-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--border-medium);
        }

        .portfolio-card .card-img-wrapper {
            height: 200px;
            position: relative;
            overflow: hidden;
            background: var(--bg-alt);
        }

        .portfolio-card .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }

        .portfolio-card:hover .card-img-wrapper img {
            transform: scale(1.05);
        }

        .portfolio-card .card-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--primary);
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 0.85rem;
            box-shadow: var(--shadow-sm);
        }

        .portfolio-card .card-details {
            padding: 20px;
            border-top: 3px solid var(--primary);
        }

        .portfolio-card .card-details h4 {
            color: var(--text-heading);
            font-size: 1.1rem;
            font-weight: 700;
            font-family: var(--font-heading);
            margin-bottom: 12px;
        }

        .portfolio-card .card-details .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .portfolio-card .card-details .price {
            color: var(--text-body);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .portfolio-card .card-details .action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #16a34a;
            background: rgba(22, 163, 74, 0.1);
            padding: 6px 12px;
            border-radius: 50px;
            transition: var(--transition);
        }

        .portfolio-card .card-details .action-btn:hover {
            background: #16a34a;
            color: white;
        }

        .slider-btn.large {
            width: 44px;
            height: 44px;
            background: var(--bg-card);
            color: var(--primary);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
        }

        .slider-btn.large:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* ===== CLIENTS MARQUEE ===== */
        .clients {
            padding: 12px 0;
            background: var(--bg-alt);
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
            overflow: hidden;
            box-sizing: border-box;
            width: 100%;
        }

        .clients-marquee {
            display: flex;
            gap: 48px;
            animation: marquee 35s linear infinite;
            width: max-content;
        }

        .clients-marquee .client-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-family: var(--font-heading);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .clients-marquee .client-item i {
            font-size: 1.2rem;
            color: var(--border-medium);
            transition: var(--transition);
        }

        .clients-marquee .client-item:hover {
            color: var(--text-heading);
        }

        .clients-marquee .client-item:hover i {
            color: var(--primary);
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        /* ===== ABOUT US ===== */
        .about {
            padding: var(--section-padding);
            background: var(--bg-main);
            overflow: hidden;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 36px;
            align-items: center;
        }

        .about-visual {
            position: relative;
            padding: 20px;
        }

        .about-visual .image-placeholder {
            width: 90%;
            aspect-ratio: 4/3;
            background: var(--bg-alt);
            border-radius: var(--radius-lg);
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;

        }

        .about-visual .image-placeholder i {
            font-size: 4rem;
            color: var(--border-medium);
        }

        .about-visual .accent-box {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60%;
            height: 60%;
            background: var(--primary-fade);
            border-radius: var(--radius-lg);
            z-index: 1;
            transform: translate(20px, 20px);
            opacity: 0.3;
        }

        .about-content h2 {
            margin-bottom: 14px;
        }

        .about-content p {
            margin-bottom: 12px;
            font-size: 1.05rem;
        }

        .about-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 18px;
        }

        .about-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: var(--text-heading);
        }

        .about-features li i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* ===== INDUSTRIES ===== */
        .industries {
            padding: var(--section-padding);
            background: var(--bg-alt);
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
            box-sizing: border-box;
            width: 100%;
        }

        .industries-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin-top: 28px;
        }

        .industry-item {
            background: var(--bg-card);
            padding: 18px 14px;
            border-radius: var(--radius-sm);
            border-top: 4px solid var(--border-medium);
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 110px;
            box-sizing: border-box;
        }

        .industry-item:hover {
            border-top-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .industry-item i {
            font-size: 1.8rem;
            color: var(--text-heading);
            margin-bottom: 8px;
            display: block;
            transition: var(--transition);
        }

        .industry-item:hover i {
            color: var(--primary);
        }

        .industry-item span {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-body);
        }

        /* ===== STATISTICS (RED VARIATION) ===== */
        .statistics {
            padding: 50px 0;
            background: var(--primary-gradient);
            color: var(--text-light);
        }

        .statistics .section-title,
        .statistics .section-subtitle,
        .statistics .section-badge {
            color: var(--text-light);
        }

        .statistics .section-badge::before {
            background: var(--text-light);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 28px;
            margin-top: 28px;
        }

        .stats-grid .stat-item {
            text-align: center;
        }

        .stats-grid .stat-item .number {
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stats-grid .stat-item .label {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }

        /* ===== PROCESS ===== */
        .process {
            padding: var(--section-padding);
            background: var(--bg-main);
        }

        .process-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 36px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .process-timeline::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-light);
            z-index: 0;
        }

        .process-step {
            position: relative;
            z-index: 1;
            width: 22%;
            text-align: center;
        }

        .process-step .step-number {
            width: 60px;
            height: 60px;
            background: var(--bg-card);
            border: 3px solid var(--primary);
            color: var(--primary);
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.4rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .process-step:hover .step-number {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
            box-shadow: var(--shadow-red);
        }

        .process-step h4 {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-heading);
            margin-bottom: 10px;
        }

        .process-step p {
            font-size: 0.95rem;
            color: var(--text-body);
            line-height: 1.5;
        }

        /* ===== WHY CHOOSE US ===== */
        .why {
            padding: var(--section-padding);
            background: var(--bg-alt);
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
            box-sizing: border-box;
            width: 100%;
        }

        .why-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 32px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .why-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 22px 24px;
            background: var(--bg-card);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-light);
            transition: var(--transition);
        }

        .why-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .why-item .icon {
            width: 56px;
            height: 56px;
            background: var(--primary);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--text-light);
            flex-shrink: 0;
        }

        .why-item h4 {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--text-heading);
            margin-bottom: 8px;
        }

        .why-item p {
            font-size: 0.95rem;
            margin: 0;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonials {
            padding: var(--section-padding);
            background: var(--bg-main);
        }

        /* Slider wrapper: positions the prev/next arrows on the sides */
        .testimonials-slider-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 32px;
        }

        /* The scrollable track — one card scrolls at a time */
        .testimonials-grid {
            display: flex;
            gap: 18px;
            overflow-x: auto;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
            padding: 6px 4px 12px;
            flex: 1;
        }

        .testimonials-grid::-webkit-scrollbar {
            display: none;
        }

        .testimonial-card {
            /* Fixed width so exactly 3 are visible on desktop, 1 on mobile */
            flex: 0 0 calc(33.333% - 20px);
            background: var(--bg-alt);
            padding: 26px 24px;
            border-radius: var(--radius-md);
            position: relative;
            transition: var(--transition);
            border: 1px solid var(--border-light);
            box-sizing: border-box;
        }

        @media (max-width: 900px) {
            .testimonial-card {
                flex: 0 0 calc(50% - 14px);
            }
        }

        @media (max-width: 600px) {
            .testimonial-card {
                flex: 0 0 100%;
            }

            .testimonials-slider-wrapper {
                gap: 8px;
            }
        }

        .testimonial-card:hover {
            background: var(--bg-card);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .testimonial-card .fa-quote-left {
            position: absolute;
            top: 32px;
            right: 32px;
            font-size: 3rem;
            color: var(--primary-fade);
            transition: var(--transition);
        }

        .testimonial-card:hover .fa-quote-left {
            color: rgba(230, 26, 39, 0.15);
        }

        .testimonial-card .stars {
            color: #F59E0B;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .testimonial-card .quote {
            font-size: 1.05rem;
            color: var(--text-heading);
            font-style: italic;
            margin-bottom: 16px;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        .testimonial-card .author {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .testimonial-card .author .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--text-light);
            font-size: 1rem;
        }

        .testimonial-card .author .info .name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-heading);
        }

        .testimonial-card .author .info .company {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* ===== FAQ ===== */
        .faq {
            padding: var(--section-padding);
            background: var(--bg-alt);
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
            box-sizing: border-box;
            width: 100%;
        }

        .faq-list {
            max-width: 760px;
            margin: 32px auto 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .faq-item {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            overflow: hidden;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .faq-item:hover {
            border-color: var(--border-medium);
        }

        .faq-item.active {
            border-left-color: var(--primary);
            box-shadow: var(--shadow-sm);
            border-color: var(--border-medium);
        }

        .faq-item .question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--text-heading);
        }

        .faq-item .question i {
            color: var(--primary);
            transition: transform 0.3s;
        }

        .faq-item.active .question i {
            transform: rotate(180deg);
        }

        .faq-item .answer {
            max-height: 0;
            padding: 0 24px;
            color: var(--text-body);
            transition: all 0.3s ease;
            opacity: 0;
            line-height: 1.6;
        }

        .faq-item.active .answer {
            max-height: 300px;
            padding: 0 20px 14px;
            opacity: 1;
        }

        /* ===== CONTACT ===== */
        .contact {
            padding: var(--section-padding);
            background: var(--bg-main);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 36px;
            margin-top: 32px;
        }

        .contact-info-blocks {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .contact-card {
            background: var(--bg-alt);
            border-radius: var(--radius-sm);
            padding: 18px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border: 1px solid var(--border-light);
            transition: var(--transition);
        }

        .contact-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
            background: var(--bg-card);
        }

        .contact-card .card-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-fade);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .contact-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 4px;
        }

        .contact-card .role {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .contact-card .contact-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .contact-card .contact-links a,
        .contact-card .contact-links p {
            font-size: 0.95rem;
            color: var(--text-heading);
            font-weight: 500;
            transition: var(--transition);
        }

        .contact-card .contact-links a:hover {
            color: var(--primary);
        }

        .contact-form-wrapper {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .contact-form-header {
            background: var(--primary-gradient);
            padding: 16px 24px;
            color: var(--text-light);
        }

        .contact-form-header h3 {
            font-family: var(--font-heading);
            font-size: 1.4rem;
            font-weight: 700;
        }

        .contact-form {
            padding: 24px;
        }

        .contact-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 0.95rem;
            background: var(--bg-alt);
            color: var(--text-heading);
            transition: var(--transition);
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px var(--primary-fade);
        }

        .contact-form textarea {
            min-height: 100px;
            resize: vertical;
            margin-bottom: 16px;
            margin-top: 0;
            width: 100%;
        }

        .contact-map {
            margin-top: 28px;
            border-radius: var(--radius-md);
            overflow: hidden;
            height: 300px;
            border: 1px solid var(--border-light);
        }

        .contact-map iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* ===== FOOTER (DARK THEME) ===== */
        .footer {
            background: #111827;
            color: #9CA3AF;
            padding: 50px 0 24px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 32px;
            margin-bottom: 36px;
        }

        .footer-brand .logo {
            color: #FFFFFF !important;
            font-size: 1.8rem;
            margin-bottom: 20px;
            display: inline-block;
        }

        .footer-brand .logo span {
            color: var(--primary);
        }

        .footer-brand p {
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 24px;
            max-width: 350px;
        }

        .footer-brand .socials {
            display: flex;
            gap: 12px;
        }

        .footer-brand .socials a {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            transition: var(--transition);
        }

        .footer-brand .socials a:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-3px);
        }

        .footer h4 {
            color: #FFFFFF;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 24px;
        }

        .footer ul {
            list-style: none;
        }

        .footer ul li {
            margin-bottom: 12px;
        }

        .footer ul a {
            color: #9CA3AF;
            font-weight: 400;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .footer ul a:hover {
            color: #FFFFFF;
            padding-left: 5px;
        }

        .footer-newsletter p {
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .newsletter-form {
            display: flex;
            gap: 8px;
        }

        .newsletter-form input {
            flex: 1;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #FFFFFF;
            font-family: var(--font-body);
        }

        .newsletter-form button {
            background: var(--primary);
            color: #FFFFFF;
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .newsletter-form button:hover {
            background: var(--primary-dark);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.9rem;
        }

        .footer-bottom .links {
            display: flex;
            gap: 24px;
        }

        .footer-bottom .links a {
            color: #9CA3AF;
            transition: var(--transition);
        }

        .footer-bottom .links a:hover {
            color: #FFFFFF;
        }

        /* ===== BACK TO TOP ===== */
        .back-to-top {
            position: fixed;
            bottom: 32px;
            right: 32px;
            width: 50px;
            height: 50px;
            background: var(--text-heading);
            color: var(--text-light);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 1.2rem;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            z-index: 999;
        }

        .back-to-top.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .back-to-top:hover {
            background: var(--primary);
            transform: translateY(-5px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .industries-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) {
            .about-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .about-visual .accent-box {
                display: none;
            }

            .process-timeline {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
                padding-left: 30px;
            }

            .process-timeline::before {
                left: 30px;
                top: 0;
                bottom: 0;
                width: 2px;
                height: auto;
            }

            .process-step {
                width: 100%;
                text-align: left;
                display: flex;
                flex-direction: column;
                position: relative;
            }

            .process-step .step-number {
                position: absolute;
                left: -60px;
                top: 0;
                margin: 0;
                width: 50px;
                height: 50px;
                font-size: 1.1rem;
                border-width: 2px;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            :root {
                --section-padding: 40px 0;
            }

            .nav-links {
                position: fixed;
                top: 52px;
                right: -100%;
                width: 240px;
                height: calc(100vh - 52px);
                background: var(--primary-dark);
                flex-direction: column;
                justify-content: flex-start;
                gap: 20px;
                padding: 30px 24px;
                box-shadow: -8px 0 24px rgba(0, 0, 0, 0.2);
                transition: var(--transition);
                z-index: 999;
            }

            .nav-links.active {
                right: 0;
            }

            .nav-links a {
                color: #ffffff;
                font-size: 1rem;
            }

            .menu-toggle {
                display: flex;
            }

            #services {
                margin-top: 52px;
            }

            .hero-content h1 {
                font-size: 2.2rem;
            }

            .hero-trust {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .why-grid {
                grid-template-columns: 1fr;
            }

            .industries-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .contact-form .form-row {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }


        /* ===== ADMIN CONTROLS & FLOATING BAR ===== */
        .admin-floating-bar {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background: rgba(10, 10, 10, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            z-index: 9998;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 0.85rem;
        }

        .admin-floating-bar a {
            color: #fff;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .admin-floating-bar a:hover {
            background: var(--primary);
            color: #fff;
        }

        .admin-badge-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #4ade80;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-badge-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 8px #4ade80;
        }

        .admin-quick-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--primary);
            color: #fff !important;
            border: none;
            border-radius: 30px;
            padding: 6px 14px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            z-index: 20;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(230, 26, 39, 0.4);
            transition: var(--transition);
        }

        .admin-quick-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .admin-quick-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #fff;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-top: 20px;
            cursor: pointer;
            border: none;
            transition: var(--transition);
        }

        .admin-quick-add-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .admin-item-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 6px;
            z-index: 15;
        }

        .admin-item-actions button {
            background: rgba(10, 10, 10, 0.85);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .admin-item-actions button:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* ===== CMS MODALS ===== */
        .cms-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(6px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }

        .cms-modal-overlay.active {
            display: flex;
        }

        .cms-modal-card {
            background: #fff;
            border-radius: var(--radius-md);
            max-width: 600px;
            width: 100%;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .cms-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }

        .cms-modal-header h3 {
            font-family: 'Outfit', 'Poppins', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-heading, #111827);
        }

        .cms-modal-close {
            background: transparent;
            border: none;
            font-size: 1.3rem;
            color: #888;
            cursor: pointer;
        }

        .cms-modal-close:hover {
            color: var(--primary);
        }

        .cms-form-group {
            margin-bottom: 18px;
        }

        .cms-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-body, #4B5563);
            margin-bottom: 6px;
        }

        .cms-form-group input,
        .cms-form-group textarea,
        .cms-form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: var(--radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 0.92rem;
            transition: var(--transition);
        }

        .cms-form-group input:focus,
        .cms-form-group textarea:focus,
        .cms-form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(230, 26, 39, 0.1);
        }

        /* Modal Wide & Repeaters */
        .cms-modal-card.cms-modal-wide {
            max-width: 680px;
        }

        .cms-repeater-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 52vh;
            overflow-y: auto;
            padding-right: 4px;
            margin-bottom: 12px;
        }

        .cms-repeater-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            transition: all 0.2s ease;
        }

        .cms-repeater-card:hover {
            border-color: #cbd5e1;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .cms-repeater-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .cms-repeater-badge {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cms-repeater-actions {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .cms-btn-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.15s;
        }

        .cms-btn-icon:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .cms-btn-delete {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.15s;
        }

        .cms-btn-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .cms-btn-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 16px;
        }

        .cms-btn-add:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(230, 26, 39, 0.04);
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
        }

        .cms-icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
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

        .cms-img-preview-box {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            margin-bottom: 12px;
        }

        .cms-img-preview-box img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }

        .cms-pill-toggle {
            display: inline-flex;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            gap: 3px;
        }

        .cms-pill-btn {
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .cms-pill-btn:hover {
            color: #0f172a;
        }

        .cms-pill-btn.active {
            background: #fff;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* ===== FLASH MESSAGES ===== */
        .alert-banner {
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-banner.success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-banner.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
    </style>
</head>

<body>
    <!-- ===== SCROLL PROGRESS ===== -->
    <div id="scrollProgress"></div>

    <?php if ($isLoggedInAdmin): ?>
        <!-- ===== ADMIN FLOATING BAR ===== -->
        <div class="admin-floating-bar">
            <div class="admin-badge-indicator">
                <span></span> Editor Mode
            </div>
            <a href="<?= SITE_URL ?>admin/dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>admin/logout.php" style="background:rgba(230,26,39,0.2);"><i
                    class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </div>
    <?php endif; ?>

    <!-- ===== NAVIGATION ===== -->
    <nav class="navbar" id="navbar" role="navigation" aria-label="Main Navigation">
        <div class="container">
            <?php
            $navLogo = getSetting('logo', '');
            $navLogoUrl = !empty($navLogo) ? resolveImgUrl($navLogo) : resolveImgUrl('logo.png');
            $logoDisplayType = getSetting('logo_display_type', 'both');
            ?>
            <div style="display:flex;align-items:center;gap:8px;position:relative;">
                <a href="#" class="logo" aria-label="<?= htmlspecialchars($siteName) ?> Home">
                    <?php if ($logoDisplayType === 'both' || $logoDisplayType === 'logo_only'): ?>
                        <img src="<?= $navLogoUrl ?>" alt="<?= htmlspecialchars($siteName) ?>" />
                    <?php endif; ?>
                    <?php if ($logoDisplayType === 'both' || $logoDisplayType === 'name_only'): ?>
                        <?= htmlspecialchars($siteName) ?><span>.</span>
                    <?php endif; ?>
                </a>
                <?php if ($isLoggedInAdmin): ?>
                    <button type="button" class="admin-quick-btn" onclick="openModal('editLogoModal')" style="font-size:0.7rem;padding:4px 10px;">
                        <i class="fas fa-image"></i> Edit Logo
                    </button>
                <?php endif; ?>
            </div>

            <ul class="nav-links" id="navLinks" role="menubar">
                <li role="none"><a href="#about" role="menuitem">About</a></li>
                <li role="none"><a href="#portfolio" role="menuitem">Portfolio</a></li>
                <li role="none"><a href="#services" role="menuitem">Services</a></li>
                <li role="none"><a href="#clients" role="menuitem">Clients</a></li>
            </ul>

            <button class="menu-toggle" id="menuToggle" aria-label="Toggle Navigation Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- ===== SERVICES (BELT) ===== -->
    <section class="services" id="services" aria-label="Our Services">
        <div class="container" style="padding: 0; position: relative;">
            <div class="services-slider-container" data-aos="fade-down">
                <button class="services-belt-btn prev" id="servicesPrevBtn" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="services-slider" id="servicesSlider">
                    <?php if ($isLoggedInAdmin): ?>
                        <div style="position: relative; flex-shrink: 0;">
                            <button type="button" onclick="openAddServiceModal()" class="service-belt-item"
                                style="background: var(--primary); color: #fff; border: 1px solid var(--primary); cursor: pointer; font-weight: 600;">
                                <div class="icon" style="color: #fff;"><i class="fas fa-plus-circle"></i></div>
                                <h3 style="color: #fff; font-weight: 600;">Add Service</h3>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($services)): ?>
                        <?php $sIdx = 0;
                        foreach ($services as $service):
                            $sIdx++; ?>
                            <div style="position: relative; flex-shrink: 0;">
                                <a href="media.php?type=<?= urlencode($service['slug'] ?? '') ?>" class="service-belt-item">
                                    <div class="icon"><i
                                            class="<?= htmlspecialchars(!empty($service['icon']) ? $service['icon'] : 'fas fa-bullhorn') ?>"></i>
                                    </div>
                                    <h3><?= htmlspecialchars($service['title']) ?></h3>
                                </a>
                                <?php if ($isLoggedInAdmin): ?>
                                    <button type="button" title="Edit Service"
                                        onclick='openEditServiceModal(<?= htmlspecialchars(json_encode($service, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'
                                        style="position: absolute; top: -6px; right: -6px; background: #111; color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; width: 20px; height: 20px; font-size: 0.6rem; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;"><i
                                            class="fas fa-edit"></i></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="media.php?type=offline-media" class="service-belt-item">
                            <div class="icon"><i class="fas fa-billboard"></i></div>
                            <h3>Offline Media</h3>
                        </a>
                        <a href="media.php?type=transit-media" class="service-belt-item active">
                            <div class="icon"><i class="fas fa-bus"></i></div>
                            <h3>Transit Media</h3>
                        </a>
                        <a href="media.php?type=electronic-media" class="service-belt-item">
                            <div class="icon"><i class="fas fa-radio"></i></div>
                            <h3>Electronic Media</h3>
                        </a>
                        <a href="media.php?type=cinema-branding" class="service-belt-item">
                            <div class="icon"><i class="fas fa-film"></i></div>
                            <h3>Cinema Branding</h3>
                        </a>
                        <a href="media.php?type=ambient-media" class="service-belt-item">
                            <div class="icon"><i class="fas fa-store"></i></div>
                            <h3>Ambient Media</h3>
                        </a>
                        <a href="media.php?type=print-media" class="service-belt-item">
                            <div class="icon"><i class="fas fa-newspaper"></i></div>
                            <h3>Print Media</h3>
                        </a>
                        <a href="media.php?type=non-traditional-media" class="service-belt-item">
                            <div class="icon"><i class="fas fa-paint-brush"></i></div>
                            <h3>Non-Traditional Media</h3>
                        </a>
                        <a href="media.php?type=corporate-gifting" class="service-belt-item">
                            <div class="icon"><i class="fas fa-gift"></i></div>
                            <h3>Corporate Gifting</h3>
                        </a>
                    <?php endif; ?>
                </div>
                <button class="services-belt-btn next" id="servicesNextBtn" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- ===== HERO ===== -->
    <section class="hero" id="hero" aria-label="Hero Section" style="position: relative;">
        <?php if ($isLoggedInAdmin): ?>
            <button type="button" class="admin-quick-btn" onclick="openEditHeroModal()"><i class="fas fa-edit"></i> Edit
                Hero</button>
        <?php endif; ?>
        <div class="container">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="800">
                <span
                    class="section-badge"><?= $heroBadge ? htmlspecialchars($heroBadge['content']) : '8 Years of Excellence' ?></span>
                <h1>
                    <?php
                    $rawTitle = $heroTitle ? $heroTitle['content'] : "Driving <span class=\"highlight\">Massive Impact</span><br> Since 2017";
                    if (strpos($rawTitle, '<span') !== false || strpos($rawTitle, '<br') !== false) {
                        echo $rawTitle;
                    } else {
                        $parts = explode('Massive Impact', $rawTitle);
                        if (count($parts) > 1) {
                            echo htmlspecialchars($parts[0]) . '<span class="highlight">Massive Impact</span>' . htmlspecialchars($parts[1]);
                        } else {
                            echo htmlspecialchars($rawTitle);
                        }
                    }
                    ?>
                </h1>
                <p><?= $heroSubtitle ? nl2br(htmlspecialchars($heroSubtitle['content'])) : "Deliver your brand message directly to millions of consumers through our full-spectrum, data-driven advertising strategies across India." ?>
                </p>
                <div class="hero-buttons">
                    <a href="#portfolio" class="btn-primary">Explore Campaigns <i class="fas fa-arrow-right"></i></a>
                    <a href="#contact" class="btn-outline">Get a Custom Quote</a>
                </div>
                <div class="hero-trust">
                    <?php if (!empty($trustItemsData)): ?>
                        <?php foreach ($trustItemsData as $item): ?>
                            <span class="trust-item"><i
                                    class="<?= htmlspecialchars($item['icon'] ?? 'fas fa-check-circle') ?>"></i>
                                <?= htmlspecialchars($item['text'] ?? '') ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="trust-item"><i class="fas fa-check-circle"></i> 8+ Years Experience</span>
                        <span class="trust-item"><i class="fas fa-check-circle"></i> Full-Service Agency</span>
                        <span class="trust-item"><i class="fas fa-check-circle"></i> Pan-India Presence</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== CAMPAIGN SHOWCASE ===== -->
            <?php if ($isLoggedInAdmin): ?>
                <div style="text-align: right; margin-bottom: 10px; position: relative; z-index: 20;">
                    <button type="button" class="admin-quick-add-btn" style="margin:0; display: inline-block;"
                        onclick="openAddPortfolioModal()"><i class="fas fa-plus-circle"></i> Add Portfolio Item</button>
                </div>
            <?php endif; ?>
            <div class="portfolio-slider-container" id="portfolio" data-aos="fade-up" data-aos-delay="200"
                style="position: relative;">
                <div style="position: relative; width: 100%; display: flex; align-items: center; gap: 16px;">
                    <div class="portfolio-slider" id="portfolioSlider">
                        <?php if (!empty($portfolio)): ?>
                            <?php 
                            $multiplier = max(1, ceil(5 / max(1, count($portfolio))));
                            ob_start();
                            for($m=0; $m<$multiplier; $m++):
                                foreach ($portfolio as $item): 
                            ?>
                                <div class="portfolio-card" style="position: relative;">
                                    <?php if ($isLoggedInAdmin): ?>
                                        <div class="admin-item-actions">
                                            <button type="button" title="Edit Portfolio"
                                                onclick='openEditPortfolioModal(<?= htmlspecialchars(json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i
                                                    class="fas fa-edit"></i></button>
                                            <form action="<?= SITE_URL ?>#portfolio" method="POST"
                                                onsubmit="return confirm('Delete portfolio item?');" style="margin:0;">
                                                <input type="hidden" name="action" value="quick_delete_portfolio">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" title="Delete" style="background:#E61A27;"><i
                                                        class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-img-wrapper">
                                        <img src="<?= resolveImgUrl($item['image'], 'https://placehold.co/400x300/F3F4F6/9CA3AF?text=Portfolio') ?>"
                                            alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" />
                                        <div class="card-badge"><i class="fas fa-bullseye"></i></div>
                                    </div>
                                    <div class="card-details">
                                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                                        <div class="price-row">
                                            <span
                                                class="price"><?= !empty($item['category']) ? htmlspecialchars($item['category']) : 'Campaign' ?></span>
                                            <a href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $contactPhone ?: '9876543210') ?>?text=<?= urlencode('Hi, I am interested in ' . $item['title']) ?>"
                                                class="action-btn" target="_blank" title="Contact on WhatsApp"><i
                                                    class="fab fa-whatsapp"></i> Chat</a>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                endforeach; 
                            endfor;
                            $itemsHtml = ob_get_clean();
                            echo $itemsHtml; // First half
                            echo $itemsHtml; // Second half for -50% CSS transition
                            ?>
                        <?php else: ?>
                            <div style="padding: 40px; text-align: center; color: var(--text-muted); width: 100%;">
                                <i class="fas fa-image fa-3x" style="margin-bottom: 15px; opacity: 0.5;"></i>
                                <h4>No portfolio items available yet.</h4>
                                <?php if ($isLoggedInAdmin): ?>
                                    <p>Click "Add Portfolio Item" to create your first showcase.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CLIENTS MARQUEE ===== -->
    <section class="clients" id="clients" aria-label="Our Clients" style="position: relative;">
        <?php if ($isLoggedInAdmin): ?>
            <button type="button" class="admin-quick-btn" onclick="openEditClientsModal()"><i class="fas fa-edit"></i> Edit
                Clients</button>
        <?php endif; ?>
        <div class="container">
            <div class="clients-marquee">
                <?php
                $clientIcons = [
                    'Tata Group' => 'fas fa-building',
                    'Reliance' => 'fas fa-building',
                    'Airtel' => 'fas fa-signal',
                    'Jio' => 'fas fa-wifi',
                    'Zomato' => 'fas fa-utensils',
                    'Amul' => 'fas fa-cow',
                    'Maruti Suzuki' => 'fas fa-car',
                    'Paytm' => 'fas fa-mobile-alt',
                    'HDFC Bank' => 'fas fa-university',
                    'Infosys' => 'fas fa-laptop'
                ];
                for ($loop = 0; $loop < 2; $loop++):
                    foreach ($clientsData as $client):
                        $cName = is_array($client) ? ($client['name'] ?? '') : $client;
                        $cLogo = is_array($client) ? ($client['logo'] ?? '') : '';
                        $cIcon = is_array($client) ? ($client['icon'] ?? ($clientIcons[$cName] ?? 'fas fa-building')) : ($clientIcons[$cName] ?? 'fas fa-building');
                        if (empty($cName) && empty($cLogo))
                            continue;

                        $iconClass = 'fas fa-building';
                        if (!empty($cIcon)) {
                            $cIcon = trim($cIcon);
                            if (strpos($cIcon, 'fa') === 0 || strpos($cIcon, 'fas ') === 0) {
                                $iconClass = $cIcon;
                            } else {
                                $iconClass = 'fas ' . $cIcon;
                            }
                        }
                ?>
                        <span class="client-item">
                            <?php if (!empty($cLogo)): ?>
                                <img src="<?= htmlspecialchars(resolveImgUrl($cLogo)) ?>" alt="<?= htmlspecialchars($cName) ?>"
                                    style="max-height: 28px; max-width: 90px; object-fit: contain; vertical-align: middle; margin-right: 8px;">
                            <?php else: ?>
                                <i class="<?= htmlspecialchars($iconClass) ?>"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($cName) ?>
                        </span>
                <?php
                    endforeach;
                endfor;
                ?>
            </div>
        </div>
    </section>

    <!-- ===== ABOUT ===== -->
    <section class="about" id="about" aria-label="About Us" style="position: relative;">
        <?php if ($isLoggedInAdmin): ?>
            <button type="button" class="admin-quick-btn" onclick="openEditAboutModal()"><i class="fas fa-edit"></i> Edit
                About</button>
        <?php endif; ?>
        <div class="container">
            <div class="about-grid">
                <div class="about-visual" data-aos="zoom-in" data-aos-duration="1000">
                    <div class="accent-box"></div>
                    <div class="image-placeholder">
                        <img src="<?= htmlspecialchars($aboutImgUrl) ?>" alt="About Pipani Advertising"
                            loading="lazy" />
                    </div>
                </div>

                <div class="about-content" data-aos="fade-left" data-aos-duration="800">
                    <span class="section-badge">About Us</span>
                    <h2 class="section-title">
                        <?= $aboutTitle ? $aboutTitle['content'] : 'Who We <span class="highlight">Are</span>' ?></h2>
                    <?php if ($aboutContent): ?>
                        <p><?= nl2br(htmlspecialchars($aboutContent['content'])) ?></p>
                    <?php else: ?>
                        <p>For over 8 years, Pipani Advertising has been at the forefront of the advertising industry,
                            helping brands achieve massive, measurable impact. As a part of the PIPANI ADVERTISING Group and
                            operating through Adnest Communications LLP, we are a full-service powerhouse specializing in a
                            diverse range of media.</p>
                        <p>From traditional outdoor and transit advertising to innovative digital and non-traditional
                            solutions, our mission is to deliver unparalleled visibility and brand awareness across every
                            touchpoint.</p>
                    <?php endif; ?>

                    <ul class="about-features">
                        <li><i class="fas fa-check-circle"></i> 8+ Years of Proven Excellence</li>
                        <li><i class="fas fa-check-circle"></i> Complete Full-Service Agency</li>
                        <li><i class="fas fa-check-circle"></i> Expansive Pan-India Presence</li>
                        <li><i class="fas fa-check-circle"></i> 9 Dedicated Service Categories</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>





    <!-- ===== INDUSTRIES ===== -->
    <section class="industries" id="industries" aria-label="Industries We Serve" style="position: relative;">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">Industries</span>
                <h2 class="section-title">Sectors We <span class="highlight">Serve</span></h2>
                <p class="section-subtitle mx-auto">Expert advertising solutions customized to your specific industry
                    constraints and opportunities.</p>
                <?php if ($isLoggedInAdmin): ?>
                    <button type="button" class="admin-quick-add-btn" onclick="openAddIndustryModal()"><i
                            class="fas fa-plus-circle"></i> Add Industry</button>
                <?php endif; ?>
            </div>
            <div class="industries-grid" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($industries)): ?>
                    <?php foreach ($industries as $ind): ?>
                        <div class="industry-item" style="position: relative;">
                            <?php if ($isLoggedInAdmin): ?>
                                <div class="admin-item-actions" style="top:5px; right:5px;">
                                    <button type="button" title="Edit Industry" style="width:24px;height:24px;font-size:0.65rem;"
                                        onclick='openEditIndustryModal(<?= htmlspecialchars(json_encode($ind, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i
                                            class="fas fa-edit"></i></button>
                                    <form action="<?= SITE_URL ?>#industries" method="POST"
                                        onsubmit="return confirm('Delete industry?');" style="margin:0;">
                                        <input type="hidden" name="action" value="quick_delete_industry">
                                        <input type="hidden" name="id" value="<?= $ind['id'] ?>">
                                        <button type="submit" title="Delete"
                                            style="width:24px;height:24px;font-size:0.65rem;background:#E61A27;"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($ind['image'])): ?>
                                <img src="<?= resolveImgUrl($ind['image']) ?>" alt="<?= htmlspecialchars($ind['name']) ?>"
                                    style="width:40px;height:40px;margin:0 auto 8px;object-fit:contain;" />
                            <?php else: ?>
                                <i class="<?= htmlspecialchars($ind['icon'] ?? 'fas fa-building') ?>"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($ind['name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="industry-item"><i class="fas fa-building"></i><span>Real Estate</span></div>
                    <div class="industry-item"><i class="fas fa-heart-pulse"></i><span>Healthcare</span></div>
                    <div class="industry-item"><i class="fas fa-graduation-cap"></i><span>Education</span></div>
                    <div class="industry-item"><i class="fas fa-cart-shopping"></i><span>Retail</span></div>
                    <div class="industry-item"><i class="fas fa-car"></i><span>Automobile</span></div>
                    <div class="industry-item"><i class="fas fa-utensils"></i><span>Hospitality</span></div>
                    <div class="industry-item"><i class="fas fa-landmark"></i><span>Government</span></div>
                    <div class="industry-item"><i class="fas fa-vote-yea"></i><span>Political</span></div>
                    <div class="industry-item"><i class="fas fa-box-open"></i><span>FMCG</span></div>
                    <div class="industry-item"><i class="fas fa-laptop-code"></i><span>Technology</span></div>
                    <div class="industry-item"><i class="fas fa-chart-line"></i><span>Finance</span></div>
                    <div class="industry-item"><i class="fas fa-film"></i><span>Entertainment</span></div>
                    <div class="industry-item"><i class="fas fa-truck"></i><span>Logistics</span></div>
                    <div class="industry-item"><i class="fas fa-tractor"></i><span>Agriculture</span></div>
                    <div class="industry-item"><i class="fas fa-bolt"></i><span>Energy</span></div>
                    <div class="industry-item"><i class="fas fa-broadcast-tower"></i><span>Telecom</span></div>
                    <div class="industry-item"><i class="fas fa-hard-hat"></i><span>Construction</span></div>
                    <div class="industry-item"><i class="fas fa-plane"></i><span>Tourism</span></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== STATISTICS ===== -->
    <section class="statistics" id="statistics" aria-label="Our Impact in Numbers" style="position: relative;">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">Our Impact</span>
                <h2 class="section-title">By the Numbers</h2>
                <p class="section-subtitle mx-auto" style="color: rgba(255,255,255,0.8);">Transparent, measurable
                    results that establish long-term brand success.</p>
                <?php if ($isLoggedInAdmin): ?>
                    <button type="button" class="admin-quick-add-btn" onclick="openAddStatModal()"><i
                            class="fas fa-plus-circle"></i> Add Stat</button>
                <?php endif; ?>
            </div>
            <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($statistics)): ?>
                    <?php foreach ($statistics as $stat): ?>
                        <div class="stat-item" style="position: relative;">
                            <?php if ($isLoggedInAdmin): ?>
                                <div class="admin-item-actions" style="top:-10px; right:0;">
                                    <button type="button" title="Edit Stat" style="width:24px;height:24px;font-size:0.65rem;"
                                        onclick='openEditStatModal(<?= htmlspecialchars(json_encode($stat, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i
                                            class="fas fa-edit"></i></button>
                                    <form action="<?= SITE_URL ?>#statistics" method="POST"
                                        onsubmit="return confirm('Delete stat?');" style="margin:0;">
                                        <input type="hidden" name="action" value="quick_delete_statistics">
                                        <input type="hidden" name="id" value="<?= $stat['id'] ?>">
                                        <button type="submit" title="Delete"
                                            style="width:24px;height:24px;font-size:0.65rem;background:#E61A27;"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <div class="number">
                                <span class="counter"
                                    data-target="<?= intval($stat['value']) ?>"><?= intval($stat['value']) ?></span><span
                                    class="suffix"><?= htmlspecialchars($stat['suffix'] ?? '+') ?></span>
                            </div>
                            <div class="label"><?= htmlspecialchars($stat['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="stat-item">
                        <div class="number"><span class="counter" data-target="8">8</span><span class="suffix">+</span>
                        </div>
                        <div class="label">Years Experience</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><span class="counter" data-target="25">25</span></div>
                        <div class="label">Media Reach</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><span class="counter" data-target="79">79</span><span class="suffix">+</span>
                        </div>
                        <div class="label">Campaigns</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><span class="counter" data-target="225">225</span><span class="suffix">+</span>
                        </div>
                        <div class="label">Brands Served</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><span class="counter" data-target="32">32</span><span class="suffix">+</span>
                        </div>
                        <div class="label">Cities Covered</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><span class="counter" data-target="3">3</span><span class="suffix">M+</span>
                        </div>
                        <div class="label">Daily Audience</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== PROCESS ===== -->
    <section class="process" id="process" aria-label="Our Process" style="position: relative;">
        <?php if ($isLoggedInAdmin): ?>
            <button type="button" class="admin-quick-btn" onclick="openEditProcessModal()"><i class="fas fa-edit"></i> Edit
                Process</button>
        <?php endif; ?>
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">Our Process</span>
                <h2 class="section-title">How We <span class="highlight">Work</span></h2>
                <p class="section-subtitle mx-auto">A seamless, data-backed approach to execute exceptional advertising
                    campaigns.</p>
            </div>
            <div class="process-timeline" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($processData)): ?>
                    <?php foreach ($processData as $p): ?>
                        <div class="process-step">
                            <div class="step-number"><?= htmlspecialchars($p['num'] ?? '') ?></div>
                            <h4><?= htmlspecialchars($p['title'] ?? '') ?></h4>
                            <p><?= htmlspecialchars($p['desc'] ?? '') ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <h4>Discovery</h4>
                        <p>Deep-diving into your brand, market goals, and defining your precise target audience.</p>
                    </div>
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <h4>Strategy</h4>
                        <p>Developing a meticulously tailored media plan designed for maximum ROI and visibility.</p>
                    </div>
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <h4>Execution</h4>
                        <p>Flawless implementation, procurement, and deployment across chosen media channels.</p>
                    </div>
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <h4>Analysis</h4>
                        <p>Continuous monitoring, measuring results, and optimizing for scalable success.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== WHY CHOOSE US ===== -->
    <section class="why" id="why" aria-label="Why Choose Us" style="position: relative;">
        <?php if ($isLoggedInAdmin): ?>
            <button type="button" class="admin-quick-btn" onclick="openEditWhyModal()"><i class="fas fa-edit"></i> Edit Why
                Us</button>
        <?php endif; ?>
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">Why Pipani</span>
                <h2 class="section-title">Why Choose <span class="highlight">Us</span></h2>
                <p class="section-subtitle mx-auto">We don't just place ads; we strategically position your brand for
                    maximum consumer interaction.</p>
            </div>
            <div class="why-grid" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($whyData)): ?>
                    <?php foreach ($whyData as $w): ?>
                        <div class="why-item">
                            <div class="icon"><i class="<?= htmlspecialchars($w['icon'] ?? 'fas fa-check') ?>"></i></div>
                            <div>
                                <h4><?= htmlspecialchars($w['title'] ?? '') ?></h4>
                                <p><?= htmlspecialchars($w['desc'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="why-item">
                        <div class="icon"><i class="fas fa-eye"></i></div>
                        <div>
                            <h4>71% Read Rate</h4>
                            <p>71% of commuters consciously read hoardings and outdoor transit signs while traveling.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <h4>80% Consumer Response</h4>
                            <p>80% of targeted consumers react to localized transit and dynamic bus advertisements.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="icon"><i class="fas fa-rocket"></i></div>
                        <div>
                            <h4>50%+ Engagement Growth</h4>
                            <p>Experience over 50% increase in brand interactions via our cinematic and OTT campaigns.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <h4>24/7 Visibility</h4>
                            <p>Ensure round-the-clock brand awareness through strategic ambient and transit media placement.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== TESTIMONIALS ===== -->
    <section class="testimonials" id="testimonials" aria-label="Testimonials" style="position: relative;">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">Testimonials</span>
                <h2 class="section-title">What Our <span class="highlight">Clients Say</span></h2>
                <p class="section-subtitle mx-auto">Real feedback from brands that trusted us to scale their visibility.
                </p>
                <?php if ($isLoggedInAdmin): ?>
                    <button type="button" class="admin-quick-add-btn" onclick="openAddTestimonialModal()"><i
                            class="fas fa-plus-circle"></i> Add Testimonial</button>
                <?php endif; ?>
            </div>

            <!-- Slider wrapper with prev/next arrows on the sides -->
            <div class="testimonials-slider-wrapper" data-aos="fade-up" data-aos-delay="100">

                <button class="slider-btn large prev" id="testimonialPrevBtn" aria-label="Previous testimonial">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <!-- The scrollable track: ALL DB testimonials render here -->
                <div class="testimonials-grid" id="testimonialsSlider">
                    <?php if (!empty($testimonials)): ?>
                        <?php foreach ($testimonials as $t): ?>
                            <div class="testimonial-card" style="position: relative;">
                                <?php if ($isLoggedInAdmin): ?>
                                    <div class="admin-item-actions">
                                        <button type="button" title="Edit Testimonial"
                                            onclick='openEditTestimonialModal(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i
                                                class="fas fa-edit"></i></button>
                                        <form action="<?= SITE_URL ?>#testimonials" method="POST"
                                            onsubmit="return confirm('Delete testimonial?');" style="margin:0;">
                                            <input type="hidden" name="action" value="quick_delete_testimonial">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" title="Delete" style="background:#E61A27;"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                <i class="fas fa-quote-left"></i>
                                <div class="stars">
                                    <?php for ($i = 0; $i < intval($t['rating'] ?? 5); $i++): ?><i
                                            class="fas fa-star"></i><?php endfor; ?>
                                </div>
                                <p class="quote">"<?= htmlspecialchars($t['content']) ?>"</p>
                                <div class="author">
                                    <div class="avatar">
                                        <?php
                                        $words = explode(' ', $t['name']);
                                        $initials = '';
                                        foreach ($words as $w) {
                                            $initials .= strtoupper(substr($w, 0, 1));
                                        }
                                        echo htmlspecialchars(substr($initials, 0, 2) ?: 'CL');
                                        ?>
                                    </div>
                                    <div class="info">
                                        <div class="name"><?= htmlspecialchars($t['name']) ?></div>
                                        <div class="company">
                                            <?= htmlspecialchars($t['company'] ?? '') ?> <?= !empty($t['position']) ? ', ' . htmlspecialchars($t['position']) : '' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback static cards shown only when DB has zero testimonials -->
                        <div class="testimonial-card">
                            <i class="fas fa-quote-left"></i>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                    class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                            <p class="quote">"Pipani Advertising delivered exceptional results for our brand. Their transit
                                media campaign reached millions and drove significant offline footfall to our stores."</p>
                            <div class="author">
                                <div class="avatar">RK</div>
                                <div class="info">
                                    <div class="name">Rahul Khanna</div>
                                    <div class="company">Marketing Head, Brand X</div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-card">
                            <i class="fas fa-quote-left"></i>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                    class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                            <p class="quote">"The team's professional expertise in outdoor and digital media helped us
                                achieve 3x ROI on our advertising spend this quarter. Highly recommended for scalable
                                growth!"</p>
                            <div class="author">
                                <div class="avatar">PM</div>
                                <div class="info">
                                    <div class="name">Priya Mehta</div>
                                    <div class="company">CEO, Brand Y</div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-card">
                            <i class="fas fa-quote-left"></i>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                    class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                            </div>
                            <p class="quote">"From initial strategy to the final hoardings, Pipani's full-service approach
                                made our product launch completely seamless and highly effective."</p>
                            <div class="author">
                                <div class="avatar">AS</div>
                                <div class="info">
                                    <div class="name">Amit Singh</div>
                                    <div class="company">Brand Manager, Brand Z</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <button class="slider-btn large next" id="testimonialNextBtn" aria-label="Next testimonial">
                    <i class="fas fa-chevron-right"></i>
                </button>

            </div><!-- /.testimonials-slider-wrapper -->
        </div>
    </section>

    <!-- ===== FAQ ===== -->
    <section class="faq" id="faq" aria-label="Frequently Asked Questions" style="position: relative;">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">FAQs</span>
                <h2 class="section-title">Common <span class="highlight">Questions</span></h2>
                <p class="section-subtitle mx-auto">Find quick answers to the most frequently asked questions regarding
                    our services.</p>
                <?php if ($isLoggedInAdmin): ?>
                    <button type="button" class="admin-quick-add-btn" onclick="openAddFaqModal()"><i
                            class="fas fa-plus-circle"></i> Add FAQ</button>
                <?php endif; ?>
            </div>
            <div class="faq-list" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($faqs)): ?>
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="faq-item <?= $index === 0 ? 'active' : '' ?>" style="position: relative;">
                            <?php if ($isLoggedInAdmin): ?>
                                <div class="admin-item-actions" style="top:10px; right:30px;">
                                    <button type="button" title="Edit FAQ" style="width:24px;height:24px;font-size:0.65rem;"
                                        onclick='openEditFaqModal(<?= htmlspecialchars(json_encode($faq, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>); event.stopPropagation();'><i
                                            class="fas fa-edit"></i></button>
                                    <form action="<?= SITE_URL ?>#faq" method="POST"
                                        onsubmit="event.stopPropagation(); return confirm('Delete FAQ?');" style="margin:0;">
                                        <input type="hidden" name="action" value="quick_delete_faq">
                                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                        <button type="submit" title="Delete"
                                            style="width:24px;height:24px;font-size:0.65rem;background:#E61A27;"
                                            onclick="event.stopPropagation();"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <div class="question">
                                <span><?= htmlspecialchars($faq['question']) ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="answer"><?= nl2br(htmlspecialchars($faq['answer'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="faq-item active">
                        <div class="question">
                            <span>What advertising services do you offer?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="answer">We offer a comprehensive range of services including Offline Media, Transit
                            Media, Electronic Media, Cinema Branding, Ambient Media, Print Media, Non-Traditional Media,
                            Corporate Gifting, and Sports & Showbiz advertising. Each service is tailored to meet your
                            specific brand objectives.</div>
                    </div>
                    <div class="faq-item">
                        <div class="question">
                            <span>Which cities do you operate in?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="answer">We have a strong presence across India with a particular focus on Pune, Mumbai,
                            Delhi, and other major metropolitan cities. Our railway station branding covers key stations
                            like Pune, Pimpri, Chinchwad, Vadgaon, Lonavala, and many more across the country.</div>
                    </div>
                    <div class="faq-item">
                        <div class="question">
                            <span>What is the minimum budget for an advertising campaign?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="answer">Our advertising solutions are highly scalable. We work with brands of all sizes,
                            from small local businesses to large corporate entities. Contact us directly for a customized
                            quote based on your specific requirements and overall campaign objectives.</div>
                    </div>
                    <div class="faq-item">
                        <div class="question">
                            <span>How do I get started with Pipani Advertising?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="answer">Simply reach out through our contact form below or call our co-founders
                            directly. We'll schedule an initial consultation to understand your needs and propose the best
                            advertising strategy for your brand. You can also email us at yasin@pipaniadvertising.com.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== CONTACT ===== -->
    <section class="contact" id="contact" aria-label="Contact Us" style="position: relative;">
        <?php if ($isLoggedInAdmin): ?>
            <button type="button" class="admin-quick-btn" onclick="openEditContactSettingsModal()"><i
                    class="fas fa-edit"></i> Edit Contact Settings</button>
        <?php endif; ?>
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <span class="section-badge">Get In Touch</span>
                <h2 class="section-title">Let's Create <span class="highlight">Massive Impact</span></h2>
                <p class="section-subtitle mx-auto">Ready to elevate your brand to the next level? Reach out to our
                    executive team today.</p>
            </div>

            <div class="contact-grid" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-info-blocks">
                    <div class="contact-card">
                        <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                        <div>
                            <h3>Yasin Jamadar</h3>
                            <p class="role">Co-Founder | Management</p>
                            <div class="contact-links">
                                <a href="tel:+919766840787"><i class="fas fa-phone"></i> +91 9766840787</a>
                                <a href="mailto:yasin@pipaniadvertising.com"><i class="fas fa-envelope"></i>
                                    yasin@pipaniadvertising.com</a>
                            </div>
                        </div>
                    </div>
                    <div class="contact-card">
                        <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                        <div>
                            <h3>Iqbal Shaikh</h3>
                            <p class="role">Co-Founder | Management</p>
                            <div class="contact-links">
                                <a href="tel:+919511244940"><i class="fas fa-phone"></i> +91 9511244940</a>
                                <a href="mailto:info.adnestcommunication@gmail.com"><i class="fas fa-envelope"></i>
                                    info.adnestcommunication@gmail.com</a>
                            </div>
                        </div>
                    </div>
                    <div class="contact-card">
                        <div class="card-icon"><i class="fas fa-building"></i></div>
                        <div>
                            <h3>Adnest Communications</h3>
                            <p class="role">LLP - PIPANI Group | Corporate Office</p>
                            <div class="contact-links">
                                <p><i class="fas fa-map-marker-alt" style="color:var(--primary)"></i>
                                    <?= htmlspecialchars($contactAddress ?: 'Dhankawadi, Pune, Maharashtra 411043') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form-wrapper">
                    <div class="contact-form-header">
                        <h3>Send a Direct Message</h3>
                    </div>
                    <form class="contact-form" action="<?= SITE_URL ?>#contact" method="POST">
                        <input type="hidden" name="action" value="submit_contact">
                        <?php if ($contactSuccess): ?>
                            <div class="alert-banner success"
                                style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:rgba(16,185,129,0.15);color:#10B981;border:1px solid rgba(16,185,129,0.3);">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($contactSuccess) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($contactError): ?>
                            <div class="alert-banner error"
                                style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:rgba(230,26,39,0.15);color:#E61A27;border:1px solid rgba(230,26,39,0.3);">
                                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($contactError) ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <input type="text" name="name" placeholder="Your Full Name" required />
                            <input type="email" name="email" placeholder="Your Email Address" required />
                        </div>
                        <input type="tel" name="phone" placeholder="Phone Number (Optional)" />
                        <textarea name="message" placeholder="Tell us about your advertising needs..."
                            required></textarea>
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">
                            Send Message <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="contact-map" data-aos="fade-up" data-aos-delay="200">
                <iframe
                    src="https://maps.google.com/maps?q=Pipani%20Advertising%20Dhankawadi%20Pune&t=&z=15&ie=UTF8&iwloc=&output=embed"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer" id="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="#" class="logo">
                        <?php if (!empty($logo)): ?>
                            <img src="<?= resolveImgUrl($logo) ?>" alt="<?= htmlspecialchars($siteName) ?>" />
                        <?php else: ?>
                            <?= htmlspecialchars($siteName) ?><span>.</span>
                        <?php endif; ?>
                    </a>
                    <p><?= htmlspecialchars($footerText ?: "8 Years of making a Massive Impact. India's premier full-service advertising agency delivering measurable results across all media channels.") ?>
                    </p>
                    <div class="socials" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;">
                        <?php if (!empty($socialLinks)): ?>
                            <?php foreach ($socialLinks as $sl): ?>
                                <span style="position:relative; display:inline-block;">
                                    <a href="<?= htmlspecialchars($sl['url']) ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars($sl['platform']) ?>"><i class="<?= htmlspecialchars($sl['icon']) ?>"></i></a>
                                    <?php if ($isLoggedInAdmin): ?>
                                        <div class="admin-item-actions" style="top:-35px; left:50%; transform:translateX(-50%); flex-direction:row; padding:4px; gap:4px; min-width:60px;">
                                            <button type="button" title="Edit Link" style="width:20px;height:20px;font-size:0.55rem;" onclick='openEditSocialLinkModal(<?= htmlspecialchars(json_encode($sl, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i class="fas fa-pencil-alt"></i></button>
                                            <form action="<?= SITE_URL ?>#footer" method="POST" style="display:inline;" onsubmit="return confirm('Delete social link?');">
                                                <input type="hidden" name="action" value="delete_social_link">
                                                <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                                                <button type="submit" title="Delete Link" style="width:20px;height:20px;font-size:0.55rem;background:#fee2e2;color:#ef4444;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://www.instagram.com/pipaniads?igsh=MXNnOHM5MnczMGRvdw==" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                        <?php if ($isLoggedInAdmin): ?>
                            <button type="button" title="Add Social Link" style="background:#dcfce7;color:#22c55e;border-radius:50%;width:35px;height:35px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;font-size:1.2rem;" onclick="openAddSocialLinkModal()"><i class="fas fa-plus"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isLoggedInAdmin): ?>
                    <div class="footer-links-col" style="display:flex;align-items:center;justify-content:center;">
                        <button type="button" class="admin-quick-add-btn" onclick="openAddFooterColModal()" style="margin:0;"><i class="fas fa-plus-circle"></i> Add Column</button>
                    </div>
                <?php endif; ?>
                <?php foreach ($footerCols as $col): ?>
                    <div class="footer-links-col" style="position:relative;">
                        <?php if ($isLoggedInAdmin): ?>
                            <div class="admin-item-actions" style="top:-10px; right:0;">
                                <button type="button" title="Edit Column" style="width:24px;height:24px;font-size:0.65rem;" onclick='openEditFooterColModal(<?= htmlspecialchars(json_encode($col, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i class="fas fa-pencil-alt"></i></button>
                                <form action="<?= SITE_URL ?>#footer" method="POST" style="display:inline;" onsubmit="return confirm('Delete column and all its links?');">
                                    <input type="hidden" name="action" value="delete_footer_column">
                                    <input type="hidden" name="id" value="<?= $col['id'] ?>">
                                    <button type="submit" title="Delete Column" style="width:24px;height:24px;font-size:0.65rem;background:#fee2e2;color:#ef4444;"><i class="fas fa-trash"></i></button>
                                </form>
                                <button type="button" title="Add Link" style="width:24px;height:24px;font-size:0.65rem;background:#dcfce7;color:#22c55e;" onclick='openAddFooterLinkModal(<?= $col['id'] ?>)'><i class="fas fa-plus"></i></button>
                            </div>
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($col['title']) ?></h4>
                        <ul>
                            <?php foreach ($col['links'] as $link): ?>
                                <li style="position:relative; padding-right:40px;">
                                    <a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a>
                                    <?php if ($isLoggedInAdmin): ?>
                                        <div class="admin-item-actions" style="top:50%; transform:translateY(-50%); right:0; flex-direction:row;">
                                            <button type="button" title="Edit Link" style="width:20px;height:20px;font-size:0.55rem;" onclick='openEditFooterLinkModal(<?= htmlspecialchars(json_encode($link, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>)'><i class="fas fa-pencil-alt"></i></button>
                                            <form action="<?= SITE_URL ?>#footer" method="POST" style="display:inline;" onsubmit="return confirm('Delete link?');">
                                                <input type="hidden" name="action" value="delete_footer_link">
                                                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                                <button type="submit" title="Delete Link" style="width:20px;height:20px;font-size:0.55rem;background:#fee2e2;color:#ef4444;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
                <div class="footer-newsletter">
                    <h4>Newsletter</h4>
                    <p>Get the latest updates on campaigns and advertising trends straight to your inbox.</p>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Your email address" />
                        <button type="button">Subscribe</button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. A Part of the PIPANI ADVERTISING
                    Group. All Rights Reserved.</span>
                <div class="links">
                    <?php foreach ($footerBottomLinks as $link): ?>
                        <a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- ===== BACK TO TOP ===== -->
    <button class="back-to-top" id="backToTop" aria-label="Back to Top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- ===== SECTION 16: ADMIN MODALS (WHEN LOGGED IN) ===== -->
    <?php if ($isLoggedInAdmin): ?>
        <!-- Edit Logo Modal -->
        <div class="cms-modal-overlay" id="editLogoModal">
            <div class="cms-modal-card" style="max-width:460px;">
                <div class="cms-modal-header">
                    <h3><i class="fas fa-image" style="margin-right:8px;"></i>Edit Navbar Logo</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editLogoModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#" method="POST" enctype="multipart/form-data" id="editLogoForm">
                    <input type="hidden" name="action" value="quick_edit_logo">
                    
                    <?php $curLogoDisplayType = getSetting('logo_display_type', 'both'); ?>
                    <div class="cms-form-group">
                        <label>Display Style</label>
                        <select name="logo_display_type" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1px solid var(--border-light);">
                            <option value="both" <?= $curLogoDisplayType === 'both' ? 'selected' : '' ?>>Show Logo and Name</option>
                            <option value="logo_only" <?= $curLogoDisplayType === 'logo_only' ? 'selected' : '' ?>>Show Logo Only</option>
                            <option value="name_only" <?= $curLogoDisplayType === 'name_only' ? 'selected' : '' ?>>Show Name Only</option>
                        </select>
                    </div>

                    <div class="cms-form-group">
                        <label>Current Logo</label>
                        <div style="background:var(--primary-gradient);padding:12px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;margin-bottom:8px;">
                            <?php
                            $curLogo = getSetting('logo', '');
                            $curLogoUrl = !empty($curLogo) ? resolveImgUrl($curLogo) : resolveImgUrl('logo.png');
                            ?>
                            <img src="<?= $curLogoUrl ?>" id="logoCurrentPreview" alt="Current Logo" style="max-height:60px;width:auto;object-fit:contain;">
                        </div>
                    </div>
                    <div class="cms-form-group">
                        <label>Upload New Logo</label>
                        <input type="file" name="logo_image" id="logoFileInput" accept="image/*" onchange="previewLogoImage(this)" style="width:100%;">
                        <small style="color:var(--text-muted);">Supported: JPG, PNG, SVG, WEBP. Max 5MB.</small>
                    </div>
                    <div class="cms-form-group" id="logoPreviewWrap" style="display:none;">
                        <label>Preview</label>
                        <div style="background:var(--primary-gradient);padding:12px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;">
                            <img id="logoNewPreview" src="" alt="New Logo Preview" style="max-height:60px;width:auto;object-fit:contain;">
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px;">
                        <button type="submit" class="btn-primary" style="flex:1;justify-content:center;"><i class="fas fa-save"></i> Save Logo</button>
                        <button type="button" onclick="closeModal('editLogoModal')" style="flex:1;padding:12px;border:1px solid var(--border-light);border-radius:var(--radius-sm);background:#fff;cursor:pointer;font-weight:600;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hero Modal -->
        <div class="cms-modal-overlay" id="heroModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit Hero Section</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('heroModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#hero" method="POST">
                    <input type="hidden" name="action" value="quick_edit_hero">
                    <div class="cms-form-group">
                        <label>Hero Badge</label>
                        <input type="text" name="hero_badge"
                            value="<?= htmlspecialchars($heroBadge['content'] ?? '7 Years of Excellence') ?>">
                    </div>
                    <div class="cms-form-group">
                        <label>Hero Title (Supports HTML &amp; .highlight)</label>
                        <textarea name="hero_title"
                            rows="3"><?= htmlspecialchars($heroTitle['content'] ?? "Making a <br />\n<span class=\"highlight\">\nMassive Impact\n<span class=\"underline\"></span>\n</span>\nSince 2017") ?></textarea>
                    </div>
                    <div class="cms-form-group">
                        <label>Hero Subtitle</label>
                        <textarea name="hero_subtitle"
                            rows="3"><?= htmlspecialchars($heroSubtitle['content'] ?? "India's premier full-service advertising agency...") ?></textarea>
                    </div>
                    <div class="cms-form-group">
                        <label>Trust Items (3 items)</label>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div style="display:flex;gap:8px;margin-bottom:8px;">
                                <input type="text" name="hero_trust_icon[]"
                                    value="<?= htmlspecialchars($trustItemsData[$i]['icon'] ?? 'fas fa-check-circle') ?>"
                                    style="width:40%;">
                                <input type="text" name="hero_trust_text[]"
                                    value="<?= htmlspecialchars($trustItemsData[$i]['text'] ?? '') ?>" style="width:60%;">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save
                        Changes</button>
                </form>
            </div>
        </div>

        <!-- About Modal -->
        <!-- About Modal -->
        <div class="cms-modal-overlay" id="aboutModal">
            <div class="cms-modal-card cms-modal-wide">
                <div class="cms-modal-header">
                    <h3>Edit About Us</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('aboutModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#about" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="quick_edit_about">
                    <div class="cms-form-group">
                        <label>About Image</label>
                        <div class="cms-img-preview-box">
                            <img src="<?= htmlspecialchars($aboutImgUrl) ?>" alt="Current About Image" id="aboutImgPreview">
                            <div>
                                <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px;color:#1e293b;">Current
                                    Image</div>
                                <small
                                    style="color:#64748b;"><?= ($aboutImage && !empty($aboutImage['content'])) ? 'Custom uploaded image' : 'Default team image' ?></small>
                            </div>
                        </div>
                        <label style="margin-top:10px;">Upload / Replace Image</label>
                        <input type="file" name="about_image" accept="image/*">
                        <?php if ($aboutImage && !empty($aboutImage['content'])): ?>
                            <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="remove_about_image" id="remove_about_image" value="1"
                                    style="width:auto;cursor:pointer;">
                                <label for="remove_about_image"
                                    style="margin-bottom:0;font-weight:normal;color:#dc2626;cursor:pointer;font-size:0.82rem;">Remove
                                    custom image and revert to default</label>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="cms-form-group">
                        <label>Tagline</label>
                        <input type="text" name="about_subtitle"
                            value="<?= htmlspecialchars($aboutSubtitle['content'] ?? 'Advertising · PR · Massive Impact') ?>">
                    </div>
                    <div class="cms-form-group">
                        <label>Main About Content</label>
                        <textarea name="about_content"
                            rows="6"><?= htmlspecialchars($aboutContent['content'] ?? "For over 8 years, Pipani Advertising has been at the forefront of the advertising industry...") ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save
                        Changes</button>
                </form>
            </div>
        </div>

        <!-- Add Service Modal -->
        <div class="cms-modal-overlay" id="addServiceModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Add New Service</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addServiceModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#services" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="quick_add_service">
                    <div class="cms-form-group">
                        <label>Service Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Icon Class (FontAwesome)</label>
                        <input type="text" name="icon" value="fas fa-bullhorn">
                    </div>
                    <div class="cms-form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    <div class="cms-form-group">
                        <label>Benefits (one per line)</label>
                        <textarea name="benefits" rows="2" placeholder="71% Read Rate&#10;High Visibility"></textarea>
                    </div>
                    <div class="cms-form-group">
                        <label>Order</label>
                        <input type="number" name="order" value="0">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Create
                        Service</button>
                </form>
            </div>
        </div>

        <!-- Edit Service Modal -->
        <div class="cms-modal-overlay" id="editServiceModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit Service</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editServiceModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#services" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="quick_edit_service">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="cms-form-group">
                        <label>Service Title</label>
                        <input type="text" name="title" id="edit_service_title" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Icon Class</label>
                        <input type="text" name="icon" id="edit_service_icon">
                    </div>
                    <div class="cms-form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_service_description" rows="3"></textarea>
                    </div>
                    <div class="cms-form-group">
                        <label>Benefits (one per line)</label>
                        <textarea name="benefits" id="edit_service_benefits" rows="2"></textarea>
                    </div>
                    <div class="cms-form-group">
                        <label>Order</label>
                        <input type="number" name="order" id="edit_service_order">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update
                        Service</button>
                </form>
            </div>
        </div>

        <!-- Add Portfolio Modal -->
        <div class="cms-modal-overlay" id="addPortfolioModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Add Portfolio Item</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addPortfolioModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#portfolio" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="quick_add_portfolio">
                    <div class="cms-form-group">
                        <label>Campaign Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Category (Tag)</label>
                        <input type="text" name="category" placeholder="Outdoor, Transit, Cinema...">
                    </div>
                    <div class="cms-form-group">
                        <label>Description / Subtitle</label>
                        <input type="text" name="description">
                    </div>
                    <div class="cms-form-group">
                        <label>Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add Item</button>
                </form>
            </div>
        </div>

        <!-- Edit Portfolio Modal -->
        <div class="cms-modal-overlay" id="editPortfolioModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit Portfolio Item</h3>
                    <button type="button" class="cms-modal-close"
                        onclick="closeModal('editPortfolioModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#portfolio" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="quick_edit_portfolio">
                    <input type="hidden" name="id" id="edit_portfolio_id">
                    <div class="cms-form-group">
                        <label>Campaign Title</label>
                        <input type="text" name="title" id="edit_portfolio_title" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Category</label>
                        <input type="text" name="category" id="edit_portfolio_category">
                    </div>
                    <div class="cms-form-group">
                        <label>Description</label>
                        <input type="text" name="description" id="edit_portfolio_description">
                    </div>
                    <div class="cms-form-group">
                        <label>Replace Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update
                        Item</button>
                </form>
            </div>
        </div>

        <!-- Add Industry Modal -->
        <div class="cms-modal-overlay" id="addIndustryModal">
            <div class="cms-modal-card cms-modal-wide">
                <div class="cms-modal-header">
                    <h3>Add Industry</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addIndustryModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#industries" method="POST">
                    <input type="hidden" name="action" value="quick_add_industry">
                    <div class="cms-form-group">
                        <label>Industry Name</label>
                        <input type="text" name="name" required placeholder="e.g. Real Estate &amp; Construction">
                    </div>
                    <div class="cms-form-group">
                        <label>Select Icon</label>
                        <input type="hidden" name="icon" id="add_industry_icon" value="fas fa-building">
                        <div class="cms-icon-preview-badge" id="add_industry_icon_preview">
                            <i class="fas fa-building"></i>
                            <span>Selected: <strong>Real Estate / Building</strong></span>
                        </div>
                        <div class="cms-icon-picker">
                            <div class="cms-icon-search-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search icons (e.g. bank, hospital, retail, car, food)..."
                                    oninput="filterIconPicker(this, 'add_industry_grid')">
                            </div>
                            <div class="cms-icon-grid" id="add_industry_grid"></div>
                        </div>
                    </div>
                    <div class="cms-form-group">
                        <label>Display Order</label>
                        <input type="number" name="order" value="0">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add
                        Industry</button>
                </form>
            </div>
        </div>

        <!-- Edit Industry Modal -->
        <div class="cms-modal-overlay" id="editIndustryModal">
            <div class="cms-modal-card cms-modal-wide">
                <div class="cms-modal-header">
                    <h3>Edit Industry</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editIndustryModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#industries" method="POST">
                    <input type="hidden" name="action" value="quick_edit_industry">
                    <input type="hidden" name="id" id="edit_industry_id">
                    <div class="cms-form-group">
                        <label>Industry Name</label>
                        <input type="text" name="name" id="edit_industry_name" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Select Icon</label>
                        <input type="hidden" name="icon" id="edit_industry_icon">
                        <div class="cms-icon-preview-badge" id="edit_industry_icon_preview">
                            <i class="fas fa-building"></i>
                            <span>Selected: <strong>Building</strong></span>
                        </div>
                        <div class="cms-icon-picker">
                            <div class="cms-icon-search-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search icons (e.g. bank, hospital, retail, car, food)..."
                                    oninput="filterIconPicker(this, 'edit_industry_grid')">
                            </div>
                            <div class="cms-icon-grid" id="edit_industry_grid"></div>
                        </div>
                    </div>
                    <div class="cms-form-group">
                        <label>Display Order</label>
                        <input type="number" name="order" id="edit_industry_order">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update
                        Industry</button>
                </form>
            </div>
        </div>

        <!-- Add Stat Modal -->
        <div class="cms-modal-overlay" id="addStatModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Add Statistic</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addStatModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#statistics" method="POST">
                    <input type="hidden" name="action" value="quick_add_statistics">
                    <div class="cms-form-group">
                        <label>Label</label>
                        <input type="text" name="label" required placeholder="e.g. Campaigns Executed">
                    </div>
                    <div class="cms-form-group">
                        <label>Value (Number)</label>
                        <input type="number" name="value" required placeholder="500">
                    </div>
                    <div class="cms-form-group">
                        <label>Suffix</label>
                        <input type="text" name="suffix" value="+" placeholder="+ or M+">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add Stat</button>
                </form>
            </div>
        </div>

        <!-- Edit Stat Modal -->
        <div class="cms-modal-overlay" id="editStatModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit Statistic</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editStatModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#statistics" method="POST">
                    <input type="hidden" name="action" value="quick_edit_statistics">
                    <input type="hidden" name="id" id="edit_stat_id">
                    <div class="cms-form-group">
                        <label>Label</label>
                        <input type="text" name="label" id="edit_stat_label" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Value (Number)</label>
                        <input type="number" name="value" id="edit_stat_value" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Suffix</label>
                        <input type="text" name="suffix" id="edit_stat_suffix">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update
                        Stat</button>
                </form>
            </div>
        </div>

        <!-- Add Testimonial Modal -->
        <div class="cms-modal-overlay" id="addTestimonialModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Add Testimonial</h3>
                    <button type="button" class="cms-modal-close"
                        onclick="closeModal('addTestimonialModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#testimonials" method="POST">
                    <input type="hidden" name="action" value="quick_add_testimonial">
                    <div class="cms-form-group">
                        <label>Client Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Company / Brand</label>
                        <input type="text" name="company" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Position</label>
                        <input type="text" name="position" placeholder="e.g. Marketing Head">
                    </div>
                    <div class="cms-form-group">
                        <label>Rating (1-5)</label>
                        <input type="number" name="rating" min="1" max="5" value="5">
                    </div>
                    <div class="cms-form-group">
                        <label>Quote Content</label>
                        <textarea name="content" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add
                        Testimonial</button>
                </form>
            </div>
        </div>

        <!-- Edit Testimonial Modal -->
        <div class="cms-modal-overlay" id="editTestimonialModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit Testimonial</h3>
                    <button type="button" class="cms-modal-close"
                        onclick="closeModal('editTestimonialModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#testimonials" method="POST">
                    <input type="hidden" name="action" value="quick_edit_testimonial">
                    <input type="hidden" name="id" id="edit_testimonial_id">
                    <div class="cms-form-group">
                        <label>Client Name</label>
                        <input type="text" name="name" id="edit_testimonial_name" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Company / Brand</label>
                        <input type="text" name="company" id="edit_testimonial_company">
                    </div>
                    <div class="cms-form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="edit_testimonial_position">
                    </div>
                    <div class="cms-form-group">
                        <label>Rating (1-5)</label>
                        <input type="number" name="rating" id="edit_testimonial_rating" min="1" max="5">
                    </div>
                    <div class="cms-form-group">
                        <label>Quote Content</label>
                        <textarea name="content" id="edit_testimonial_content" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update
                        Testimonial</button>
                </form>
            </div>
        </div>

        <!-- Add FAQ Modal -->
        <div class="cms-modal-overlay" id="addFaqModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Add FAQ</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addFaqModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#faq" method="POST">
                    <input type="hidden" name="action" value="quick_add_faq">
                    <div class="cms-form-group">
                        <label>Question</label>
                        <input type="text" name="question" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Answer</label>
                        <textarea name="answer" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add FAQ</button>
                </form>
            </div>
        </div>

        <!-- Edit FAQ Modal -->
        <div class="cms-modal-overlay" id="editFaqModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit FAQ</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editFaqModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#faq" method="POST">
                    <input type="hidden" name="action" value="quick_edit_faq">
                    <input type="hidden" name="id" id="edit_faq_id">
                    <div class="cms-form-group">
                        <label>Question</label>
                        <input type="text" name="question" id="edit_faq_question" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Answer</label>
                        <textarea name="answer" id="edit_faq_answer" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update FAQ</button>
                </form>
            </div>
        </div>

        <!-- Contact Settings Modal -->
        <div class="cms-modal-overlay" id="contactSettingsModal">
            <div class="cms-modal-card">
                <div class="cms-modal-header">
                    <h3>Edit Contact Settings</h3>
                    <button type="button" class="cms-modal-close"
                        onclick="closeModal('contactSettingsModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#contact" method="POST">
                    <input type="hidden" name="action" value="quick_edit_contact_settings">
                    <div class="cms-form-group">
                        <label>Site Email</label>
                        <input type="email" name="site_email" value="<?= htmlspecialchars($contactEmail) ?>" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Site Phone</label>
                        <input type="text" name="site_phone" value="<?= htmlspecialchars($contactPhone) ?>" required>
                    </div>
                    <div class="cms-form-group">
                        <label>Site Address</label>
                        <textarea name="site_address" rows="3" required><?= htmlspecialchars($contactAddress) ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save
                        Settings</button>
                </form>
            </div>
        </div>

        <!-- Clients Modal -->
        <div class="cms-modal-overlay" id="clientsModal">
            <div class="cms-modal-card cms-modal-wide">
                <div class="cms-modal-header">
                    <h3>Edit Clients Marquee</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('clientsModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#clients" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="quick_edit_clients">

                    <div class="cms-repeater-list" id="clientsListContainer">
                        <!-- Dynamic client items populated by JS -->
                    </div>

                    <button type="button" class="cms-btn-add" onclick="addClientRow()">
                        <i class="fas fa-plus"></i> Add Client
                    </button>

                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save
                        Clients</button>
                </form>
            </div>
        </div>

        <!-- Process Modal -->
        <div class="cms-modal-overlay" id="processModal">
            <div class="cms-modal-card cms-modal-wide">
                <div class="cms-modal-header">
                    <h3>Edit Process Steps</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('processModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#process" method="POST" onsubmit="return prepareProcessJson()">
                    <input type="hidden" name="action" value="quick_edit_json_setting">
                    <input type="hidden" name="setting_key" value="process_data">
                    <input type="hidden" name="section" value="process">
                    <input type="hidden" name="json_data" id="process_json_data">

                    <div class="cms-repeater-list" id="processListContainer">
                        <!-- Dynamic process items populated by JS -->
                    </div>

                    <button type="button" class="cms-btn-add" onclick="addProcessRow()">
                        <i class="fas fa-plus"></i> Add Process Step
                    </button>

                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Process
                        Steps</button>
                </form>
            </div>
        </div>

        <!-- Why Us Modal -->
        <div class="cms-modal-overlay" id="whyModal">
            <div class="cms-modal-card cms-modal-wide">
                <div class="cms-modal-header">
                    <h3>Edit Why Choose Us</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('whyModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#why" method="POST" onsubmit="return prepareWhyJson()">
                    <input type="hidden" name="action" value="quick_edit_json_setting">
                    <input type="hidden" name="setting_key" value="why_data">
                    <input type="hidden" name="section" value="why">
                    <input type="hidden" name="json_data" id="why_json_data">

                    <div class="cms-repeater-list" id="whyListContainer">
                        <!-- Dynamic why items populated by JS -->
                    </div>

                    <button type="button" class="cms-btn-add" onclick="addWhyRow()">
                        <i class="fas fa-plus"></i> Add Feature Card
                    </button>

                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Why
                        Us</button>
                </form>
            </div>
        </div>
        <!-- Add Footer Column Modal -->
        <div class="cms-modal-overlay" id="addFooterColModal">
            <div class="cms-modal-card" style="max-width:400px;">
                <div class="cms-modal-header">
                    <h3>Add Footer Column</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addFooterColModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#footer" method="POST">
                    <input type="hidden" name="action" value="add_footer_column">
                    <div style="margin-bottom:10px;">
                        <label>Title *</label>
                        <input type="text" name="title" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Order</label>
                        <input type="number" name="order" value="0" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add Column</button>
                </form>
            </div>
        </div>

        <!-- Edit Footer Column Modal -->
        <div class="cms-modal-overlay" id="editFooterColModal">
            <div class="cms-modal-card" style="max-width:400px;">
                <div class="cms-modal-header">
                    <h3>Edit Footer Column</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editFooterColModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#footer" method="POST">
                    <input type="hidden" name="action" value="edit_footer_column">
                    <input type="hidden" name="id" id="edit_footer_col_id">
                    <div style="margin-bottom:10px;">
                        <label>Title *</label>
                        <input type="text" name="title" id="edit_footer_col_title" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Order</label>
                        <input type="number" name="order" id="edit_footer_col_order" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Add Footer Link Modal -->
        <div class="cms-modal-overlay" id="addFooterLinkModal">
            <div class="cms-modal-card" style="max-width:400px;">
                <div class="cms-modal-header">
                    <h3>Add Footer Link</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addFooterLinkModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#footer" method="POST">
                    <input type="hidden" name="action" value="add_footer_link">
                    <input type="hidden" name="column_id" id="add_footer_link_col_id">
                    <div style="margin-bottom:10px;">
                        <label>Label *</label>
                        <input type="text" name="label" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>URL *</label>
                        <input type="text" name="url" placeholder="e.g. #services or /page" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Order</label>
                        <input type="number" name="order" value="0" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add Link</button>
                </form>
            </div>
        </div>

        <!-- Edit Footer Link Modal -->
        <div class="cms-modal-overlay" id="editFooterLinkModal">
            <div class="cms-modal-card" style="max-width:400px;">
                <div class="cms-modal-header">
                    <h3>Edit Footer Link</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editFooterLinkModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#footer" method="POST">
                    <input type="hidden" name="action" value="edit_footer_link">
                    <input type="hidden" name="id" id="edit_footer_link_id">
                    <div style="margin-bottom:10px;">
                        <label>Label *</label>
                        <input type="text" name="label" id="edit_footer_link_label" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>URL *</label>
                        <input type="text" name="url" id="edit_footer_link_url" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Order</label>
                        <input type="number" name="order" id="edit_footer_link_order" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Add Social Link Modal -->
        <div class="cms-modal-overlay" id="addSocialLinkModal">
            <div class="cms-modal-card" style="max-width:400px;">
                <div class="cms-modal-header">
                    <h3>Add Social Link</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('addSocialLinkModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#footer" method="POST">
                    <input type="hidden" name="action" value="add_social_link">
                    <div style="margin-bottom:10px;">
                        <label>Platform *</label>
                        <input type="text" name="platform" placeholder="e.g. Instagram" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>URL *</label>
                        <input type="text" name="url" placeholder="https://..." required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>Icon Class *</label>
                        <input type="text" name="icon" placeholder="fab fa-instagram" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Order</label>
                        <input type="number" name="order" value="0" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Add Social Link</button>
                </form>
            </div>
        </div>

        <!-- Edit Social Link Modal -->
        <div class="cms-modal-overlay" id="editSocialLinkModal">
            <div class="cms-modal-card" style="max-width:400px;">
                <div class="cms-modal-header">
                    <h3>Edit Social Link</h3>
                    <button type="button" class="cms-modal-close" onclick="closeModal('editSocialLinkModal')">&times;</button>
                </div>
                <form action="<?= SITE_URL ?>#footer" method="POST">
                    <input type="hidden" name="action" value="edit_social_link">
                    <input type="hidden" name="id" id="edit_social_link_id">
                    <div style="margin-bottom:10px;">
                        <label>Platform *</label>
                        <input type="text" name="platform" id="edit_social_link_platform" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>URL *</label>
                        <input type="text" name="url" id="edit_social_link_url" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>Icon Class *</label>
                        <input type="text" name="icon" id="edit_social_link_icon" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Order</label>
                        <input type="number" name="order" id="edit_social_link_order" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Changes</button>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <!-- ===== SECTION 16: JAVASCRIPT ===== -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // ===== AOS INIT =====
        AOS.init({
            duration: 700,
            once: true,
            offset: 40,
            easing: 'ease-out-cubic'
        });

        // ===== SCROLL PROGRESS =====
        const scrollProgress = document.getElementById('scrollProgress');
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrollTop / docHeight) * 100;
            if (scrollProgress) scrollProgress.style.width = progress + '%';
        });

        // ===== NAVBAR SCROLL & ACTIVE STATE =====
        const navbar = document.getElementById('navbar');
        const navLinks = document.getElementById('navLinks');
        const menuToggle = document.getElementById('menuToggle');
        const sections = document.querySelectorAll('section[id]');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 120;
                if (window.scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });
            document.querySelectorAll('.nav-links a:not(.btn-nav)').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // ===== MOBILE MENU TOGGLE =====
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                menuToggle.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', () => {
                    menuToggle.classList.remove('active');
                    navLinks.classList.remove('active');
                });
            });
        }

        // ===== BACK TO TOP =====
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 400) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
            backToTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // ===== COUNTER ANIMATION =====
        const counters = document.querySelectorAll('.counter');
        let counted = false;

        if (counters.length > 0) {
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !counted) {
                        counted = true;
                        counters.forEach(counter => {
                            const target = parseInt(counter.getAttribute('data-target')) || 0;
                            const duration = 2000;
                            const startTime = performance.now();

                            function updateCounter(currentTime) {
                                const elapsed = currentTime - startTime;
                                const progress = Math.min(elapsed / duration, 1);
                                const value = Math.floor(progress * target);
                                counter.textContent = value;
                                if (progress < 1) {
                                    requestAnimationFrame(updateCounter);
                                } else {
                                    counter.textContent = target;
                                }
                            }
                            requestAnimationFrame(updateCounter);
                        });
                    }
                });
            }, {
                threshold: 0.3
            });

            counters.forEach(c => counterObserver.observe(c));
        }

        // ===== FAQ ACCORDION =====
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            item.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                faqItems.forEach(i => i.classList.remove('active'));
                if (!isActive) item.classList.add('active');
            });
        });

        // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const offset = 80;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // ===== MODAL UTILITIES =====
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('active');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        }
        document.querySelectorAll('.cms-modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });
        });

        // ===== CMS ICON PICKER DATA & HELPERS =====
        const CMS_ICONS = [{
                class: 'fas fa-building',
                name: 'Real Estate / Building',
                tags: 'building property real estate architecture tower office'
            },
            {
                class: 'fas fa-heart-pulse',
                name: 'Healthcare / Medical',
                tags: 'healthcare medical hospital doctor clinic health heart pulse'
            },
            {
                class: 'fas fa-graduation-cap',
                name: 'Education',
                tags: 'education school college university student study degree'
            },
            {
                class: 'fas fa-cart-shopping',
                name: 'Retail / Shopping',
                tags: 'retail shopping store mall market buy ecommerce cart'
            },
            {
                class: 'fas fa-car',
                name: 'Automotive',
                tags: 'automotive car vehicle drive motor automobile'
            },
            {
                class: 'fas fa-utensils',
                name: 'Food & Dining',
                tags: 'food restaurant dining cafe eatery drink chef'
            },
            {
                class: 'fas fa-coins',
                name: 'Finance & Banking',
                tags: 'finance banking money investment wealth bank coins'
            },
            {
                class: 'fas fa-landmark',
                name: 'Government & Public',
                tags: 'government landmark civic public law authority'
            },
            {
                class: 'fas fa-laptop-code',
                name: 'IT & Tech',
                tags: 'it tech technology software computer code laptop digital'
            },
            {
                class: 'fas fa-film',
                name: 'Entertainment & Media',
                tags: 'entertainment film cinema movie video media'
            },
            {
                class: 'fas fa-industry',
                name: 'Manufacturing',
                tags: 'manufacturing factory industry production plant industrial'
            },
            {
                class: 'fas fa-tshirt',
                name: 'Fashion & Apparel',
                tags: 'fashion apparel clothing style garment textile'
            },
            {
                class: 'fas fa-plane',
                name: 'Travel & Tourism',
                tags: 'travel tourism flight plane holiday vacation tour'
            },
            {
                class: 'fas fa-hotel',
                name: 'Hospitality',
                tags: 'hospitality hotel resort lodge stay accommodation'
            },
            {
                class: 'fas fa-truck',
                name: 'Logistics & Supply',
                tags: 'logistics supply shipping transport delivery freight'
            },
            {
                class: 'fas fa-bullhorn',
                name: 'Marketing & Ads',
                tags: 'marketing advertising promotion announcement megaphone bullhorn'
            },
            {
                class: 'fas fa-rectangle-ad',
                name: 'Billboard / Ads',
                tags: 'billboard advertisement banner hoarding display ad'
            },
            {
                class: 'fas fa-tv',
                name: 'Broadcasting & TV',
                tags: 'broadcasting tv television stream display screen'
            },
            {
                class: 'fas fa-newspaper',
                name: 'Print Media & News',
                tags: 'print media news newspaper press journal'
            },
            {
                class: 'fas fa-gift',
                name: 'Gifting & Events',
                tags: 'gifting gifts present reward corporate events'
            },
            {
                class: 'fas fa-gem',
                name: 'Jewelry & Luxury',
                tags: 'jewelry luxury premium diamond gem elegance'
            },
            {
                class: 'fas fa-leaf',
                name: 'Agriculture & Nature',
                tags: 'agriculture farming organic nature green leaf environment'
            },
            {
                class: 'fas fa-bolt',
                name: 'Energy & Power',
                tags: 'energy power electricity solar utility lightning bolt'
            },
            {
                class: 'fas fa-hand-holding-heart',
                name: 'NGO & Social Cause',
                tags: 'ngo charity social welfare help care donate non-profit'
            },
            {
                class: 'fas fa-shield-halved',
                name: 'Security & Legal',
                tags: 'security protection legal safety defense insurance'
            },
            {
                class: 'fas fa-briefcase',
                name: 'Corporate & Consulting',
                tags: 'corporate business consulting services management work'
            },
            {
                class: 'fas fa-chart-line',
                name: 'Growth & Analytics',
                tags: 'growth analytics statistics performance success sales chart'
            },
            {
                class: 'fas fa-eye',
                name: 'Visibility & Readership',
                tags: 'visibility view look impression see eye awareness'
            },
            {
                class: 'fas fa-rocket',
                name: 'Speed & Scale',
                tags: 'speed rocket launch scale boost fast'
            },
            {
                class: 'fas fa-clock',
                name: '24/7 & Timely',
                tags: 'clock time hour 24/7 round the clock duration'
            },
            {
                class: 'fas fa-star',
                name: 'Quality & Rating',
                tags: 'quality star rating review excellence premium'
            },
            {
                class: 'fas fa-lightbulb',
                name: 'Innovation & Creative',
                tags: 'innovation creative idea lightbulb design solution'
            },
            {
                class: 'fas fa-mobile-screen',
                name: 'Mobile & Apps',
                tags: 'mobile phone smartphone screen app telecom'
            },
            {
                class: 'fas fa-wifi',
                name: 'Telecom & Network',
                tags: 'wifi internet telecom connectivity network signal'
            },
            {
                class: 'fas fa-award',
                name: 'Awards & Recognition',
                tags: 'award trophy honor badge distinction winner'
            },
            {
                class: 'fas fa-handshake',
                name: 'Partnership',
                tags: 'partnership deal agreement trust collaboration client'
            }
        ];

        function renderIconPickerGrid(gridId, inputId, previewId, selectedClass) {
            const grid = document.getElementById(gridId);
            if (!grid) return;
            grid.innerHTML = '';

            CMS_ICONS.forEach(item => {
                const cell = document.createElement('div');
                cell.className = 'cms-icon-cell' + (item.class === selectedClass ? ' selected' : '');
                cell.dataset.class = item.class;
                cell.dataset.name = item.name;
                cell.dataset.tags = item.tags.toLowerCase();
                cell.innerHTML = `<i class="${item.class}"></i><span>${item.name.split('/')[0].trim()}</span>`;
                cell.onclick = function() {
                    grid.querySelectorAll('.cms-icon-cell').forEach(c => c.classList.remove('selected'));
                    cell.classList.add('selected');
                    const input = document.getElementById(inputId);
                    if (input) input.value = item.class;
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.innerHTML = `<i class="${item.class}"></i><span>Selected: <strong>${item.name}</strong></span>`;
                    }
                };
                grid.appendChild(cell);
            });

            // Update preview initial state
            const matched = CMS_ICONS.find(i => i.class === selectedClass) || {
                class: selectedClass || 'fas fa-check',
                name: selectedClass || 'Custom'
            };
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.innerHTML = `<i class="${matched.class}"></i><span>Selected: <strong>${matched.name}</strong></span>`;
            }
        }

        function filterIconPicker(searchInput, gridId) {
            const query = (searchInput.value || '').toLowerCase().trim();
            const grid = document.getElementById(gridId);
            if (!grid) return;
            grid.querySelectorAll('.cms-icon-cell').forEach(cell => {
                const text = (cell.dataset.name + ' ' + cell.dataset.tags + ' ' + cell.dataset.class).toLowerCase();
                cell.style.display = (!query || text.includes(query)) ? 'flex' : 'none';
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // ===== REPEATERS (CLIENTS, PROCESS, WHY) =====
        const INITIAL_CLIENTS_DATA = <?= json_encode($clientsData ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const INITIAL_PROCESS_DATA = <?= json_encode($processData ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const INITIAL_WHY_DATA = <?= json_encode($whyData ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        // --- Clients Repeater with Visual Logo / Icon Picker ---
        const CLIENT_ICON_PRESETS = [{
                value: 'fas fa-building',
                label: 'Corporate / Building (Tata, Reliance)'
            },
            {
                value: 'fas fa-signal',
                label: 'Telecom / Signal (Airtel)'
            },
            {
                value: 'fas fa-wifi',
                label: 'Broadband / Tech (Jio)'
            },
            {
                value: 'fas fa-utensils',
                label: 'Food & Dining (Zomato)'
            },
            {
                value: 'fas fa-cow',
                label: 'Dairy & FMCG (Amul)'
            },
            {
                value: 'fas fa-car',
                label: 'Automotive (Maruti Suzuki)'
            },
            {
                value: 'fas fa-mobile-alt',
                label: 'Mobile / Fintech (Paytm)'
            },
            {
                value: 'fas fa-university',
                label: 'Banking & Finance (HDFC Bank)'
            },
            {
                value: 'fas fa-laptop',
                label: 'IT & Software (Infosys)'
            },
            {
                value: 'fas fa-shopping-cart',
                label: 'Retail & E-commerce'
            },
            {
                value: 'fas fa-briefcase',
                label: 'Corporate & Business'
            },
            {
                value: 'fas fa-globe',
                label: 'Global / International'
            },
            {
                value: 'fas fa-shield-alt',
                label: 'Insurance & Security'
            },
            {
                value: 'fas fa-plane',
                label: 'Aviation & Travel'
            },
            {
                value: 'fas fa-gem',
                label: 'Luxury & Jewelry'
            },
            {
                value: 'fas fa-bolt',
                label: 'Energy & Power'
            },
            {
                value: 'fas fa-heartbeat',
                label: 'Healthcare & Pharma'
            },
            {
                value: 'fas fa-industry',
                label: 'Manufacturing & Industry'
            },
            {
                value: 'fas fa-award',
                label: 'Awards & Excellence'
            },
            {
                value: 'fas fa-star',
                label: 'Featured Brand'
            }
        ];

        function addClientRow(name = '', logo = '', icon = '') {
            const container = document.getElementById('clientsListContainer');
            if (!container) return;

            let activeIcon = icon || '';
            if (!activeIcon && name) {
                const defaultMap = {
                    'tata group': 'fas fa-building',
                    'reliance': 'fas fa-building',
                    'airtel': 'fas fa-signal',
                    'jio': 'fas fa-wifi',
                    'zomato': 'fas fa-utensils',
                    'amul': 'fas fa-cow',
                    'maruti suzuki': 'fas fa-car',
                    'paytm': 'fas fa-mobile-alt',
                    'hdfc bank': 'fas fa-university',
                    'infosys': 'fas fa-laptop'
                };
                activeIcon = defaultMap[name.toLowerCase()] || 'fas fa-building';
            }
            if (!activeIcon) activeIcon = 'fas fa-building';
            if (!activeIcon.startsWith('fa')) activeIcon = 'fas ' + activeIcon;

            const hasImageLogo = !!logo;
            const initialType = hasImageLogo ? 'image' : 'icon';

            let optionsHtml = '';
            let foundPreset = false;
            CLIENT_ICON_PRESETS.forEach(p => {
                const selected = (p.value === activeIcon || p.value.includes(activeIcon.replace('fas ', ''))) ? 'selected' : '';
                if (selected) foundPreset = true;
                optionsHtml += `<option value="${p.value}" ${selected}>${p.label}</option>`;
            });
            if (!foundPreset && activeIcon) {
                optionsHtml += `<option value="${activeIcon}" selected>${activeIcon} (Custom)</option>`;
            }

            const resolvedLogoUrl = logo ? (logo.startsWith('http') ? logo : '<?= SITE_URL ?>' + (logo.startsWith('uploads/') ? logo : 'uploads/images/' + logo)) : '';

            const card = document.createElement('div');
            card.className = 'cms-repeater-card client-item-row';
            card.innerHTML = `
                <div class="cms-repeater-header">
                    <span class="cms-repeater-badge"><i class="fas fa-briefcase"></i> Client #<span class="client-idx"></span></span>
                    <div class="cms-repeater-actions">
                        <button type="button" class="cms-btn-icon" title="Move Up" onclick="moveRepeaterRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
                        <button type="button" class="cms-btn-icon" title="Move Down" onclick="moveRepeaterRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
                        <button type="button" class="cms-btn-delete" title="Remove" onclick="removeRepeaterRow(this)"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Client / Brand Name *</label>
                    <input type="text" name="client_names[]" class="client-name-input" value="${escapeHtml(name)}" placeholder="e.g. Tata Group" required style="padding:8px 12px;font-size:0.88rem;width:100%;" oninput="autoSuggestClientIcon(this)">
                </div>
                
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:0;"><i class="fas fa-certificate" style="color:var(--primary);margin-right:4px;"></i> Choose Logo Format:</label>
                        <div class="cms-pill-toggle">
                            <button type="button" class="cms-pill-btn ${initialType === 'icon' ? 'active' : ''}" onclick="switchClientLogoType(this, 'icon')"><i class="fas fa-icons"></i> Choose Icon</button>
                            <button type="button" class="cms-pill-btn ${initialType === 'image' ? 'active' : ''}" onclick="switchClientLogoType(this, 'image')"><i class="fas fa-image"></i> Upload Image</button>
                        </div>
                    </div>
                    <input type="hidden" name="client_logo_types[]" class="client-logo-type-input" value="${initialType}">

                    <!-- Icon Selector Panel -->
                    <div class="client-logo-icon-panel" style="display:${initialType === 'icon' ? 'block' : 'none'};">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="client-icon-preview-box" style="width:38px;height:38px;border-radius:6px;background:#fef2f2;color:#E61A27;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0;border:1px solid #fecaca;">
                                <i class="${escapeHtml(activeIcon)}"></i>
                            </div>
                            <select name="client_icons[]" class="client-icon-select" style="flex:1;padding:7px 10px;font-size:0.85rem;border:1px solid #cbd5e1;border-radius:6px;" onchange="updateClientIconPreview(this)">
                                ${optionsHtml}
                            </select>
                        </div>
                    </div>

                    <!-- Image Upload Panel -->
                    <div class="client-logo-image-panel" style="display:${initialType === 'image' ? 'block' : 'none'};">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="client-img-preview-box" style="width:48px;height:38px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #cbd5e1;flex-shrink:0;">
                                <img src="${escapeHtml(resolvedLogoUrl)}" class="client-img-preview" style="max-height:100%;max-width:100%;object-fit:contain;${hasImageLogo ? '' : 'display:none;'}">
                                <i class="fas fa-image client-img-placeholder" style="color:#94a3b8;font-size:1.1rem;${hasImageLogo ? 'display:none;' : ''}"></i>
                            </div>
                            <div style="flex:1;">
                                <input type="file" name="client_logo_files[]" accept="image/*" style="font-size:0.8rem;width:100%;" onchange="previewClientLogoFile(this)">
                                <input type="hidden" name="client_existing_logos[]" class="client-existing-logo" value="${escapeHtml(logo)}">
                                ${hasImageLogo ? `<small style="color:#64748b;display:block;margin-top:2px;">Current: ${escapeHtml(logo.split('/').pop())}</small>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
            updateRowIndices('clientsListContainer', 'client-idx');
        }

        function switchClientLogoType(btn, type) {
            const card = btn.closest('.client-item-row');
            const toggle = btn.parentElement;
            toggle.querySelectorAll('.cms-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            card.querySelector('.client-logo-type-input').value = type;
            const iconPanel = card.querySelector('.client-logo-icon-panel');
            const imagePanel = card.querySelector('.client-logo-image-panel');

            if (type === 'icon') {
                iconPanel.style.display = 'block';
                imagePanel.style.display = 'none';
            } else {
                iconPanel.style.display = 'none';
                imagePanel.style.display = 'block';
            }
        }

        function updateClientIconPreview(select) {
            const card = select.closest('.client-item-row');
            const previewIcon = card.querySelector('.client-icon-preview-box i');
            if (previewIcon) {
                previewIcon.className = select.value;
            }
        }

        function previewClientLogoFile(fileInput) {
            const card = fileInput.closest('.client-item-row');
            const imgPreview = card.querySelector('.client-img-preview');
            const placeholder = card.querySelector('.client-img-placeholder');

            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function autoSuggestClientIcon(nameInput) {
            const card = nameInput.closest('.client-item-row');
            const typeInput = card.querySelector('.client-logo-type-input');
            if (!typeInput || typeInput.value !== 'icon') return;

            const name = nameInput.value.toLowerCase().trim();
            const defaultMap = {
                'tata': 'fas fa-building',
                'reliance': 'fas fa-building',
                'airtel': 'fas fa-signal',
                'jio': 'fas fa-wifi',
                'zomato': 'fas fa-utensils',
                'swiggy': 'fas fa-utensils',
                'amul': 'fas fa-cow',
                'maruti': 'fas fa-car',
                'hyundai': 'fas fa-car',
                'paytm': 'fas fa-mobile-alt',
                'hdfc': 'fas fa-university',
                'sbi': 'fas fa-university',
                'icici': 'fas fa-university',
                'infosys': 'fas fa-laptop',
                'wipro': 'fas fa-laptop',
                'amazon': 'fas fa-shopping-cart',
                'flipkart': 'fas fa-shopping-cart'
            };

            for (const key in defaultMap) {
                if (name.includes(key)) {
                    const select = card.querySelector('.client-icon-select');
                    if (select) {
                        select.value = defaultMap[key];
                        updateClientIconPreview(select);
                    }
                    break;
                }
            }
        }

        function populateClientsList() {
            const container = document.getElementById('clientsListContainer');
            if (!container) return;
            container.innerHTML = '';
            if (Array.isArray(INITIAL_CLIENTS_DATA) && INITIAL_CLIENTS_DATA.length > 0) {
                INITIAL_CLIENTS_DATA.forEach(item => {
                    const name = typeof item === 'object' ? (item.name || '') : item;
                    const logo = typeof item === 'object' ? (item.logo || '') : '';
                    const icon = typeof item === 'object' ? (item.icon || '') : '';
                    addClientRow(name, logo, icon);
                });
            } else {
                addClientRow('Tata Group', '', 'fas fa-building');
            }
        }

        // --- Process Steps Repeater ---
        function addProcessRow(num = '', title = '', desc = '') {
            const container = document.getElementById('processListContainer');
            if (!container) return;
            const currentCount = container.querySelectorAll('.process-item-row').length + 1;
            const stepNum = num || String(currentCount);

            const card = document.createElement('div');
            card.className = 'cms-repeater-card process-item-row';
            card.innerHTML = `
                <div class="cms-repeater-header">
                    <span class="cms-repeater-badge"><i class="fas fa-list-ol"></i> Step #<span class="process-idx"></span></span>
                    <div class="cms-repeater-actions">
                        <button type="button" class="cms-btn-icon" title="Move Up" onclick="moveRepeaterRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
                        <button type="button" class="cms-btn-icon" title="Move Down" onclick="moveRepeaterRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
                        <button type="button" class="cms-btn-delete" title="Remove" onclick="removeRepeaterRow(this)"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:80px 1fr;gap:10px;margin-bottom:8px;">
                    <div>
                        <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Step #</label>
                        <input type="text" class="process-num-input" value="${escapeHtml(stepNum)}" placeholder="1" style="padding:8px 12px;font-size:0.88rem;text-align:center;">
                    </div>
                    <div>
                        <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Step Title *</label>
                        <input type="text" class="process-title-input" value="${escapeHtml(title)}" placeholder="e.g. Discovery" required style="padding:8px 12px;font-size:0.88rem;">
                    </div>
                </div>
                <div>
                    <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Description</label>
                    <textarea class="process-desc-input" rows="2" placeholder="Brief explanation of this step..." style="padding:8px 12px;font-size:0.88rem;">${escapeHtml(desc)}</textarea>
                </div>
            `;
            container.appendChild(card);
            updateRowIndices('processListContainer', 'process-idx');
        }

        function populateProcessList() {
            const container = document.getElementById('processListContainer');
            if (!container) return;
            container.innerHTML = '';
            if (Array.isArray(INITIAL_PROCESS_DATA) && INITIAL_PROCESS_DATA.length > 0) {
                INITIAL_PROCESS_DATA.forEach(item => {
                    addProcessRow(item.num || '', item.title || '', item.desc || '');
                });
            } else {
                addProcessRow('1', 'Discovery', 'Understanding your brand, goals, and target audience');
            }
        }

        function prepareProcessJson() {
            const rows = document.querySelectorAll('#processListContainer .process-item-row');
            const data = [];
            rows.forEach((row, i) => {
                const num = row.querySelector('.process-num-input').value.trim() || String(i + 1);
                const title = row.querySelector('.process-title-input').value.trim();
                const desc = row.querySelector('.process-desc-input').value.trim();
                if (title) {
                    data.push({
                        num,
                        title,
                        desc
                    });
                }
            });
            document.getElementById('process_json_data').value = JSON.stringify(data);
            return true;
        }

        // --- Why Choose Us Repeater ---
        function addWhyRow(icon = 'fas fa-check', title = '', desc = '') {
            const container = document.getElementById('whyListContainer');
            if (!container) return;
            const card = document.createElement('div');
            card.className = 'cms-repeater-card why-item-row';
            card.innerHTML = `
                <div class="cms-repeater-header">
                    <span class="cms-repeater-badge"><i class="fas fa-check-circle"></i> Feature #<span class="why-idx"></span></span>
                    <div class="cms-repeater-actions">
                        <button type="button" class="cms-btn-icon" title="Move Up" onclick="moveRepeaterRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
                        <button type="button" class="cms-btn-icon" title="Move Down" onclick="moveRepeaterRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
                        <button type="button" class="cms-btn-delete" title="Remove" onclick="removeRepeaterRow(this)"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:140px 1fr;gap:10px;margin-bottom:8px;">
                    <div>
                        <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Icon Class</label>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div class="why-icon-preview" style="width:36px;height:36px;border-radius:6px;background:#fef2f2;color:#E61A27;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
                                <i class="${escapeHtml(icon || 'fas fa-check')}"></i>
                            </div>
                            <input type="text" class="why-icon-input" value="${escapeHtml(icon || 'fas fa-check')}" placeholder="fas fa-eye" style="padding:6px 8px;font-size:0.78rem;" oninput="updateWhyRowIcon(this)">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Title / Metric *</label>
                        <input type="text" class="why-title-input" value="${escapeHtml(title)}" placeholder="e.g. 71% Read Rate" required style="padding:8px 12px;font-size:0.88rem;">
                    </div>
                </div>
                <div>
                    <label style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:4px;display:block;">Description</label>
                    <textarea class="why-desc-input" rows="2" placeholder="Describe the feature or proof point..." style="padding:8px 12px;font-size:0.88rem;">${escapeHtml(desc)}</textarea>
                </div>
            `;
            container.appendChild(card);
            updateRowIndices('whyListContainer', 'why-idx');
        }

        function updateWhyRowIcon(input) {
            const preview = input.closest('.why-item-row').querySelector('.why-icon-preview i');
            if (preview) {
                preview.className = input.value.trim() || 'fas fa-check';
            }
        }

        function populateWhyList() {
            const container = document.getElementById('whyListContainer');
            if (!container) return;
            container.innerHTML = '';
            if (Array.isArray(INITIAL_WHY_DATA) && INITIAL_WHY_DATA.length > 0) {
                INITIAL_WHY_DATA.forEach(item => {
                    addWhyRow(item.icon || 'fas fa-check', item.title || '', item.desc || '');
                });
            } else {
                addWhyRow('fas fa-eye', '71% Read Rate', '71% of people read hoardings carefully while driving');
            }
        }

        function prepareWhyJson() {
            const rows = document.querySelectorAll('#whyListContainer .why-item-row');
            const data = [];
            rows.forEach(row => {
                const icon = row.querySelector('.why-icon-input').value.trim() || 'fas fa-check';
                const title = row.querySelector('.why-title-input').value.trim();
                const desc = row.querySelector('.why-desc-input').value.trim();
                if (title) {
                    data.push({
                        icon,
                        title,
                        desc
                    });
                }
            });
            document.getElementById('why_json_data').value = JSON.stringify(data);
            return true;
        }

        // --- Common Helpers ---
        function moveRepeaterRow(btn, direction) {
            const card = btn.closest('.cms-repeater-card');
            const container = card.parentElement;
            if (direction === -1 && card.previousElementSibling) {
                container.insertBefore(card, card.previousElementSibling);
            } else if (direction === 1 && card.nextElementSibling) {
                container.insertBefore(card.nextElementSibling, card);
            }
            updateRowIndices(container.id, container.id.replace('ListContainer', '-idx'));
        }

        function removeRepeaterRow(btn) {
            const card = btn.closest('.cms-repeater-card');
            const container = card.parentElement;
            if (container.querySelectorAll('.cms-repeater-card').length <= 1) {
                alert('At least one item is required.');
                return;
            }
            card.remove();
            updateRowIndices(container.id, container.id.replace('ListContainer', '-idx'));
        }

        function updateRowIndices(containerId, badgeClass) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.querySelectorAll('.' + badgeClass).forEach((badge, idx) => {
                badge.textContent = idx + 1;
            });
        }

        // Specific Modal Openers
        function previewLogoImage(input) {
            var wrap = document.getElementById('logoPreviewWrap');
            var preview = document.getElementById('logoNewPreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openEditHeroModal() {
            openModal('heroModal');
        }

        function openEditAboutModal() {
            openModal('aboutModal');
        }

        function openEditClientsModal() {
            populateClientsList();
            openModal('clientsModal');
        }

        function openEditProcessModal() {
            populateProcessList();
            openModal('processModal');
        }

        function openEditWhyModal() {
            populateWhyList();
            openModal('whyModal');
        }

        function openEditContactSettingsModal() {
            openModal('contactSettingsModal');
        }

        function openAddServiceModal() {
            openModal('addServiceModal');
        }

        function openEditServiceModal(service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_service_title').value = service.title;
            document.getElementById('edit_service_icon').value = service.icon || 'fas fa-bullhorn';
            document.getElementById('edit_service_description').value = service.description || '';
            document.getElementById('edit_service_order').value = service.order || 0;

            let benefits = '';
            if (service.benefits) {
                try {
                    const parsed = JSON.parse(service.benefits);
                    if (Array.isArray(parsed)) benefits = parsed.join('\n');
                } catch (e) {
                    benefits = service.benefits;
                }
            }
            document.getElementById('edit_service_benefits').value = benefits;
            openModal('editServiceModal');
        }

        function openAddPortfolioModal() {
            openModal('addPortfolioModal');
        }

        function openEditPortfolioModal(item) {
            document.getElementById('edit_portfolio_id').value = item.id;
            document.getElementById('edit_portfolio_title').value = item.title;
            document.getElementById('edit_portfolio_category').value = item.category || '';
            document.getElementById('edit_portfolio_description').value = item.description || '';
            openModal('editPortfolioModal');
        }

        function openAddIndustryModal() {
            renderIconPickerGrid('add_industry_grid', 'add_industry_icon', 'add_industry_icon_preview', 'fas fa-building');
            openModal('addIndustryModal');
        }

        function openEditIndustryModal(ind) {
            document.getElementById('edit_industry_id').value = ind.id;
            document.getElementById('edit_industry_name').value = ind.name;
            const iconVal = ind.icon || 'fas fa-building';
            document.getElementById('edit_industry_icon').value = iconVal;
            document.getElementById('edit_industry_order').value = ind.order || 0;
            renderIconPickerGrid('edit_industry_grid', 'edit_industry_icon', 'edit_industry_icon_preview', iconVal);
            openModal('editIndustryModal');
        }

        function openAddStatModal() {
            openModal('addStatModal');
        }

        function openEditStatModal(stat) {
            document.getElementById('edit_stat_id').value = stat.id;
            document.getElementById('edit_stat_label').value = stat.label;
            document.getElementById('edit_stat_value').value = stat.value;
            document.getElementById('edit_stat_suffix').value = stat.suffix || '+';
            openModal('editStatModal');
        }

        function openAddTestimonialModal() {
            openModal('addTestimonialModal');
        }

        function openEditTestimonialModal(t) {
            document.getElementById('edit_testimonial_id').value = t.id;
            document.getElementById('edit_testimonial_name').value = t.name;
            document.getElementById('edit_testimonial_company').value = t.company || '';
            document.getElementById('edit_testimonial_position').value = t.position || '';
            document.getElementById('edit_testimonial_rating').value = t.rating || 5;
            document.getElementById('edit_testimonial_content').value = t.content || '';
            openModal('editTestimonialModal');
        }

        function openAddFaqModal() {
            openModal('addFaqModal');
        }

        function openEditFaqModal(faq) {
            document.getElementById('edit_faq_id').value = faq.id;
            document.getElementById('edit_faq_question').value = faq.question;
            document.getElementById('edit_faq_answer').value = faq.answer;
            openModal('editFaqModal');
        }

        function openAddFooterColModal() {
            openModal('addFooterColModal');
        }

        function openEditFooterColModal(col) {
            document.getElementById('edit_footer_col_id').value = col.id;
            document.getElementById('edit_footer_col_title').value = col.title;
            document.getElementById('edit_footer_col_order').value = col.order || 0;
            openModal('editFooterColModal');
        }

        function openAddFooterLinkModal(colId) {
            document.getElementById('add_footer_link_col_id').value = colId;
            openModal('addFooterLinkModal');
        }

        function openEditFooterLinkModal(link) {
            document.getElementById('edit_footer_link_id').value = link.id;
            document.getElementById('edit_footer_link_label').value = link.label;
            document.getElementById('edit_footer_link_url').value = link.url;
            document.getElementById('edit_footer_link_order').value = link.order || 0;
            openModal('editFooterLinkModal');
        }

        function openAddSocialLinkModal() {
            openModal('addSocialLinkModal');
        }

        function openEditSocialLinkModal(sl) {
            document.getElementById('edit_social_link_id').value = sl.id;
            document.getElementById('edit_social_link_platform').value = sl.platform;
            document.getElementById('edit_social_link_url').value = sl.url;
            document.getElementById('edit_social_link_icon').value = sl.icon;
            document.getElementById('edit_social_link_order').value = sl.order || 0;
            openModal('editSocialLinkModal');
        }

        // ===== SLIDERS FUNCTIONALITY =====
        document.addEventListener('DOMContentLoaded', function() {
            // Services Belt Slider
            const slider = document.getElementById('servicesSlider');
            const prevBtn = document.getElementById('servicesPrevBtn');
            const nextBtn = document.getElementById('servicesNextBtn');

            if (slider && prevBtn && nextBtn) {
                const scrollAmount = 300;
                prevBtn.addEventListener('click', () => {
                    slider.scrollBy({
                        left: -scrollAmount,
                        behavior: 'smooth'
                    });
                });
                nextBtn.addEventListener('click', () => {
                    slider.scrollBy({
                        left: scrollAmount,
                        behavior: 'smooth'
                    });
                });
            }

            // Portfolio Showcase Slider is now handled purely by CSS marquee animation.

            // ===== TESTIMONIALS SLIDER (index-based carousel) =====
            // Uses a tracked currentIndex so navigation never resets mid-animation.
            // ROOT CAUSE of previous bug: a scroll event listener was firing during
            // the smooth-scroll animation and immediately resetting scrollLeft to 0
            // because maxScroll (≈ 1 card-width with 4 cards/3 visible) was reached
            // within the first animation frame. Fixed by switching to index-based
            // scrollTo() which never relies on scroll events.
            const tSlider = document.getElementById('testimonialsSlider');
            const tPrevBtn = document.getElementById('testimonialPrevBtn');
            const tNextBtn = document.getElementById('testimonialNextBtn');

            if (tSlider && tPrevBtn && tNextBtn) {
                const cards = tSlider.querySelectorAll('.testimonial-card');
                const totalCards = cards.length;
                let currentIndex = 0; // which card is leftmost/visible
                let isAnimating = false;

                // How many cards are visible at the current viewport width
                const getVisibleCount = () => {
                    if (window.innerWidth <= 600) return 1;
                    if (window.innerWidth <= 900) return 2;
                    return 3;
                };

                // Pixel width of one card + one gap
                const getCardScrollWidth = () => {
                    if (!cards.length) return 320;
                    const gap = parseInt(getComputedStyle(tSlider).gap) || 28;
                    return cards[0].offsetWidth + gap;
                };

                // Maximum valid starting index (last window of cards)
                const getMaxIndex = () => Math.max(0, totalCards - getVisibleCount());

                // Scroll the track to the exact position for currentIndex
                const scrollToIndex = (idx) => {
                    const target = idx * getCardScrollWidth();
                    tSlider.scrollTo({
                        left: target,
                        behavior: 'smooth'
                    });
                };

                // Guard against double-clicks during animation
                const ANIM_MS = 420; // slightly longer than CSS scroll duration

                tNextBtn.addEventListener('click', () => {
                    if (isAnimating) return;
                    isAnimating = true;
                    const maxIdx = getMaxIndex();
                    // Advance one card; wrap to start only after the last slide
                    currentIndex = (currentIndex >= maxIdx) ? 0 : currentIndex + 1;
                    scrollToIndex(currentIndex);
                    setTimeout(() => {
                        isAnimating = false;
                    }, ANIM_MS);
                });

                tPrevBtn.addEventListener('click', () => {
                    if (isAnimating) return;
                    isAnimating = true;
                    const maxIdx = getMaxIndex();
                    // Go back one card; wrap to end only before the first slide
                    currentIndex = (currentIndex <= 0) ? maxIdx : currentIndex - 1;
                    scrollToIndex(currentIndex);
                    setTimeout(() => {
                        isAnimating = false;
                    }, ANIM_MS);
                });

                // Re-clamp index on resize so the slider never stalls
                window.addEventListener('resize', () => {
                    currentIndex = Math.min(currentIndex, getMaxIndex());
                    // Snap instantly (no animation) on resize
                    tSlider.scrollTo({
                        left: currentIndex * getCardScrollWidth(),
                        behavior: 'instant'
                    });
                });
            }
        });

        console.log('🚀 Pipani Advertising — Modern Dynamic CMS Frontend Initialized');
    </script>
</body>

</html>