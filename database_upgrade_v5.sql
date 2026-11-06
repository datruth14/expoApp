-- One-time MySQL upgrade after v4: temporary, unverified delegate access.
-- Existing verified delegate IDs and feedback links remain unchanged.
ALTER TABLE delegates
  MODIFY kingschat_user_id VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  ADD COLUMN auth_method ENUM('kingschat', 'manual') NOT NULL DEFAULT 'kingschat' AFTER kingschat_user_id,
  ADD COLUMN phone VARCHAR(32) NULL AFTER display_name,
  ADD COLUMN manual_login_key CHAR(64) NULL AFTER phone,
  ADD UNIQUE KEY delegates_manual_login_unique (manual_login_key);
