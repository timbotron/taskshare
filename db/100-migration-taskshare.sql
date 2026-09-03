-- TaskShare app tables (CODE-78). Runs after 001-migration-start.sql, which
-- ships the Initium `users` table — this migration does NOT recreate it.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- boards: owned by a user, reachable by an unguessable share slug
-- --------------------------------------------------------
CREATE TABLE `boards` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id` int UNSIGNED NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Untitled Board',
  `slug` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `boards_slug_unique` (`slug`),
  KEY `boards_owner_id_idx` (`owner_id`),
  CONSTRAINT `fk_boards_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- lists: ordered lists within a board
-- --------------------------------------------------------
CREATE TABLE `lists` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id` int UNSIGNED NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'New List',
  `position` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `lists_board_id_idx` (`board_id`),
  CONSTRAINT `fk_lists_board` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- tasks: `completed` is the persisted strikethrough state (replaces legacy delete-on-done)
-- --------------------------------------------------------
CREATE TABLE `tasks` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `list_id` int UNSIGNED NOT NULL,
  `text` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `completed` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `position` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tasks_list_id_idx` (`list_id`),
  CONSTRAINT `fk_tasks_list` FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- board_permissions: one row per board, all flags default off (owner-only until enabled)
-- --------------------------------------------------------
CREATE TABLE `board_permissions` (
  `board_id` int UNSIGNED NOT NULL,
  `allow_add_tasks` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `allow_complete` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `allow_clear_completed` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `allow_create_lists` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `allow_delete_lists` tinyint UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`board_id`),
  CONSTRAINT `fk_perms_board` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- users: per-user theme preference; a board's default theme derives from its owner's (CODE-88)
-- --------------------------------------------------------
ALTER TABLE `users` ADD COLUMN `theme` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'light';
