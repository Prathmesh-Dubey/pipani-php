-- install/schema.sql - Database Schema

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','editor') DEFAULT 'editor',
  `avatar` longtext,
  `last_login` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `group` varchar(50) DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Content blocks table
CREATE TABLE IF NOT EXISTS `content_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` longtext,
  `image` longtext,
  `status` tinyint(1) DEFAULT 1,
  `order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Media table
CREATE TABLE IF NOT EXISTS `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menus table
CREATE TABLE IF NOT EXISTS `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(50) DEFAULT 'main',
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu items table
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `label` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(10) DEFAULT '_self',
  `icon` varchar(50) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `menu_id` (`menu_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contacts table
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FAQs table
CREATE TABLE IF NOT EXISTS `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services table
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `description` text,
  `icon` varchar(100) DEFAULT NULL,
  `image` longtext,
  `how_it_works` text,
  `formats` text,
  `benefits` text,
  `target_audience` text,
  `applications` text,
  `gallery` text,
  `cta_text` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Portfolio table
CREATE TABLE IF NOT EXISTS `portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `image` longtext NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Testimonials table
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `company` varchar(200) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `avatar` longtext,
  `rating` int(1) DEFAULT 5,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Industries table
CREATE TABLE IF NOT EXISTS `industries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `image` longtext,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Statistics table
CREATE TABLE IF NOT EXISTS `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `value` varchar(50) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Social links table
CREATE TABLE IF NOT EXISTS `social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO settings table
CREATE TABLE IF NOT EXISTS `seo_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(100) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text,
  `keywords` text,
  `canonical` varchar(255) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_description` text,
  `og_image` varchar(255) DEFAULT NULL,
  `twitter_title` varchar(200) DEFAULT NULL,
  `twitter_description` text,
  `twitter_image` varchar(255) DEFAULT NULL,
  `schema_json` longtext,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@example.com', 'admin123', 'Super Admin', 'super_admin');

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `group`) VALUES
('site_name', 'Pipani Advertising', 'general'),
('site_tagline', 'Advertising · PR · Massive Impact', 'general'),
('site_description', '7 Years of PIPANI ADVERTISING - India\'s premier full-service advertising agency', 'general'),
('site_email', 'info@pipaniadvertising.com', 'general'),
('site_phone', '+91 9766840787', 'general'),
('site_address', 'Dhankawadi, Pune, Maharashtra 411043', 'general'),
('timezone', 'Asia/Kolkata', 'general'),
('maintenance_mode', '0', 'general'),
('logo', '', 'general'),
('favicon', '', 'general'),
('footer_text', '2026 Pipani Advertising. A Part of the PIPANI ADVERTISING Group. All Rights Reserved.', 'general'),
('max_upload_size', '5242880', 'media');

-- Insert content blocks
INSERT INTO `content_blocks` (`section`, `slug`, `title`, `content`) VALUES
('hero', 'hero_title', 'Hero Title', 'Making a Massive Impact Since 2017'),
('hero', 'hero_subtitle', 'Hero Subtitle', 'India\'s premier full-service advertising agency — delivering Offline, Transit, Digital, and Non-Traditional media solutions that create lasting impressions and measurable results.'),
('hero', 'hero_badge', 'Hero Badge', '7 Years of Excellence'),
('about', 'about_title', 'About Title', 'Who We Are'),
('about', 'about_subtitle', 'About Subtitle', 'Advertising · PR · Massive Impact'),
('about', 'about_content', 'About Content', '<p>For over 7 years, Pipani Advertising has been at the forefront of the advertising industry, helping brands achieve massive impact. As a part of the PIPANI ADVERTISING Group and operating through Adnest Communications LLP, we are a full-service powerhouse specializing in a diverse range of media.</p><p>From traditional outdoor and transit advertising to innovative digital and non-traditional solutions, our mission is to deliver unparalleled visibility and brand awareness across every touchpoint.</p>');

-- Insert services
INSERT INTO `services` (`slug`, `title`, `description`, `icon`, `benefits`, `order`) VALUES
('offline-media', 'Offline Media', 'Hoardings, bus shelters, pole kiosks, railway stations, airport branding, and more.', 'fas fa-billboard', '["71% Read Rate","High Visibility"]', 1),
('transit-media', 'Transit Media', 'Bus, train, metro, auto, cab, in-flight, and road show branding solutions.', 'fas fa-bus', '["80% Response","24/7 Visibility"]', 2),
('electronic-media', 'Electronic Media', 'Radio and television advertising for maximum reach and brand recall.', 'fas fa-radio', '["Mass Reach","High Recall"]', 3),
('cinema-branding', 'Cinema Branding', 'On-screen and off-screen advertising in cinemas across India.', 'fas fa-film', '["Captive Audience","High Impact"]', 4),
('ambient-media', 'Ambient Media', 'Mall, restaurant, café, and pub branding for targeted audience engagement.', 'fas fa-store', '["Targeted","High Engagement"]', 5),
('print-media', 'Print Media', 'Newspaper and magazine advertising with wide circulation and long shelf life.', 'fas fa-newspaper', '["Credible","Long-lasting"]', 6),
('non-traditional-media', 'Non-Traditional Media', 'Standees, dealer boards, one-way vision, wall painting, OTT, influencer marketing & more.', 'fas fa-paint-brush', '["Innovative","High Impact"]', 7),
('corporate-gifting', 'Corporate Gifting', 'Premium corporate gifts, branded merchandise, and promotional items for your brand.', 'fas fa-gift', '["Premium","Brand Recall"]', 8),
('sports-showbiz', 'Sports & Showbiz', 'Jersey sponsorships, sports gear branding, and entertainment industry advertising.', 'fas fa-cricket-bat-ball', '["Massive Reach","High Engagement"]', 9);

-- Insert statistics
INSERT INTO `statistics` (`label`, `value`, `suffix`, `order`) VALUES
('Years of Experience', '7', '+', 1),
('Campaigns Executed', '500', '+', 2),
('Brands Served', '200', '+', 3),
('Cities Covered', '50', '+', 4),
('Media Assets', '5000', '+', 5),
('Daily Reach', '10', 'M+', 6);

-- Insert FAQs
INSERT INTO `faqs` (`question`, `answer`, `order`) VALUES
('What advertising services do you offer?', 'We offer a comprehensive range of services including Offline Media, Transit Media, Electronic Media, Cinema Branding, Ambient Media, Print Media, Non-Traditional Media, Corporate Gifting, and Sports & Showbiz advertising. Each service is tailored to meet your specific brand objectives.', 1),
('Which cities do you operate in?', 'We have a strong presence across India with particular focus on Pune, Mumbai, Delhi, and other major metropolitan cities. Our railway station branding covers key stations like Pune, Pimpri, Chinchwad, Vadgaon, Lonavala, and many more across the country.', 2),
('What is the minimum budget for advertising?', 'Our advertising solutions are highly scalable. We work with brands of all sizes, from small businesses to large corporations. Contact us for a customized quote based on your specific requirements and campaign objectives.', 3),
('How do I get started with Pipani Advertising?', 'Simply reach out through our contact form or call our co-founders directly. We\'ll schedule a consultation to understand your needs and propose the best advertising strategy for your brand.', 4);

-- Insert testimonials
INSERT INTO `testimonials` (`name`, `company`, `position`, `content`, `rating`, `order`) VALUES
('Rahul Khanna', 'Brand X', 'Marketing Head', '"Pipani Advertising delivered exceptional results for our brand. Their transit media campaign reached millions and drove significant footfall."', 5, 1),
('Priya Mehta', 'Brand Y', 'CEO', '"The team\'s expertise in outdoor and digital media helped us achieve 3x ROI on our advertising spend. Highly recommended!"', 5, 2),
('Amit Singh', 'Brand Z', 'Brand Manager', '"From hoardings to influencer marketing, Pipani\'s full-service approach made our campaign seamless and highly effective."', 5, 3);

-- Insert default menu
INSERT INTO `menus` (`name`, `location`) VALUES ('Main Menu', 'main');

INSERT INTO `menu_items` (`menu_id`, `label`, `url`, `order`) VALUES
(1, 'About', '#about', 1),
(1, 'Services', '#services', 2),
(1, 'Portfolio', '#portfolio', 3),
(1, 'Clients', '#clients', 4),
(1, 'Contact', '#contact', 5);

-- Insert social links
INSERT INTO `social_links` (`platform`, `url`, `icon`, `order`) VALUES
('Facebook', '#', 'fab fa-facebook', 1),
('Instagram', '#', 'fab fa-instagram', 2),
('LinkedIn', '#', 'fab fa-linkedin', 3),
('YouTube', '#', 'fab fa-youtube', 4);

-- Insert industries
INSERT INTO `industries` (`name`, `icon`, `order`, `status`) VALUES
('Real Estate', 'fas fa-building', 1, 1),
('Healthcare', 'fas fa-heart-pulse', 2, 1),
('Education', 'fas fa-graduation-cap', 3, 1),
('Retail', 'fas fa-cart-shopping', 4, 1),
('Automobile', 'fas fa-car', 5, 1),
('Hospitality', 'fas fa-utensils', 6, 1),
('Government', 'fas fa-landmark', 7, 1),
('Political', 'fas fa-vote-yea', 8, 1),
('FMCG', 'fas fa-box-open', 9, 1),
('Financial Services', 'fas fa-coins', 10, 1),
('Technology', 'fas fa-microchip', 11, 1),
('Manufacturing', 'fas fa-industry', 12, 1),
('Media & Entertainment', 'fas fa-film', 13, 1),
('Non-Profit', 'fas fa-hand-holding-heart', 14, 1),
('Renewable Energy', 'fas fa-leaf', 15, 1),
('Telecommunications', 'fas fa-broadcast-tower', 16, 1),
('Agriculture', 'fas fa-tractor', 17, 1),
('Legal Services', 'fas fa-gavel', 18, 1);

-- Footer columns table
CREATE TABLE IF NOT EXISTS `footer_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Footer links table
CREATE TABLE IF NOT EXISTS `footer_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `column_id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `column_id` (`column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `footer_columns` (`id`, `title`, `order`) VALUES
(1, 'Services', 1),
(2, 'Company', 2);

INSERT INTO `footer_links` (`column_id`, `label`, `url`, `order`) VALUES
(1, 'Offline Media', '#services', 1),
(1, 'Transit Media', '#services', 2),
(1, 'Electronic Media', '#services', 3),
(1, 'Corporate Gifting', '#services', 4),
(2, 'About Us', '#about', 1),
(2, 'Our Portfolio', '#portfolio', 2),
(2, 'Sectors We Serve', '#industries', 3),
(2, 'Contact Support', '#contact', 4),
(0, 'Privacy Policy', '#', 1),
(0, 'Terms of Service', '#', 2);