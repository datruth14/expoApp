-- One-time MySQL upgrade: verified KingsChat delegate identities.
-- Apply previous upgrades first. Existing feedback remains with delegate_id NULL.
CREATE TABLE IF NOT EXISTS delegates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kingschat_user_id VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL UNIQUE,
  handle VARCHAR(180) NOT NULL,
  display_name VARCHAR(180) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE feedback
  ADD COLUMN delegate_id BIGINT UNSIGNED NULL AFTER program_id,
  ADD KEY feedback_delegate_idx (delegate_id),
  ADD CONSTRAINT feedback_delegate_fk FOREIGN KEY (delegate_id) REFERENCES delegates (id) ON DELETE SET NULL;
