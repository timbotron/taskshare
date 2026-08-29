-- Local dev seed (CODE-78). Not run by initdb — apply manually against a migrated DB:
--   docker compose exec -T db mysql -utaskshare -ptaskshare taskshare < seed.sql
-- Demo login:  demo@taskshare.test  /  password
-- Assumes a fresh DB (inserts a fixed email + slug; re-running hits the unique keys).

INSERT INTO `users` (`email`, `password`, `is_active`, `created_at`, `theme`)
VALUES ('demo@taskshare.test', '$2y$12$mVzSKNCu9AGoamCuh4POzOZI3snbHJiUsjXd7fGPzYsNUPwcHAq7W', 1, CURDATE(), 'light');
SET @uid = LAST_INSERT_ID();

INSERT INTO `boards` (`owner_id`, `title`, `slug`) VALUES (@uid, 'Demo Board', 'demoseedslug0001');
SET @bid = LAST_INSERT_ID();

INSERT INTO `board_permissions` (`board_id`) VALUES (@bid);

INSERT INTO `lists` (`board_id`, `title`, `position`) VALUES (@bid, 'Groceries', 0);
SET @l1 = LAST_INSERT_ID();
INSERT INTO `lists` (`board_id`, `title`, `position`) VALUES (@bid, 'Chores', 1);
SET @l2 = LAST_INSERT_ID();

INSERT INTO `tasks` (`list_id`, `text`, `completed`, `position`) VALUES
  (@l1, 'Milk', 0, 0),
  (@l1, 'Eggs', 0, 1),
  (@l1, 'Bread', 1, 2);

INSERT INTO `tasks` (`list_id`, `text`, `completed`, `position`) VALUES
  (@l2, 'Take out trash', 0, 0),
  (@l2, 'Wash dishes', 1, 1);
