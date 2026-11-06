-- Offline SQLite schema. Initialized automatically when DB_DRIVER=sqlite.
CREATE TABLE IF NOT EXISTS programs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  platform_title TEXT NOT NULL DEFAULT 'Delegate Experience Centre',
  tagline TEXT NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  welcome_message TEXT NOT NULL,
  banner_path TEXT,
  primary_color TEXT NOT NULL DEFAULT '#123c7a',
  accent_color TEXT NOT NULL DEFAULT '#e5ad36',
  services_json TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published')),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS programs_status_updated ON programs (status, updated_at);

CREATE TABLE IF NOT EXISTS feedback (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  program_id INTEGER NOT NULL REFERENCES programs(id) ON DELETE CASCADE,
  service_area TEXT NOT NULL,
  rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
  delegate_name TEXT,
  is_anonymous INTEGER NOT NULL DEFAULT 0 CHECK (is_anonymous IN (0, 1)),
  went_well TEXT NOT NULL,
  comments TEXT NOT NULL,
  submitted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS feedback_program_date ON feedback (program_id, submitted_at);
CREATE INDEX IF NOT EXISTS feedback_program_area ON feedback (program_id, service_area);
