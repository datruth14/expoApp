-- Delegate Experience Centre: MySQL/MariaDB schema
-- Import this file using cPanel phpMyAdmin after creating the database.

CREATE TABLE IF NOT EXISTS programs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  platform_title VARCHAR(160) NOT NULL DEFAULT 'Delegate Experience Centre',
  tagline VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  welcome_message TEXT NOT NULL,
  banner_path VARCHAR(500) NULL,
  primary_color CHAR(7) NOT NULL DEFAULT '#123c7a',
  accent_color CHAR(7) NOT NULL DEFAULT '#e5ad36',
  services_json LONGTEXT NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY programs_slug_unique (slug),
  KEY programs_status_updated (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  home_program_id BIGINT UNSIGNED NULL,
  CONSTRAINT settings_home_program_fk FOREIGN KEY (home_program_id) REFERENCES programs (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO platform_settings (id) VALUES (1);

CREATE TABLE IF NOT EXISTS feedback (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  program_id BIGINT UNSIGNED NOT NULL,
  service_area VARCHAR(180) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  delegate_name VARCHAR(180) NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  went_well TEXT NOT NULL,
  comments TEXT NOT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY feedback_program_date (program_id, submitted_at),
  KEY feedback_program_area (program_id, service_area),
  CONSTRAINT feedback_program_fk FOREIGN KEY (program_id) REFERENCES programs (id) ON DELETE CASCADE,
  CONSTRAINT feedback_rating_check CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO programs
  (name, slug, platform_title, tagline, description, welcome_message, primary_color, accent_color, services_json, status)
VALUES
  (
    'Loveword Expo 2026',
    'loveword-expo-2026',
    'Delegate Experience Centre',
    'Where the World Meets Loveworld',
    'A seamless, welcoming and memorable event experience starts here.',
    'A seamless, welcoming and memorable event experience starts here.',
    '#123c7a',
    '#e5ad36',
    '[ ["Exhibition Experience","Exhibition stands and displays","🎪"], ["Food Court Experience","Food vendors and dining facilities","🍽️"], ["Accreditation Experience","Registration and badge collection","🪪"], ["Access Control Experience","Entry points and access routes","🔐"], ["Hospitality Experience","Guest reception and assistance","🛎️"], ["Transportation Experience","Shuttle and transport services","🚌"], ["Car Parking Experience","Parking locations and vehicle access","🚘"], ["Venue Experience","Facilities, environment and overall venue experience","🏟️"], ["Lost & Found","Help with misplaced belongings","🧳"] ]',
    'published'
  )
ON DUPLICATE KEY UPDATE slug = VALUES(slug);
