CREATE DATABASE IF NOT EXISTS yong_artourism CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yong_artourism;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE destinations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    short_description VARCHAR(500) NOT NULL DEFAULT '',
    description MEDIUMTEXT NULL,
    cover_image VARCHAR(255) NULL,
    location VARCHAR(190) NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    map_url VARCHAR(500) NULL,
    status ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'draft',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_destinations_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_destinations_status_featured (status, featured),
    INDEX idx_destinations_category (category_id)
) ENGINE=InnoDB;

CREATE TABLE attractions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    short_description VARCHAR(500) NOT NULL DEFAULT '',
    description MEDIUMTEXT NULL,
    main_image VARCHAR(255) NULL,
    youtube_url VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    map_url VARCHAR(500) NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    opening_hours VARCHAR(190) NULL,
    entry_information VARCHAR(500) NULL,
    status ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'draft',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attractions_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    CONSTRAINT fk_attractions_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_attractions_destination_status (destination_id, status),
    INDEX idx_attractions_category (category_id),
    FULLTEXT INDEX idx_attractions_search (name, short_description, description)
) ENGINE=InnoDB;

CREATE TABLE attraction_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attraction_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(190) NOT NULL DEFAULT '',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attraction_images_attraction FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE,
    INDEX idx_attraction_images_order (attraction_id, display_order)
) ENGINE=InnoDB;

CREATE TABLE ar_posters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    poster_image VARCHAR(255) NOT NULL,
    target_file VARCHAR(255) NULL,
    target_status ENUM('not_compiled', 'ready', 'failed') NOT NULL DEFAULT 'not_compiled',
    target_compiled_at DATETIME NULL,
    status ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ar_posters_status (status, target_status)
) ENGINE=InnoDB;

CREATE TABLE ar_hotspots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poster_id BIGINT UNSIGNED NOT NULL,
    attraction_id BIGINT UNSIGNED NULL,
    label VARCHAR(120) NOT NULL,
    x DECIMAL(8, 6) NOT NULL DEFAULT 0.5,
    y DECIMAL(8, 6) NOT NULL DEFAULT 0.5,
    width DECIMAL(8, 6) NOT NULL DEFAULT 0.2,
    height DECIMAL(8, 6) NOT NULL DEFAULT 0.2,
    z_index INT NOT NULL DEFAULT 1,
    video_url VARCHAR(500) NULL,
    content_type ENUM('information', 'video', 'image') NOT NULL DEFAULT 'information',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ar_hotspots_poster FOREIGN KEY (poster_id) REFERENCES ar_posters(id) ON DELETE CASCADE,
    CONSTRAINT fk_ar_hotspots_attraction FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE SET NULL,
    INDEX idx_ar_hotspots_poster (poster_id, status, z_index)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_logs_created (created_at),
    INDEX idx_activity_logs_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name, slug, description, display_order) VALUES
('Heritage', 'heritage', 'Historic places and living cultural traditions.', 1),
('Nature', 'nature', 'Parks, landscapes, and outdoor experiences.', 2),
('Culture', 'culture', 'Local arts, communities, and cultural experiences.', 3),
('Food', 'food', 'Regional flavors and food heritage.', 4),
('Architecture', 'architecture', 'Distinctive buildings and designed places.', 5);

INSERT INTO destinations (category_id, name, slug, short_description, description, cover_image, location, status, featured) VALUES
(1, 'Old Quarter', 'old-quarter', 'A walk through layered history, craft, and street life.', 'Explore a thoughtful mix of historic streets, independent makers, and local gathering places.', 'assets/images/old-quarter.jpg', 'Central District', 'active', 1),
(2, 'Green Valley', 'green-valley', 'Open trails, quiet viewpoints, and a slower pace.', 'A nature-focused escape with trails for short visits and full-day exploration.', 'assets/images/green-valley.jpg', 'North Region', 'active', 1),
(3, 'Makers Village', 'makers-village', 'Meet local makers and discover living traditions.', 'Community-led experiences connect visitors with local craft, food, and stories.', 'assets/images/makers-village.jpg', 'East Region', 'active', 1),
(4, 'Coastal Table', 'coastal-table', 'A destination shaped by the sea and its kitchens.', 'Explore a welcoming coastal neighborhood and its distinctive food culture.', 'assets/images/coastal-table.jpg', 'South Coast', 'active', 0);

INSERT INTO attractions (destination_id, category_id, name, slug, short_description, description, main_image, opening_hours, status, display_order) VALUES
(1, 1, 'Founders House', 'founders-house', 'A restored home with stories from the district’s early days.', 'Discover the people and everyday objects that shaped this neighborhood.', 'assets/images/founders-house.jpg', 'Tue-Sun, 10:00-17:00', 'active', 1),
(1, 5, 'Clocktower Square', 'clocktower-square', 'A landmark meeting place at the heart of the old streets.', 'Pause at the square and explore the civic architecture around it.', 'assets/images/clocktower-square.jpg', 'Open daily', 'active', 2),
(2, 2, 'Cloudline Lookout', 'cloudline-lookout', 'A broad valley view reached by a gentle ridge trail.', 'Follow the marked path to a quiet overlook above the forest canopy.', 'assets/images/cloudline-lookout.jpg', 'Open daily, sunrise-sunset', 'active', 1),
(3, 3, 'Weavers Workshop', 'weavers-workshop', 'See a traditional craft practiced and shared by local artists.', 'Small-group visits introduce the materials, patterns, and people behind the work.', 'assets/images/weavers-workshop.jpg', 'Wed-Sun, 09:00-16:00', 'active', 1),
(4, 4, 'Harbor Market', 'harbor-market', 'A lively market for seasonal produce and coastal cooking.', 'Meet local vendors and sample dishes rooted in the region’s fishing heritage.', 'assets/images/harbor-market.jpg', 'Daily, 08:00-20:00', 'active', 1);
