-- Delegate Experience Centre: MySQL/MariaDB starter schema
-- Import this file using cPanel phpMyAdmin after creating the database.

CREATE TABLE IF NOT EXISTS programs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  welcome_message TEXT NOT NULL,
  primary_color CHAR(7) NOT NULL DEFAULT '#123c7a',
  accent_color CHAR(7) NOT NULL DEFAULT '#e5ad36',
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY programs_slug_unique (slug),
  KEY programs_status_updated (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
