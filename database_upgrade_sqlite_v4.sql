-- Internal SQLite migration. The application executes this inside a transaction
-- with foreign_keys temporarily disabled, then checks all foreign keys.
CREATE TABLE delegates_v4 (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  kingschat_user_id TEXT UNIQUE,
  auth_method TEXT NOT NULL DEFAULT 'kingschat' CHECK (auth_method IN ('kingschat', 'manual')),
  phone TEXT,
  manual_login_key TEXT UNIQUE,
  handle TEXT NOT NULL,
  display_name TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO delegates_v4 (id, kingschat_user_id, handle, display_name, created_at, updated_at)
SELECT id, kingschat_user_id, handle, display_name, created_at, updated_at FROM delegates;
DROP TABLE delegates;
ALTER TABLE delegates_v4 RENAME TO delegates;
