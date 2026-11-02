-- Timshi LMS migration. Run once in phpMyAdmin (select your database first).
-- Adds lessons (course content) and per-student lesson progress.

CREATE TABLE IF NOT EXISTS lessons (
  id             CHAR(36) NOT NULL PRIMARY KEY,
  program_id     VARCHAR(80) NOT NULL,
  module_title   VARCHAR(120) NOT NULL DEFAULT 'Module 1',
  title          VARCHAR(190) NOT NULL,
  kind           ENUM('text','video') NOT NULL DEFAULT 'text',
  video_url      VARCHAR(300) NULL,
  content        MEDIUMTEXT NULL,
  resource_url   VARCHAR(300) NULL,
  resource_label VARCHAR(120) NULL,
  position       INT NOT NULL DEFAULT 0,
  published      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_program_pos (program_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
  user_id      CHAR(36) NOT NULL,
  lesson_id    CHAR(36) NOT NULL,
  program_id   VARCHAR(80) NOT NULL,
  completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, lesson_id),
  KEY idx_user_program (user_id, program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
