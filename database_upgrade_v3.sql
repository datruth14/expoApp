-- Add homepage selection to an existing MySQL installation.
-- Run in phpMyAdmin after the earlier v2 upgrade if that is still needed.
-- Safe to run again; existing homepage selection is preserved.
CREATE TABLE IF NOT EXISTS platform_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  home_program_id BIGINT UNSIGNED NULL,
  CONSTRAINT settings_home_program_fk FOREIGN KEY (home_program_id) REFERENCES programs (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO platform_settings (id) VALUES (1);
