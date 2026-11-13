-- Nested Forum Lab: schema + fictional seed data (MySQL 8 / MariaDB 10.5+)
-- Run:  mysql -u root -p < database/schema.sql

DROP DATABASE IF EXISTS nested_forum;
CREATE DATABASE nested_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum;

-- All timestamps are stored in UTC (the app sets the session time zone to +00:00).
SET time_zone = '+00:00';

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(30)  NOT NULL,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NULL,          -- NULL = account cannot log in (seed users)
    avatar_filename VARCHAR(64)  NULL,          -- optional extension (not used yet)
    use_gravatar    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE discussions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE comments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT UNSIGNED NOT NULL,
    parent_id     INT UNSIGNED NULL,            -- NULL = top-level comment
    user_id       INT UNSIGNED NOT NULL,        -- the author
    body          TEXT         NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Needed so the composite foreign key below can point at (discussion_id, id).
    UNIQUE KEY uq_comments_discussion_id (discussion_id, id),
    KEY idx_comments_parent (discussion_id, parent_id),
    CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) REFERENCES discussions (id),
    CONSTRAINT fk_comments_user       FOREIGN KEY (user_id)       REFERENCES users (id),
    -- A reply's parent must be in the SAME discussion.
    -- When parent_id is NULL the check is skipped (top-level comment).
    CONSTRAINT fk_comments_parent_same_discussion
        FOREIGN KEY (discussion_id, parent_id) REFERENCES comments (discussion_id, id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Fictional seed data. Seed users have NULL password hashes,
-- so they can never log in. Register your own account to post.
-- ---------------------------------------------------------------
INSERT INTO users (id, username, email, password_hash) VALUES
    (1, 'Alice', 'alice@example.test', NULL),
    (2, 'Ben',   'ben@example.test',   NULL),
    (3, 'Cara',  'cara@example.test',  NULL),
    (4, 'Dee',   'dee@example.test',   NULL),
    (5, 'Eli',   'eli@example.test',   NULL);

INSERT INTO discussions (id, user_id, title, body, created_at) VALUES
    (1, 1, 'How do I learn PHP?', 'Share your learning tips.', '2026-11-01 08:00:00'),
    (2, 5, 'Favorite SQL tricks', 'Post one SQL feature you use often.', '2026-11-02 08:00:00');

-- Discussion 1: seven comments, deepest branch reaches displayed depth 4 (1 > 2 > 3 > 6 > 7).
INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
    (1, 1, NULL, 1, 'Start with the PHP manual.',     '2026-11-01 09:00:00'),
    (2, 1, 1,    2, 'Try the examples as you read.',  '2026-11-01 09:10:00'),
    (3, 1, 2,    3, 'Build a small project too.',     '2026-11-01 09:20:00'),
    (4, 1, 1,    4, 'Which manual section first?',    '2026-11-01 09:30:00'),
    (5, 1, NULL, 5, 'Practice SQL as well.',          '2026-11-01 09:40:00'),
    (6, 1, 3,    2, 'Start with a forum.',            '2026-11-01 09:50:00'),
    (7, 1, 6,    5, 'Add replies next.',              '2026-11-01 10:00:00');

-- Discussion 2: used to test that a cross-discussion parent is rejected.
INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
    (8, 2, NULL, 3, 'Window functions like ROW_NUMBER().', '2026-11-02 09:00:00');

ALTER TABLE users       AUTO_INCREMENT = 100;
ALTER TABLE discussions AUTO_INCREMENT = 100;
ALTER TABLE comments    AUTO_INCREMENT = 100;
